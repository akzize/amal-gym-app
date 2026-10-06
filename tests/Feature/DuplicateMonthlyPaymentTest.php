<?php

namespace Tests\Feature;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Association;
use App\Models\Group;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Sport;
use App\Models\Trainee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class DuplicateMonthlyPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private Trainee $trainee;

    private Payment $existing;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');
        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local']);
        Gate::before(fn() => true);
        $this->actingAs(User::factory()->create());

        PaymentType::create(['name' => 'Monthly', 'name_ar' => 'شهري']);
        $this->group = Group::forceCreate([
            'name' => 'G1', 'monthly_fee' => 200,
            'sport_id' => Sport::forceCreate(['name' => 'Judo', 'name_ar' => 'جودو'])->id,
            'association_id' => Association::create(['name' => 'Club'])->id,
        ]);
        $this->trainee = Trainee::forceCreate([
            'full_name' => 'Amine', 'full_arabic_name' => 'أمين', 'is_active' => true,
            'gender' => 'male', 'dob' => '2010-01-01', 'phone' => '0600000000', 'address' => 'x',
        ]);
        $this->group->trainees()->attach($this->trainee);

        $this->existing = Payment::create([
            'trainee_id' => $this->trainee->id, 'group_id' => $this->group->id, 'payment_type_id' => Payment::TYPE_MONTHLY,
            'amount_due' => 200, 'amount_paid' => 50, 'status' => Payment::STATUS_PARTIAL,
            'applies_to_date' => '2026-10-01', 'payment_date' => '2026-10-02',
        ]);
    }

    private function createSecondPaymentFor(string $appliesTo)
    {
        return Livewire::test(CreatePayment::class)
            ->fillForm([
                'payment_type_id' => Payment::TYPE_MONTHLY,
                'group_id' => $this->group->id,
                'trainee_id' => $this->trainee->id,
                'amount_due' => 200,
                'amount_paid' => 200,
                'status' => Payment::STATUS_PAID,
                'applies_to_date' => $appliesTo,
                'payment_date' => '2026-10-20',
            ])
            ->call('create');
    }

    public function test_same_month_opens_a_modal_with_the_existing_payment(): void
    {
        $component = $this->createSecondPaymentFor('2026-10-15')
            ->assertActionMounted('monthlyPaymentExists')
            ->assertHasNoErrors();

        $this->assertSame(1, Payment::count());

        // Modal bodies render outside the page HTML, so render the mounted action's content directly
        $html = $component->instance()->getMountedAction()->getModalContent()->render();
        $this->assertStringContainsString('أمين', $html);
        $this->assertStringContainsString('2026-10', $html);
        $this->assertStringContainsString(__('resources.payment.status.partial'), $html);
        $this->assertStringContainsString('150.00 MAD', $html); // still owed on the existing payment
        $this->assertStringContainsString(e(PaymentResource::getUrl('edit', ['record' => $this->existing])), $html);
        $this->assertStringContainsString(e(route('filament.admin.payments.receipt', $this->existing)), $html);
    }

    public function test_another_month_is_created_normally(): void
    {
        $this->createSecondPaymentFor('2026-11-01')
            ->assertActionNotMounted('monthlyPaymentExists')
            ->assertHasNoFormErrors();

        $this->assertSame(2, Payment::count());
    }
}
