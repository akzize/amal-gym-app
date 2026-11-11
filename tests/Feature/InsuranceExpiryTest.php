<?php

namespace Tests\Feature;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Trainees\Pages\ListTrainees;
use App\Filament\Resources\Trainees\TraineeResource;
use App\Filament\Widgets\StatsOverview;
use App\Models\Association;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Sport;
use App\Models\Subscription;
use App\Models\Trainee;
use App\Models\User;
use App\Support\DashboardMetrics;
use Carbon\Carbon;
use Database\Seeders\PaymentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Today" is 2026-11-11, so "ending soon" runs to 2026-12-11.
 */
class InsuranceExpiryTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-11 12:00:00');
        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local']);
        Gate::before(fn() => true);
        $this->actingAs(User::factory()->create());

        $this->seed(PaymentTypeSeeder::class);
        $this->group = Group::forceCreate([
            'name' => 'G1', 'monthly_fee' => 200, 'insurance_fee' => 120, 'registration_fee' => 50,
            'sport_id' => Sport::forceCreate(['name' => 'Judo', 'name_ar' => 'جودو'])->id,
            'association_id' => Association::create(['name' => 'Club'])->id,
        ]);
    }

    private function trainee(string $name, bool $active = true): Trainee
    {
        $trainee = Trainee::forceCreate(['full_name' => $name, 'full_arabic_name' => $name, 'gender' => 'male', 'is_active' => $active]);
        $this->group->trainees()->attach($trainee);

        return $trainee;
    }

    private function covered(Trainee $trainee, string $from, string $to, int $type = Payment::TYPE_INSURANCE): void
    {
        Subscription::create([
            'trainee_id' => $trainee->id, 'group_id' => $this->group->id, 'payment_type_id' => $type,
            'start_date' => $from, 'end_date' => $to, 'duration_months' => 6, 'status' => 'active',
        ]);
    }

    public function test_insurance_is_flagged_from_its_end_date(): void
    {
        $expired = $this->trainee('Expired');
        $this->covered($expired, '2026-05-10', '2026-11-10');

        $endsToday = $this->trainee('Ends today');
        $this->covered($endsToday, '2026-05-11', '2026-11-11');

        $endsSoon = $this->trainee('Ends soon');
        $this->covered($endsSoon, '2026-06-11', '2026-12-11');

        $valid = $this->trainee('Valid');
        $this->covered($valid, '2026-06-12', '2026-12-12');

        // the renewal counts, not the insurance it replaced
        $renewed = $this->trainee('Renewed');
        $this->covered($renewed, '2026-01-01', '2026-07-01');
        $this->covered($renewed, '2026-07-01', '2027-01-01');

        $none = $this->trainee('None');
        // a multi-month subscription is not an insurance
        $this->covered($none, '2026-06-01', '2026-12-01', Payment::TYPE_CUSTOM);

        $inactive = $this->trainee('Inactive', active: false);
        $this->covered($inactive, '2026-01-01', '2026-07-01');

        $status = fn(Trainee $trainee) => Trainee::query()->withInsuranceEnd()->find($trainee->id)->insuranceStatus();
        $this->assertSame(Trainee::INSURANCE_EXPIRED, $status($expired));
        $this->assertSame(Trainee::INSURANCE_EXPIRING, $status($endsToday));
        $this->assertSame(Trainee::INSURANCE_EXPIRING, $status($endsSoon));
        $this->assertSame(Trainee::INSURANCE_VALID, $status($valid));
        $this->assertSame(Trainee::INSURANCE_VALID, $status($renewed));
        $this->assertSame(Trainee::INSURANCE_NONE, $status($none));

        // dashboard: active trainees only
        $this->assertSame(1, DashboardMetrics::traineesWithInsurance(Trainee::INSURANCE_EXPIRED));
        $this->assertSame(2, DashboardMetrics::traineesWithInsurance(Trainee::INSURANCE_EXPIRING));
        $this->assertSame(1, DashboardMetrics::traineesWithSubscriptionEndingSoon());

        $toRenew = [Trainee::INSURANCE_EXPIRED, Trainee::INSURANCE_EXPIRING];
        Livewire::test(StatsOverview::class)
            ->assertSee(TraineeResource::getUrl('index', ['filters' => ['insurance' => ['values' => $toRenew]]]))
            ->assertSee(TraineeResource::getUrl('index', ['filters' => ['subscription_ending_soon' => ['isActive' => true]]]));

        // the list behind the cards
        Livewire::test(ListTrainees::class)
            ->assertSee('10/11/2026 · ' . __('resources.trainee.insurance.expired'))
            ->filterTable('insurance', $toRenew)
            ->assertCanSeeTableRecords([$expired, $endsToday, $endsSoon])
            ->assertCanNotSeeTableRecords([$valid, $renewed, $none, $inactive])
            ->filterTable('insurance', [Trainee::INSURANCE_NONE])
            ->assertCanSeeTableRecords([$none])
            ->assertCanNotSeeTableRecords([$expired, $endsSoon, $valid, $renewed])
            ->resetTableFilters()
            ->filterTable('subscription_ending_soon')
            ->assertCanSeeTableRecords([$none])
            ->assertCanNotSeeTableRecords([$expired, $endsSoon, $valid, $renewed]);
    }

    public function test_a_renewed_subscription_is_not_ending_soon(): void
    {
        $trainee = $this->trainee('Renewed');
        $this->covered($trainee, '2026-09-01', '2026-12-01', Payment::TYPE_CUSTOM);
        $this->assertSame(1, DashboardMetrics::traineesWithSubscriptionEndingSoon());

        $this->covered($trainee, '2026-12-01', '2027-03-01', Payment::TYPE_CUSTOM);
        $this->assertSame(0, DashboardMetrics::traineesWithSubscriptionEndingSoon());
    }

    public function test_insurance_duration_defaults_to_six_months_and_can_be_changed(): void
    {
        $trainee = $this->trainee('Amine');

        Livewire::test(CreatePayment::class)
            ->fillForm([
                'payment_type_id' => Payment::TYPE_INSURANCE,
                'group_id' => $this->group->id,
                'trainee_id' => $trainee->id,
                'subscription' => ['start_date' => '2026-11-11'],
            ])
            ->assertFormSet(['custom_duration_months' => 6])
            ->fillForm(['custom_duration_months' => 12, 'amount_paid' => 120, 'payment_date' => '2026-11-11'])
            ->call('create')
            ->assertHasNoFormErrors();

        $subscription = Subscription::sole();
        $this->assertSame(12, (int) $subscription->duration_months);
        $this->assertSame('2027-11-11', $subscription->end_date->toDateString());
    }
}
