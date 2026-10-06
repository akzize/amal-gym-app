<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Group;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Sport;
use App\Models\Trainer;
use App\Models\User;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * The app locale is Arabic, but numbers must always render with Western digits (0-9),
 * never Arabic-Indic (٠١٢٣) or Persian (۰۱۲۳) ones.
 */
class WesternDigitsTest extends TestCase
{
    use RefreshDatabase;

    private const EASTERN_DIGITS = '/[\x{0660}-\x{0669}\x{06F0}-\x{06F9}]/u';

    /**
     * Whether plain "ar" gives Arabic-Indic digits depends on the server's ICU version
     * (ICU 77 gives 0-9, older ones and ar_EG/ar_SA give ٠-٩), so the page scan below
     * can pass on a dev machine even without the fix. Assert the fix itself too.
     */
    public function test_number_formatting_is_forced_to_english(): void
    {
        $this->assertSame('en', Number::defaultLocale());
        $this->assertSame('1,234.50', Number::format(1234.5, 2));

        $this->assertSame('en', Schema::make()->getDefaultNumberLocale());
    }

    public function test_admin_pages_render_western_digits(): void
    {
        app()->setLocale('ar');
        // User doesn't implement FilamentUser, so Filament only lets it into the panel in "local"
        config(['app.env' => 'local']);
        Gate::before(fn() => true);
        $this->actingAs($user = User::factory()->create());

        $association = Association::create(['name' => 'Club', 'name_arabic' => 'جمعية']);
        $sport = Sport::forceCreate(['name' => 'Judo', 'name_ar' => 'جودو']);
        $trainer = Trainer::create(['name' => 'Coach', 'user_id' => $user->id, 'salary_type' => 'fixed', 'salary_amount' => 12345.5]);
        $group = Group::forceCreate([
            'name' => 'G1', 'sport_id' => $sport->id, 'association_id' => $association->id, 'trainer_id' => $trainer->id,
            'monthly_fee' => 1234.5, 'insurance_fee' => 150, 'registration_fee' => 200, 'max_capacity' => 25,
        ]);
        $type = PaymentType::create(['name' => 'Monthly', 'name_ar' => 'شهري']);
        $payment = Payment::create([
            'payment_type_id' => $type->id, 'group_id' => $group->id,
            'amount_due' => 1234.5, 'amount_paid' => 987.25, 'status' => Payment::STATUS_PARTIAL,
            'applies_to_date' => '2026-10-01',
        ]);
        $trainer->recordPayoutInstallment('2026-10-01', 4321);

        $pages = [
            '/admin',
            '/admin/payments',
            '/admin/payments/create',
            "/admin/payments/{$payment->id}/edit",
            '/admin/trainers',
            "/admin/trainers/{$trainer->id}/edit",
            '/admin/groups',
            "/admin/groups/{$group->id}/edit",
            route('filament.admin.payments.receipt', $payment, false),
            route('filament.admin.trainer-payouts.receipt', $trainer->payouts()->first()->installments()->first(), false),
        ];

        foreach ($pages as $page) {
            $html = $this->get($page)->assertOk()->getContent();

            preg_match(self::EASTERN_DIGITS, $html, $match, PREG_OFFSET_CAPTURE);
            $context = $match ? mb_substr(substr($html, max(0, $match[0][1] - 120), 240), 0) : '';
            $this->assertEmpty($match, "Eastern digits on {$page}: …{$context}…");
        }
    }
}
