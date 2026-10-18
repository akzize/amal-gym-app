<?php

namespace Tests\Feature;

use App\Filament\Resources\Payments\Pages\EditPayment;
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

class PaymentFullEditTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private Trainee $trainee;

    private bool $canFullEdit = true;

    protected function setUp(): void
    {
        parent::setUp();
        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local']);
        Gate::before(fn($user, string $ability) => $ability === 'fullEdit' ? $this->canFullEdit : true);
        $this->actingAs(User::factory()->create());

        // ids 1..6 so every Payment::TYPE_* constant has its row
        foreach (['Monthly', 'Yearly', 'Inscription', 'One session', 'Insurance', 'Custom'] as $name) {
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

    private function monthlyPayment(float $paid = 200): Payment
    {
        return Payment::create([
            'payment_type_id' => Payment::TYPE_MONTHLY, 'group_id' => $this->group->id, 'trainee_id' => $this->trainee->id,
            'amount_gross' => 200, 'discount_total' => 0, 'amount_due' => 200, 'amount_paid' => $paid,
            'applies_to_date' => '2026-10-01', 'payment_date' => '2026-10-02',
        ]);
    }

    public function test_full_edit_corrects_the_paid_amount_through_the_installments(): void
    {
        $payment = $this->monthlyPayment();
        PaymentInstallment::create(['payment_id' => $payment->id, 'amount_paid' => 0.01, 'paid_at' => '2026-10-03']);
        $payment->correctAmountPaid(200); // two installments: 199.99 then 0.01

        Livewire::test(EditPayment::class, ['record' => $payment->id])
            ->fillForm(['amount_paid' => 150])
            ->call('save')
            ->assertHasNoFormErrors();

        $payment->refresh();
        $this->assertEquals(150, $payment->amount_paid);
        $this->assertSame(Payment::STATUS_PARTIAL, $payment->status);
        $this->assertEquals(150, $payment->installments()->sum('amount_paid'));
        $this->assertSame(1, $payment->installments()->count());
    }

    public function test_full_edit_corrects_the_reduction_but_not_the_gross(): void
    {
        $payment = $this->monthlyPayment(150);

        Livewire::test(EditPayment::class, ['record' => $payment->id])
            ->fillForm(['discount_total' => 50, 'amount_gross' => 999])
            ->call('save')
            ->assertHasNoFormErrors();

        $payment->refresh();
        $this->assertEquals(200, $payment->amount_gross);
        $this->assertEquals(150, $payment->amount_due);
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
    }

    public function test_paid_amount_cannot_exceed_the_amount_due(): void
    {
        $payment = $this->monthlyPayment(100);

        Livewire::test(EditPayment::class, ['record' => $payment->id])
            ->fillForm(['amount_paid' => 250])
            ->call('save')
            ->assertHasFormErrors(['amount_paid']);

        $this->assertEquals(100, $payment->refresh()->amount_paid);
    }

    public function test_without_the_permission_the_amounts_stay_locked(): void
    {
        $this->canFullEdit = false;
        $payment = $this->monthlyPayment(100);

        Livewire::test(EditPayment::class, ['record' => $payment->id])
            ->assertFormFieldIsDisabled('amount_paid')
            ->assertFormFieldIsDisabled('discount_total')
            ->fillForm(['amount_paid' => 150, 'discount_total' => 50, 'notes' => 'ok'])
            ->call('save');

        $payment->refresh();
        $this->assertEquals(100, $payment->amount_paid);
        $this->assertEquals(200, $payment->amount_due);
        $this->assertSame('ok', $payment->notes);
    }

    public function test_reductions_only_apply_to_monthly_and_custom_payments(): void
    {
        $discount = Discount::create(['name' => 'Fixed', 'name_ar' => 'ثابت', 'type' => 'fixed', 'value' => 30]);
        $data = ['group_id' => $this->group->id, 'trainee_id' => $this->trainee->id, 'discount_ids' => [$discount->id], 'manual_discount' => 20];

        $this->assertEquals(0, PriceQuote::forPayment($data + ['payment_type_id' => Payment::TYPE_INSCRIPTION])->discountTotal);
        $this->assertEquals(50, PriceQuote::forPayment($data + ['payment_type_id' => Payment::TYPE_MONTHLY])->discountTotal);
    }
}
