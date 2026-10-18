<?php

namespace Tests\Feature;

use App\Filament\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Models\AppliedDiscount;
use App\Models\Association;
use App\Models\Discount;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Trainee;
use App\Models\User;
use Database\Seeders\PaymentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class DiscountConditionsTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private Discount $threeMonths;

    private Discount $sixMonths;

    private Discount $newMember;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');
        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local']);
        Gate::before(fn() => true);
        $this->actingAs(User::factory()->create());
        $this->travelTo('2026-10-15 10:00');

        $this->seed(PaymentTypeSeeder::class);
        $this->group = Group::forceCreate([
            'name' => 'G1', 'monthly_fee' => 200,
            'sport_id' => \App\Models\Sport::forceCreate(['name' => 'Judo', 'name_ar' => 'جودو'])->id,
            'association_id' => Association::create(['name' => 'Club'])->id,
        ]);

        $this->threeMonths = $this->discount('3 months', 10, ['min_months' => 3, 'is_automatic' => true]);
        $this->sixMonths = $this->discount('6 months', 15, ['min_months' => 6, 'is_automatic' => true]);
        $this->newMember = $this->discount('New member', 20, ['new_members_only' => true, 'is_automatic' => true]);
    }

    private function discount(string $name, float $percent, array $attributes = []): Discount
    {
        return Discount::create(['name' => $name, 'name_ar' => $name, 'type' => 'percent', 'value' => $percent] + $attributes);
    }

    private function trainee(string $registeredAt): Trainee
    {
        $trainee = Trainee::forceCreate([
            'full_name' => 'Amine', 'full_arabic_name' => 'أمين', 'is_active' => true,
            'gender' => 'male', 'dob' => '2010-01-01', 'phone' => '0600000000', 'address' => 'x',
            'created_at' => $registeredAt,
        ]);
        $this->group->trainees()->attach($trainee);

        return $trainee;
    }

    private function sameIds(Discount ...$discounts): \Closure
    {
        $expected = collect($discounts)->map(fn(Discount $discount) => (string) $discount->id)->sort()->values()->all();

        return fn(array $ids) => collect($ids)->sort()->values()->all() === $expected;
    }

    private function available(array $data): array
    {
        return Discount::availableFor($data + ['payment_type_id' => Payment::TYPE_CUSTOM])->pluck('name')->all();
    }

    public function test_only_the_best_duration_tier_is_offered(): void
    {
        $this->assertSame([], $this->available(['custom_duration_months' => 2]));
        $this->assertSame(['3 months'], $this->available(['custom_duration_months' => 4]));
        $this->assertSame(['6 months'], $this->available(['custom_duration_months' => 6]));
        $this->assertSame(['6 months'], $this->available(['custom_duration_months' => 12]));
        // a single monthly payment is one month
        $this->assertSame([], $this->available(['payment_type_id' => Payment::TYPE_MONTHLY]));
    }

    public function test_new_member_means_registered_in_the_last_30_days(): void
    {
        $recent = $this->trainee('2026-09-20');
        $old = $this->trainee('2026-09-10');

        $this->assertSame(['New member'], $this->available(['trainee_id' => $recent->id]));
        $this->assertSame([], $this->available(['trainee_id' => $old->id]));
        $this->assertSame([], $this->available([])); // no trainee picked yet
    }

    public function test_validity_dates_follow_the_payment_date(): void
    {
        $this->discount('Back to school', 10, ['valid_from' => '2026-09-01', 'valid_until' => '2026-10-15']);

        $this->assertSame(['Back to school'], $this->available(['payment_date' => '2026-10-15']));
        $this->assertSame([], $this->available(['payment_date' => '2026-10-16']));
        $this->assertSame([], $this->available(['payment_date' => '2026-08-31']));
    }

    public function test_form_preselects_the_automatic_reductions_the_payment_qualifies_for(): void
    {
        $trainee = $this->trainee('2026-10-01');
        $sibling = $this->discount('Sibling', 5);

        $form = Livewire::test(CreatePayment::class)
            ->fillForm([
                'payment_type_id' => Payment::TYPE_CUSTOM,
                'group_id' => $this->group->id,
                'trainee_id' => $trainee->id,
                'custom_duration_months' => 3,
            ])
            ->assertSet('data.discount_ids', $this->sameIds($this->threeMonths, $this->newMember));

        // going to 6 months swaps the tier, and a reduction the cashier added stays
        $form->set('data.discount_ids', [(string) $this->threeMonths->id, (string) $this->newMember->id, (string) $sibling->id])
            ->set('data.custom_duration_months', 6)
            ->assertSet('data.discount_ids', $this->sameIds($this->newMember, $sibling, $this->sixMonths))
            ->assertSet('data.amount_gross', 1200.0)
            ->assertSet('data.discount_total', 480.0); // 15% + 20% + 5% of 1200
    }

    public function test_cashier_can_remove_a_preselected_reduction(): void
    {
        $trainee = $this->trainee('2026-10-01');

        Livewire::test(CreatePayment::class)
            ->fillForm([
                'payment_type_id' => Payment::TYPE_CUSTOM,
                'group_id' => $this->group->id,
                'trainee_id' => $trainee->id,
                'custom_duration_months' => 6,
                'subscription' => ['start_date' => '2026-10-15'],
                'payment_date' => '2026-10-15',
                'amount_paid' => 0,
            ])
            ->set('data.discount_ids', [(string) $this->sixMonths->id]) // new-member reduction removed
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(['6 months'], AppliedDiscount::pluck('label')->all());
        $this->assertEquals(1020, Subscription::sole()->payments->sum('amount_due'));
    }

    public function test_server_refuses_reductions_the_payment_does_not_qualify_for(): void
    {
        $trainee = $this->trainee('2026-01-01'); // not a new member

        // the price itself never counts them
        $quote = \App\Support\PriceQuote::forPayment([
            'payment_type_id' => Payment::TYPE_CUSTOM, 'group_id' => $this->group->id, 'trainee_id' => $trainee->id,
            'custom_duration_months' => 3, 'discount_ids' => [$this->sixMonths->id, $this->newMember->id, $this->threeMonths->id],
        ]);
        $this->assertSame(['3 months'], array_column($quote->lines, 'label'));

        Livewire::test(CreatePayment::class)
            ->fillForm([
                'payment_type_id' => Payment::TYPE_CUSTOM,
                'group_id' => $this->group->id,
                'trainee_id' => $trainee->id,
                'custom_duration_months' => 3,
                'subscription' => ['start_date' => '2026-10-15'],
                'payment_date' => '2026-10-15',
                'amount_paid' => 0,
            ])
            ->set('data.discount_ids', [(string) $this->sixMonths->id, (string) $this->newMember->id])
            ->call('create')
            ->assertHasFormErrors(['discount_ids.0', 'discount_ids.1']);

        $this->assertSame(0, Subscription::count());
    }

    public function test_catalog_saves_the_conditions(): void
    {
        Livewire::test(ListDiscounts::class)
            ->callAction('create', [
                'name' => '12 months', 'name_ar' => '12 شهر', 'type' => 'percent', 'value' => 25,
                'min_months' => 12, 'is_automatic' => true,
                'valid_from' => '2026-11-01', 'valid_until' => '2026-10-01',
            ])
            ->assertHasActionErrors(['valid_until']);

        Livewire::test(ListDiscounts::class)
            ->callAction('create', [
                'name' => '12 months', 'name_ar' => '12 شهر', 'type' => 'percent', 'value' => 25,
                'min_months' => 12, 'is_automatic' => true,
            ])
            ->assertHasNoActionErrors();

        $discount = Discount::where('name', '12 months')->sole();
        $this->assertSame(12, $discount->min_months);
        $this->assertTrue($discount->is_automatic);
    }
}
