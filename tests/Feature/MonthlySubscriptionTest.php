<?php

namespace Tests\Feature;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Models\AppliedDiscount;
use App\Models\Association;
use App\Models\Discount;
use App\Models\Group;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\PaymentType;
use App\Models\Sport;
use App\Models\Subscription;
use App\Models\Trainee;
use App\Models\User;
use App\Support\MonthlySubscription;
use App\Support\PriceQuote;
use Carbon\CarbonImmutable;
use Database\Seeders\PaymentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class MonthlySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private Trainee $trainee;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');
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
        $this->trainee = Trainee::forceCreate([
            'full_name' => 'Amine', 'full_arabic_name' => 'أمين', 'is_active' => true,
            'gender' => 'male', 'dob' => '2010-01-01', 'phone' => '0600000000', 'address' => 'x',
        ]);
        $this->group->trainees()->attach($this->trainee);
    }

    private function createSubscription(array $data)
    {
        return Livewire::test(CreatePayment::class)
            ->fillForm($data + [
                'payment_type_id' => Payment::TYPE_CUSTOM,
                'group_id' => $this->group->id,
                'trainee_id' => $this->trainee->id,
                'custom_duration_months' => 3,
                'subscription' => ['start_date' => '2026-10-15'],
                'payment_date' => '2026-10-15',
            ])
            ->call('create');
    }

    private function threeMonthsWithTenPercentOff(float $amountPaid = 400)
    {
        $upfront = Discount::create(['name' => '3 months', 'name_ar' => '3 أشهر', 'type' => 'percent', 'value' => 10]);

        return $this->createSubscription(['discount_ids' => [$upfront->id], 'amount_paid' => $amountPaid]);
    }

    public function test_payment_type_constants_match_the_seeded_rows(): void
    {
        $names = PaymentType::pluck('name', 'id');

        $this->assertSame('Monthly', $names[Payment::TYPE_MONTHLY]);
        $this->assertSame('Inscription', $names[Payment::TYPE_INSCRIPTION]);
        $this->assertSame('Daily Session', $names[Payment::TYPE_ONE_SESSION]);
        $this->assertSame('Insurance', $names[Payment::TYPE_INSURANCE]);
        $this->assertSame('Custom', $names[Payment::TYPE_CUSTOM]);
        $this->assertSame(50.0, PriceQuote::listPrice($this->group, Payment::TYPE_INSCRIPTION));
        $this->assertSame(120.0, PriceQuote::listPrice($this->group, Payment::TYPE_INSURANCE));
    }

    public function test_insurance_is_priced_and_lasts_as_its_type_says(): void
    {
        Livewire::test(CreatePayment::class)
            ->fillForm([
                'payment_type_id' => Payment::TYPE_INSURANCE,
                'group_id' => $this->group->id,
                'trainee_id' => $this->trainee->id,
                'subscription' => ['start_date' => '2026-10-15'],
                'amount_paid' => 120,
                'payment_date' => '2026-10-15',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $payment = Payment::sole();
        $this->assertEquals(120, $payment->amount_due);
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertSame(6, (int) $payment->subscription->duration_months);
        $this->assertSame($this->group->id, $payment->subscription->group_id);
        $this->assertSame('2027-04-15', $payment->subscription->end_date->toDateString());
    }

    public function test_subscription_becomes_one_monthly_payment_per_month(): void
    {
        $this->threeMonthsWithTenPercentOff()->assertHasNoFormErrors();

        $subscription = Subscription::sole();
        $this->assertSame('2026-10-15', $subscription->start_date->toDateString());
        $this->assertSame('2027-01-15', $subscription->end_date->toDateString());
        $this->assertSame(3, (int) $subscription->duration_months);

        $payments = $subscription->payments;
        $this->assertSame(['2026-10-01', '2026-11-01', '2026-12-01'], $payments->pluck('month_key')->all());
        $this->assertSame([Payment::TYPE_MONTHLY], $payments->pluck('payment_type_id')->unique()->values()->all());
        $this->assertEquals([200, 200, 200], $payments->pluck('amount_gross')->all());
        $this->assertEquals([20, 20, 20], $payments->pluck('discount_total')->all());
        $this->assertEquals([180, 180, 180], $payments->pluck('amount_due')->all());
        // 400 upfront settles the oldest months first
        $this->assertEquals([180, 180, 40], $payments->pluck('amount_paid')->all());
        $this->assertSame([Payment::STATUS_PAID, Payment::STATUS_PAID, Payment::STATUS_PARTIAL], $payments->pluck('status')->all());
        $this->assertEquals(400, PaymentInstallment::sum('amount_paid'));

        // the reduction is recorded once, on the subscription
        $applied = AppliedDiscount::sole();
        $this->assertTrue($applied->discountable->is($subscription));
        $this->assertEquals(60, $applied->amount);
    }

    public function test_monthly_reductions_apply_to_multi_month_subscriptions(): void
    {
        $monthly = Discount::create(['name' => 'school', 'name_ar' => 'school', 'type' => 'percent', 'value' => 10, 'payment_type_id' => Payment::TYPE_MONTHLY]);
        Discount::create(['name' => 'reg', 'name_ar' => 'reg', 'type' => 'percent', 'value' => 50, 'payment_type_id' => Payment::TYPE_INSCRIPTION]);

        $this->assertSame([$monthly->id], Discount::usableFor(Payment::TYPE_CUSTOM)->pluck('id')->all());

        $this->createSubscription(['discount_ids' => [$monthly->id], 'amount_paid' => 0])->assertHasNoFormErrors();
        $this->assertEquals(540, Subscription::sole()->payments->sum('amount_due'));
    }

    public function test_split_puts_the_rounding_on_the_last_share(): void
    {
        $this->assertSame([33.33, 33.33, 33.34], MonthlySubscription::split(100, 3));
        $this->assertSame([200.0, 200.0], MonthlySubscription::split(400, 2));
    }

    public function test_month_already_paid_blocks_the_subscription(): void
    {
        Payment::create([
            'trainee_id' => $this->trainee->id, 'group_id' => $this->group->id, 'payment_type_id' => Payment::TYPE_MONTHLY,
            'amount_gross' => 200, 'amount_due' => 200, 'amount_paid' => 200, 'applies_to_date' => '2026-11-01',
        ]);

        $this->createSubscription(['amount_paid' => 0])
            ->assertHasFormErrors(['custom_duration_months']);

        $this->assertSame(0, Subscription::count());
        $this->assertSame(1, Payment::count());
    }

    public function test_paying_more_than_the_total_is_refused(): void
    {
        $this->createSubscription(['amount_paid' => 601])
            ->assertHasFormErrors(['amount_paid']);

        $this->assertSame(0, Subscription::count());
    }

    public function test_covered_months_are_not_reported_unpaid(): void
    {
        $this->threeMonthsWithTenPercentOff();

        $this->assertSame(0, Trainee::unpaidForMonth(CarbonImmutable::parse('2026-11-10'))->count());
        $this->assertSame(1, Trainee::unpaidForMonth(CarbonImmutable::parse('2026-12-10'))->count()); // partial
        $this->assertSame(1, Trainee::unpaidForMonth(CarbonImmutable::parse('2027-01-10'))->count()); // not covered
    }

    public function test_receipt_and_edit_page_show_the_whole_subscription(): void
    {
        $this->threeMonthsWithTenPercentOff();
        $november = Payment::whereDate('month_key', '2026-11-01')->sole();

        $this->get(route('filament.admin.payments.receipt', $november))
            ->assertOk()
            ->assertSee('3 شهر')
            ->assertSee('2026-10-15')
            ->assertSee('2027-01-15')
            ->assertSee('600.00 درهم')  // list price of the three months
            ->assertSee('- 60.00 درهم')
            ->assertSee('540.00 درهم')  // net total
            ->assertSee('400.00 درهم')  // paid
            ->assertSee('140.00 درهم'); // remaining

        Livewire::test(EditPayment::class, ['record' => $november->getRouteKey()])
            ->assertSee(__('resources.subscription.reductions'))
            ->assertSee('3 أشهر: -60.00 MAD');
    }
}
