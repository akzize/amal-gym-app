<?php

namespace Tests\Feature;

use App\Filament\Resources\Trainees\Pages\ListTrainees;
use App\Filament\Resources\Trainees\TraineeResource;
use App\Filament\Widgets\CollectedPaymentsTable;
use App\Filament\Widgets\CollectedVsOutstandingChart;
use App\Filament\Widgets\PaymentsByTypeChart;
use App\Filament\Widgets\StatsOverview;
use App\Models\Association;
use App\Models\Group;
use App\Models\Payment;
use App\Models\PaymentInstallment;
use App\Models\PaymentType;
use App\Models\Sport;
use App\Models\Trainee;
use App\Models\Trainer;
use App\Models\User;
use App\Support\DashboardMetrics;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Scenario (range 2026-09-01 → 2026-10-31, "today" 2026-10-15):
 *
 *   A  monthly Sept  due 300, paid in full on 09-05            → 300 collected in Sept
 *   B  monthly Sept  due 300, 100 on 09-10 + 150 on 10-03      → 100 Sept, 150 Oct, 50 still owed (Sept)
 *   B  inscription   due  50, paid in full on 10-02            →  50 Oct
 *   A  monthly Oct   due 300, paid in full on 10-01            → 300 Oct
 *   trainer payout installment of 200 on 10-04
 *
 *   collected 900 (Sept 400, Oct 500) · outstanding 50 · net 700
 *   monthly fees: paid 850 of 900 due → 94.4%
 *   unpaid this month (Oct): B only (A paid October, C is inactive)
 */
