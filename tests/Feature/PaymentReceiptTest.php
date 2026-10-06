<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function makePayment(): Payment
    {
        $type = PaymentType::create(['name' => 'Monthly', 'name_ar' => 'شهري']);

        return Payment::create([
            'payment_type_id' => $type->id,
            'amount_due' => 150,
            'amount_paid' => 150,
            'status' => Payment::STATUS_PAID,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $payment = $this->makePayment();

        $this->get(route('filament.admin.payments.receipt', $payment))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        config(['app.env' => 'local']);
        $payment = $this->makePayment();

        $this->actingAs(User::factory()->create())
            ->get(route('filament.admin.payments.receipt', $payment))
            ->assertForbidden();
    }

    public function test_receipt_uses_configured_paper_width(): void
    {
        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local', 'receipt.paper_width' => '80mm']);
        $payment = $this->makePayment();

        Permission::create(['name' => 'View:Payment']);
        $user = User::factory()->create();
        $user->givePermissionTo('View:Payment');

        $this->actingAs($user)
            ->get(route('filament.admin.payments.receipt', $payment))
            ->assertOk()
            ->assertSee('--printer-width: 80mm;', false)
            ->assertSee('const paperWidth = "80mm";', false)
            ->assertSee('150.00 درهم');
    }
}
