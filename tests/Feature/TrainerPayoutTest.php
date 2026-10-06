<?php

namespace Tests\Feature;

use App\Filament\Widgets\MonthlyTrainerPayments;
use App\Models\Association;
use App\Models\Group;
use App\Models\Sport;
use App\Models\Trainer;
use App\Models\TrainerPayout;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerPayoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeTrainer(float $salary = 3000): Trainer
    {
        return Trainer::create([
            'name' => 'Coach',
            'user_id' => User::factory()->create()->id,
            'salary_type' => 'fixed',
            'salary_amount' => $salary,
        ]);
    }

    public function test_installments_add_up_to_paid(): void
    {
        $trainer = $this->makeTrainer();

        $trainer->recordPayoutInstallment('2026-10-15', 1000);
        $payout = $trainer->payoutFor('2026-10-01');
        $this->assertSame(TrainerPayout::STATUS_PARTIAL, $payout->status);
        $this->assertEquals(2000, $payout->remainingAmount());

        $trainer->recordPayoutInstallment('2026-10-20', 2000);
        $payout = $trainer->payoutFor('2026-10-01');
        $this->assertSame(TrainerPayout::STATUS_PAID, $payout->status);
        $this->assertEquals(0, $payout->remainingAmount());

        // Both installments land on the same monthly payout
        $this->assertSame(1, $trainer->payouts()->count());
        $this->assertSame(2, $payout->installments()->count());
    }

    public function test_expected_amount_is_frozen_after_first_installment(): void
    {
        $trainer = $this->makeTrainer(3000);
        $trainer->recordPayoutInstallment('2026-10-01', 1000);

        $trainer->update(['salary_amount' => 5000]);
        $trainer->refresh()->recordPayoutInstallment('2026-10-01', 2000);

        $payout = $trainer->payoutFor('2026-10-01');
        $this->assertEquals(3000, $payout->expected_amount);
        $this->assertSame(TrainerPayout::STATUS_PAID, $payout->status);
    }

    public function test_months_are_tracked_separately(): void
    {
        $trainer = $this->makeTrainer();
        $trainer->recordPayoutInstallment('2026-09-10', 3000);
        $trainer->recordPayoutInstallment('2026-10-10', 500);

        $this->assertSame(TrainerPayout::STATUS_PAID, $trainer->payoutFor('2026-09-01')->status);
        $this->assertSame(TrainerPayout::STATUS_PARTIAL, $trainer->payoutFor('2026-10-01')->status);
        $this->assertNull($trainer->payoutFor('2026-11-01'));
    }

    public function test_paying_more_than_remaining_shows_arabic_error(): void
    {
        app()->setLocale('ar');
        $this->actingAs(User::factory()->create());
        $trainer = $this->makeTrainer(3000);
        $trainer->recordPayoutInstallment(now(), 1000);

        $component = Livewire::test(MonthlyTrainerPayments::class)
            ->callAction(TestAction::make('recordPayment')->table($trainer), data: [
                'applies_to_date' => now()->startOfMonth()->toDateString(),
                'amount_paid' => 2500,
            ])
            ->assertHasActionErrors(['amount_paid' => 'max']);

        $this->assertContains(
            'لا يمكن أن يتجاوز المبلغ المستحق دفعه الآن المبلغ المتبقي (2,000.00 MAD).',
            $component->errors()->all(),
        );

        $this->assertSame(1, $trainer->payoutFor(now())->installments()->count());
    }

    public function test_display_name_prefers_arabic(): void
    {
        $trainer = $this->makeTrainer();
        $this->assertSame('Coach', $trainer->display_name);

        $trainer->update(['name_ar' => 'المدرب']);
        $this->assertSame('المدرب', $trainer->display_name);
    }

    private function userWhoCanViewTrainers(): User
    {
        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local']);
        Permission::firstOrCreate(['name' => 'View:Trainer']);
        $user = User::factory()->create();
        $user->givePermissionTo('View:Trainer');

        return $user;
    }

    public function test_receipt_requires_login_and_permission(): void
    {
        $installment = $this->makeTrainer()->recordPayoutInstallment('2026-10-01', 1000);
        $url = route('filament.admin.trainer-payouts.receipt', $installment);

        $this->get($url)->assertRedirect(route('filament.admin.auth.login'));

        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    public function test_receipt_shows_running_totals_as_of_that_installment(): void
    {
        $trainer = $this->makeTrainer(3000);
        $trainer->update(['name_ar' => 'المدرب']);
        $first = $trainer->recordPayoutInstallment('2026-10-01', 1000);
        $trainer->recordPayoutInstallment('2026-10-01', 500);

        // Reprinting the first receipt still shows the balance as it was at the time
        $this->actingAs($this->userWhoCanViewTrainers())
            ->get(route('filament.admin.trainer-payouts.receipt', $first))
            ->assertOk()
            ->assertSee('المدرب')
            ->assertSee('2026-10')
            ->assertSeeInOrder(['الأجرة الشهرية', '3 000.00 درهم', 'المبلغ المدفوع', '1 000.00 درهم', 'المبلغ المتبقي', '2 000.00 درهم'])
            ->assertSee('window.print()', false);
    }

    public function test_receipt_shows_recorder_as_cashier_and_association_name(): void
    {
        $trainer = $this->makeTrainer();
        $association = Association::create(['name' => 'Club Amal', 'name_arabic' => 'جمعية أمل']);
        $sport = Sport::forceCreate(['name' => 'Judo', 'name_ar' => 'جودو']);
        Group::forceCreate(['name' => 'G1', 'sport_id' => $sport->id, 'association_id' => $association->id, 'trainer_id' => $trainer->id]);

        $this->actingAs(User::factory()->create(['name' => 'Cashier Karim']));
        $installment = $trainer->recordPayoutInstallment('2026-10-01', 1000);

        // Someone else reprints it: the cashier is still the one who recorded it
        $viewer = $this->userWhoCanViewTrainers();
        $url = route('filament.admin.trainer-payouts.receipt', $installment);

        $this->actingAs($viewer)->get($url)
            ->assertOk()
            ->assertSee('Cashier Karim')
            ->assertDontSee($viewer->name)
            ->assertSee('جمعية أمل')
            ->assertDontSee('مركز أمل للياقة البدنية');

        // No Arabic association name: fall back to the French one
        $association->update(['name_arabic' => null]);
        $this->get($url)->assertSee('Club Amal');
    }

    public function test_receipts_modal_lists_installments_with_print_links(): void
    {
        $this->actingAs(User::factory()->create());
        $trainer = $this->makeTrainer();
        $installment = $trainer->recordPayoutInstallment(now(), 1000);

        // Modal bodies render outside the widget's HTML, so render the action's content directly
        $html = MonthlyTrainerPayments::makeReceiptsAction()->record($trainer)->getModalContent()->render();

        $this->assertStringContainsString(route('filament.admin.trainer-payouts.receipt', $installment), $html);
        $this->assertStringContainsString('1,000.00 MAD', $html);
    }

    public function test_deleting_an_installment_recomputes_status(): void
    {
        $trainer = $this->makeTrainer();
        $installment = $trainer->recordPayoutInstallment('2026-10-01', 3000);

        $installment->delete();

        $this->assertSame(TrainerPayout::STATUS_UNPAID, $trainer->payoutFor('2026-10-01')->status);
    }
}
