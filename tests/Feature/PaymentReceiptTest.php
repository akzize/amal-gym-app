<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Group;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function makePayment(array $attributes = []): Payment
    {
        $type = PaymentType::create(['name' => 'Monthly', 'name_ar' => 'شهري']);

        return Payment::create($attributes + [
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
            ->assertSee('window.print()', false)
            ->assertSee('150.00 درهم');
    }

    private function viewReceipt(Payment $payment)
    {
        config(['app.env' => 'local']);
        Permission::firstOrCreate(['name' => 'View:Payment']);
        $user = User::factory()->create();
        $user->givePermissionTo('View:Payment');

        return $this->actingAs($user)->get(route('filament.admin.payments.receipt', $payment));
    }

    private function makeGroup(array $associationAttributes): Group
    {
        $association = Association::create(['name' => 'Club'] + $associationAttributes);
        $sport = Sport::forceCreate(['name' => 'Judo', 'name_ar' => 'جودو']);

        return Group::forceCreate(['name' => 'G1', 'sport_id' => $sport->id, 'association_id' => $association->id]);
    }

    public function test_receipt_shows_the_group_association_logo(): void
    {
        Storage::fake(config('filament.default_filesystem_disk', config('filesystems.default')))
            ->put('logos/club.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        $group = $this->makeGroup(['logo' => 'logos/club.png']);

        $this->viewReceipt($this->makePayment(['group_id' => $group->id]))
            ->assertOk()
            ->assertSee('<img class="logo" src="data:image/png;base64,', false);
    }

    public function test_receipt_has_no_logo_when_the_file_is_missing(): void
    {
        Storage::fake(config('filament.default_filesystem_disk', config('filesystems.default')));
        // the column default points to an image that doesn't exist
        $group = $this->makeGroup([]);

        $this->viewReceipt($this->makePayment(['group_id' => $group->id]))
            ->assertOk()
            ->assertDontSee('class="logo"', false);
    }
}