class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private const FILTERS = ['from' => '2026-09-01', 'to' => '2026-10-31'];

    private Trainee $a;

    private Trainee $b;

    private Trainee $c;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 12:00:00');
        app()->setLocale('ar');

        // Same ids as PaymentTypeSeeder
        foreach ([['Monthly', 'شهري'], ['Yearly', 'سنوي'], ['Inscription', 'التسجيل'], ['Daily Session', 'حصة يومية'], ['Insurance', 'التأمين'], ['Custom', 'مخصص']] as [$name, $nameAr]) {
            PaymentType::create(['name' => $name, 'name_ar' => $nameAr]);
        }

        $association = Association::create(['name' => 'Club']);
        $sport = Sport::forceCreate(['name' => 'Judo', 'name_ar' => 'جودو']);
        $group = Group::forceCreate(['name' => 'G1', 'sport_id' => $sport->id, 'association_id' => $association->id, 'monthly_fee' => 300]);

        $required = ['gender' => 'male', 'dob' => '2010-01-01', 'phone' => '0600000000', 'address' => 'Ouarzazate'];
        $this->a = Trainee::forceCreate(['full_name' => 'Amine', 'full_arabic_name' => 'أمين', 'is_active' => true] + $required);
        $this->b = Trainee::forceCreate(['full_name' => 'Badr', 'full_arabic_name' => 'بدر', 'is_active' => true] + $required);
        $this->c = Trainee::forceCreate(['full_name' => 'Chama', 'full_arabic_name' => 'شامة', 'is_active' => false] + $required);
        $group->trainees()->attach([$this->a->id, $this->b->id, $this->c->id]);

        $pay = fn(Trainee $trainee, int $type, float $due, float $paid, string $date, ?string $appliesTo = null) => Payment::create([
            'trainee_id' => $trainee->id, 'group_id' => $group->id, 'payment_type_id' => $type,
            'amount_due' => $due, 'amount_paid' => $paid, 'payment_date' => $date, 'applies_to_date' => $appliesTo,
            'status' => $paid >= $due ? Payment::STATUS_PAID : Payment::STATUS_PARTIAL,
        ]);

        $pay($this->a, Payment::TYPE_MONTHLY, 300, 300, '2026-09-05', '2026-09-01');
        $partial = $pay($this->b, Payment::TYPE_MONTHLY, 300, 100, '2026-09-10', '2026-09-01');
        $pay($this->b, 3, 50, 50, '2026-10-02');
        $pay($this->a, Payment::TYPE_MONTHLY, 300, 300, '2026-10-01', '2026-10-01');

        // A later installment, recorded the way the installments relation manager does it
        PaymentInstallment::create(['payment_id' => $partial->id, 'amount_paid' => 150, 'paid_at' => '2026-10-03']);
        $partial->update(['amount_paid' => 250]);

        $trainer = Trainer::create(['name' => 'Coach', 'user_id' => User::factory()->create()->id, 'salary_type' => 'fixed', 'salary_amount' => 1000]);
        $installment = $trainer->recordPayoutInstallment('2026-10-01', 200);
        $installment->update(['paid_at' => '2026-10-04']);
    }

    public function test_metrics_for_the_scenario(): void
    {
        $metrics = DashboardMetrics::fromFilters(self::FILTERS);

        $this->assertSame(900.0, $metrics->collected());
        $this->assertSame(['2026-09' => 400.0, '2026-10' => 500.0], $metrics->collectedByMonth());
        $this->assertSame([
            'شهري' => ['2026-09' => 400.0, '2026-10' => 450.0],
            'التسجيل' => ['2026-09' => 0.0, '2026-10' => 50.0],
        ], $metrics->collectedByTypeAndMonth());
        $this->assertSame(50.0, $metrics->outstanding());
        $this->assertSame(['2026-09' => 50.0, '2026-10' => 0.0], $metrics->outstandingByMonth());
        $this->assertSame(94.4, $metrics->collectionRate());
        $this->assertSame(200.0, $metrics->trainerPayoutsPaid());
        $this->assertSame(700.0, $metrics->net());
        $this->assertSame(2, $metrics->activeTrainees());
        $this->assertSame(1, DashboardMetrics::traineesWithUnpaidMonthlyFee());
    }

    public function test_cards_charts_and_table_agree(): void
    {
        $metrics = DashboardMetrics::fromFilters(self::FILTERS);
        $collected = $metrics->collected();

        // Charts
        $chartData = fn(string $widget): array => (fn() => $this->getData())
            ->call(Livewire::test($widget, ['pageFilters' => self::FILTERS])->instance());

        $collectedVsOutstanding = $chartData(CollectedVsOutstandingChart::class);
        $this->assertSame($collected, array_sum($collectedVsOutstanding['datasets'][0]['data']));
        $this->assertSame($metrics->outstanding(), array_sum($collectedVsOutstanding['datasets'][1]['data']));
        $this->assertCount(2, $collectedVsOutstanding['labels']);

        $byType = $chartData(PaymentsByTypeChart::class);
        $this->assertSame($collected, array_sum(array_merge(...array_column($byType['datasets'], 'data'))));

        // Table: exactly the installments of the period, and its total row is the same figure
        Livewire::test(CollectedPaymentsTable::class, ['pageFilters' => self::FILTERS])
            ->assertCanSeeTableRecords($metrics->collectedQuery()->get())
            ->assertCountTableRecords(5)
            ->assertSee(DashboardMetrics::money($collected));

        // Cards
        Livewire::test(StatsOverview::class, ['pageFilters' => self::FILTERS])
            ->assertSee(DashboardMetrics::money($collected))
            ->assertSee(DashboardMetrics::money($metrics->outstanding()))
            ->assertSee('94.4%')
            ->assertSee(DashboardMetrics::money($metrics->net()));
    }

    public function test_date_range_filter_narrows_everything(): void
    {
        $october = ['from' => '2026-10-01', 'to' => '2026-10-31'];
        $metrics = DashboardMetrics::fromFilters($october);

        $this->assertSame(500.0, $metrics->collected());
        $this->assertSame(['2026-10' => 500.0], $metrics->collectedByMonth());
        // The Sept payment is still owed 50, but it belongs to September
        $this->assertSame(0.0, $metrics->outstanding());

        Livewire::test(CollectedPaymentsTable::class, ['pageFilters' => $october])
            ->assertCountTableRecords(3);
    }

    public function test_defaults_to_the_last_six_months(): void
    {
        $metrics = DashboardMetrics::fromFilters(null);

        $this->assertSame('2026-05-01', $metrics->from->toDateString());
        $this->assertSame('2026-10-31', $metrics->to->toDateString());
        $this->assertCount(6, $metrics->months());
        $this->assertSame(900.0, $metrics->collected());
    }

    public function test_new_payments_always_record_an_installment(): void
    {
        $payment = Payment::create(['payment_type_id' => 3, 'amount_due' => 80, 'amount_paid' => 80, 'status' => Payment::STATUS_PAID, 'payment_date' => '2026-10-10']);

        $this->assertSame(80.0, (float) $payment->installments()->sum('amount_paid'));
        $this->assertSame('2026-10-10', CarbonImmutable::parse($payment->installments()->first()->paid_at)->toDateString());
    }

    public function test_unpaid_card_links_to_the_matching_trainees_filter(): void
    {
        config(['app.env' => 'local']);
        Gate::before(fn() => true);
        $this->actingAs(User::factory()->create());

        Livewire::test(ListTrainees::class)
            ->filterTable('unpaid_this_month')
            ->assertCanSeeTableRecords([$this->b])
            ->assertCanNotSeeTableRecords([$this->a, $this->c]);

        Livewire::test(StatsOverview::class, ['pageFilters' => self::FILTERS])
            ->assertSee(TraineeResource::getUrl('index', ['filters' => ['unpaid_this_month' => ['isActive' => true]]]));
    }
}
