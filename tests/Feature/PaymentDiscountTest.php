<?php

namespace Tests\Feature;

use App\Filament\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Models\AppliedDiscount;
use App\Models\Association;
use App\Models\Discount;
use App\Models\Group;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\PaymentType;
use App\Models\Sport;
use App\Models\Trainee;
use App\Models\User;
use App\Support\PriceQuote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentDiscountTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private Trainee $trainee;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');
        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local']);
        Gate::before(fn() => true);
        $this->actingAs($this->user = User::factory()->create());

        // ids 1..6 so every Payment::TYPE_* constant has its row
        foreach (['Monthly', 'Yearly', 'Insurance', 'One session', 'Inscription', 'Custom'] as $name) {
            PaymentType::create(['name' => $name, 'name_ar' => $name]);
        }
        $this->group = Group::forceCreate([
            'name' => 'G1', 'monthly_fee' => 200, 'registration_fee' => 100,
            'sport_id' => Sport::forceCreate(['name' => 'Judo', 'name_ar' => 'جودو'])->id,
            'association_id' => Association::create(['name' => 'Club'])->id,
        ]);
        $this->trainee = Trainee::forceCreate([
            'full_name' => 'Amine', 'full_arabic_name' => 'أمين', 'is_active' => true,
            'gender' => 'male', 'dob' => '2010-01-01', 'phone' => '0600000000', 'address' => 'x',
        ]);
        $this->group->trainees()->attach($this->trainee);
    }

    private function discount(string $type, float $value, array $attributes = []): Discount
    {
        return Discount::create(['name' => "{$type} {$value}", 'name_ar' => "تخفيض {$value}", 'type' => $type, 'value' => $value] + $attributes);
    }

    private function createMonthlyPayment(array $data)
    {
        return Livewire::test(CreatePayment::class)
            ->fillForm($data + [
                'payment_type_id' => Payment::TYPE_MONTHLY,
                'group_id' => $this->group->id,
                'trainee_id' => $this->trainee->id,
                'applies_to_date' => '2026-10-01',
                'payment_date' => '2026-10-02',
            ])
            ->call('create');
    }

    public function test_quote_applies_percentages_to_gross_and_caps_the_total(): void
    {
        $quote = PriceQuote::make(200, [$this->discount('percent', 50), $this->discount('percent', 30)], 100);

        // 100 + 60 = 160, then the manual 100 is cut to the 40 left
        $this->assertSame(200.0, $quote->gross);
        $this->assertSame(200.0, $quote->discountTotal);
        $this->assertSame(0.0, $quote->net());
        $this->assertSame([100.0, 60.0, 40.0], array_column($quote->lines, 'amount'));
    }

    public function test_custom_subscription_is_priced_per_month(): void
    {
        $this->assertSame(600.0, PriceQuote::listPrice($this->group, Payment::TYPE_CUSTOM, 3));
        $this->assertNull(PriceQuote::listPrice($this->group, Payment::TYPE_YEARLY));
    }

    public function test_reduction_is_recorded_and_amount_comes_from_the_server(): void
    {
        $sibling = $this->discount('percent', 20);

        $this->createMonthlyPayment([
            'discount_ids' => [$sibling->id],
            'amount_due' => 1, // whatever the browser sends is ignored
            'amount_paid' => 100,
            'discount_reason' => 'brother already enrolled',
        ])->assertHasNoFormErrors();

        $payment = Payment::sole();
        $this->assertEquals(200, $payment->amount_gross);
        $this->assertEquals(40, $payment->discount_total);
        $this->assertEquals(160, $payment->amount_due);
        $this->assertSame(Payment::STATUS_PARTIAL, $payment->status);

        $applied = AppliedDiscount::sole();
        $this->assertTrue($applied->discountable->is($payment));
        $this->assertSame($sibling->id, $applied->discount_id);
        $this->assertEquals(40, $applied->amount);
        $this->assertSame('brother already enrolled', $applied->reason);
        $this->assertSame($this->user->id, $applied->approved_by);
    }

    public function test_discount_for_another_payment_type_is_ignored(): void
    {
        $registrationOnly = $this->discount('percent', 50, ['payment_type_id' => Payment::TYPE_INSCRIPTION]);

        $this->createMonthlyPayment(['discount_ids' => [$registrationOnly->id], 'amount_paid' => 0]);

        $this->assertEquals(200, Payment::sole()->amount_due);
        $this->assertSame(0, AppliedDiscount::count());
    }

    public function test_manual_reduction_needs_a_reason(): void
    {
        $this->createMonthlyPayment(['manual_discount' => 50, 'amount_paid' => 0])
            ->assertHasFormErrors(['discount_reason' => 'required']);

        $this->assertSame(0, Payment::count());
    }

    public function test_full_reduction_makes_the_payment_free(): void
    {
        $this->createMonthlyPayment(['manual_discount' => 500, 'discount_reason' => 'scholarship', 'amount_paid' => 0])
            ->assertHasNoFormErrors();

        $payment = Payment::sole();
        $this->assertEquals(0, $payment->amount_due);
        $this->assertEquals(200, $payment->discount_total); // capped at the list price
        $this->assertSame(Payment::STATUS_FREE, $payment->status);
    }

    public function test_installments_keep_amount_paid_and_status_in_sync(): void
    {
        $payment = Payment::create([
            'trainee_id' => $this->trainee->id, 'group_id' => $this->group->id, 'payment_type_id' => Payment::TYPE_MONTHLY,
            'amount_gross' => 200, 'amount_due' => 200, 'amount_paid' => 50, 'applies_to_date' => '2026-10-01',
        ]);
        $this->assertSame(Payment::STATUS_PARTIAL, $payment->status);

        $rest = PaymentInstallment::create(['payment_id' => $payment->id, 'amount_paid' => 150, 'paid_at' => now()]);
        $this->assertEquals(200, $payment->fresh()->amount_paid);
        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);

        $rest->delete();
        $this->assertEquals(50, $payment->fresh()->amount_paid);
        $this->assertSame(Payment::STATUS_PARTIAL, $payment->fresh()->status);
    }

    public function test_receipt_lists_the_reduction(): void
    {
        $this->createMonthlyPayment(['manual_discount' => 30, 'discount_reason' => 'promo', 'amount_paid' => 170]);

        $this->get(route('filament.admin.payments.receipt', Payment::sole()))
            ->assertOk()
            ->assertSee(__('resources.discount.manual'))
            ->assertSee('- 30.00')
            ->assertSee('170.00');
    }

    public function test_discount_catalog_page_creates_a_discount(): void
    {
        Livewire::test(ListDiscounts::class)
            ->callAction('create', ['name' => 'Sibling', 'name_ar' => 'إخوة', 'type' => 'percent', 'value' => 150])
            ->assertHasActionErrors(['value']);

        Livewire::test(ListDiscounts::class)
            ->callAction('create', ['name' => 'Sibling', 'name_ar' => 'إخوة', 'type' => 'percent', 'value' => 20])
            ->assertHasNoActionErrors();

        $this->assertEquals(20, Discount::sole()->value);
    }
}
