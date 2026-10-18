<?php

namespace Tests\Feature;

use App\Filament\Resources\Groups\Pages\EditGroup;
use App\Filament\Resources\Groups\Pages\ListGroups;
use App\Filament\Resources\Groups\RelationManagers\TraineesRelationManager;
use App\Filament\Resources\Sports\Pages\ListSports;
use App\Filament\Resources\Trainees\Pages\CreateTrainee;
use App\Filament\Resources\Trainees\Pages\EditTrainee;
use App\Filament\Resources\Trainees\Pages\ListTrainees;
use App\Filament\Resources\Trainers\Pages\ListTrainers;
use App\Filament\Widgets\MonthlyTrainerPayments;
use App\Models\Association;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Sport;
use App\Models\Trainee;
use App\Models\Trainer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PaymentTypeSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class DeactivateInsteadOfDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Sport $sport;

    private Group $group;

    private Trainer $trainer;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn() => true);
        $this->actingAs(User::factory()->create());
        $this->seed(PaymentTypeSeeder::class);

        $this->trainer = Trainer::create(['name' => 'Coach', 'user_id' => User::factory()->create()->id, 'salary_type' => 'fixed', 'salary_amount' => 1000]);
        $this->sport = Sport::forceCreate(['name' => 'Judo', 'name_ar' => 'جودو']);
        $this->group = Group::forceCreate([
            'name' => 'G1', 'monthly_fee' => 200, 'sport_id' => $this->sport->id, 'trainer_id' => $this->trainer->id,
            'association_id' => Association::create(['name' => 'Club'])->id,
        ]);
    }

    private function trainee(string $name = 'Amine'): Trainee
    {
        $trainee = Trainee::forceCreate(['full_name' => $name, 'full_arabic_name' => $name, 'gender' => 'male']);
        $this->group->trainees()->attach($trainee);

        return $trainee;
    }

    private function payFor(Trainee $trainee): Payment
    {
        return Payment::create([
            'trainee_id' => $trainee->id, 'group_id' => $this->group->id, 'payment_type_id' => Payment::TYPE_MONTHLY,
            'amount_gross' => 200, 'amount_due' => 200, 'amount_paid' => 200, 'applies_to_date' => now()->toDateString(),
        ]);
    }

    public function test_new_trainees_are_active(): void
    {
        Livewire::test(CreateTrainee::class)
            ->fillForm(['full_name' => 'Sara', 'full_arabic_name' => 'سارة', 'gender' => 'female'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Trainee::sole()->is_active);
    }

    public function test_deactivated_trainee_is_hidden_and_not_counted_unpaid(): void
    {
        $trainee = $this->trainee();
        $this->assertSame(1, Trainee::unpaidForMonth(CarbonImmutable::now())->count());

        Livewire::test(ListTrainees::class)
            ->callTableAction('deactivate', $trainee)
            ->assertCanNotSeeTableRecords([$trainee]);

        $this->assertFalse($trainee->fresh()->is_active);
        $this->assertSame(0, Trainee::unpaidForMonth(CarbonImmutable::now())->count());
        // still in the group, and back with the filter
        $this->assertTrue($this->group->trainees()->whereKey($trainee->id)->exists());
        Livewire::test(ListTrainees::class)
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$trainee])
            ->callTableAction('activate', $trainee);
        $this->assertTrue($trainee->fresh()->is_active);
    }

    public function test_delete_is_only_offered_without_history(): void
    {
        $paid = $this->trainee('Paid');
        $mistake = $this->trainee('Mistake');
        $this->payFor($paid);

        Livewire::test(ListTrainees::class)
            ->assertTableActionHidden('delete', $paid)
            ->assertTableActionVisible('delete', $mistake);

        Livewire::test(ListSports::class)->assertTableActionHidden('delete', $this->sport);
        $unused = Sport::forceCreate(['name' => 'Boxe', 'name_ar' => 'ملاكمة']);
        Livewire::test(ListSports::class)->assertTableActionVisible('delete', $unused);

        Livewire::test(ListGroups::class)->assertTableActionHidden('delete', $this->group);
        Livewire::test(ListTrainers::class)->assertTableActionHidden('delete', $this->trainer);
    }

    public function test_database_refuses_deletes_that_would_wipe_history(): void
    {
        $this->payFor($this->trainee());

        foreach ([$this->sport, $this->group, Trainee::sole()] as $record) {
            try {
                $record->delete();
                $this->fail(class_basename($record) . ' with history was deleted');
            } catch (QueryException) {
                $this->assertModelExists($record);
            }
        }
        $this->assertSame(1, Payment::count());
    }

    public function test_deactivated_trainer_cannot_log_in_and_is_not_paid(): void
    {
        Livewire::test(MonthlyTrainerPayments::class)->assertCanSeeTableRecords([$this->trainer]);
        $this->actingAs($this->trainer->user)->get('/admin')->assertOk();

        Livewire::test(ListTrainers::class)->callTableAction('deactivate', $this->trainer);

        $this->assertSame(Trainer::STATUS_INACTIVE, $this->trainer->fresh()->status);
        // a real next request reloads the user from the session
        $this->actingAs($this->trainer->user->fresh())->get('/admin')->assertForbidden();
        Livewire::test(MonthlyTrainerPayments::class)->assertCanNotSeeTableRecords([$this->trainer]);
    }

    public function test_closed_group_is_hidden_but_kept(): void
    {
        $trainee = $this->trainee();

        Livewire::test(ListGroups::class)
            ->callTableAction('close', $this->group, ['closed_at' => '2026-10-31'])
            ->assertCanNotSeeTableRecords([$this->group]);

        $this->assertSame('2026-10-31', $this->group->fresh()->closed_at->toDateString());
        $this->assertSame(0, Trainee::unpaidForMonth(CarbonImmutable::now())->count());

        Livewire::test(ListGroups::class)
            ->filterTable('closed_at', true)
            ->assertCanSeeTableRecords([$this->group])
            ->callTableAction('reopen', $this->group);
        $this->assertNull($this->group->fresh()->closed_at);
        $this->assertTrue($this->group->trainees()->whereKey($trainee->id)->exists());
    }

    public function test_saving_a_trainee_keeps_their_closed_group(): void
    {
        $trainee = $this->trainee();
        $this->group->update(['closed_at' => now()]);

        Livewire::test(EditTrainee::class, ['record' => $trainee->getRouteKey()])
            ->fillForm(['phone' => '0600000000'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($trainee->groups()->whereKey($this->group->id)->exists());
    }

    public function test_group_trainees_tab_only_removes_from_the_group(): void
    {
        $trainee = $this->trainee();

        Livewire::test(TraineesRelationManager::class, ['ownerRecord' => $this->group, 'pageClass' => EditGroup::class])
            ->assertTableActionDoesNotExist('delete')
            ->callTableAction('detach', $trainee);

        $this->assertModelExists($trainee);
        $this->assertFalse($this->group->trainees()->whereKey($trainee->id)->exists());
    }
}
