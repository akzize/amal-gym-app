<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Group;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Sport;
use App\Models\Trainee;
use App\Support\DashboardMetrics;
use Database\Seeders\DemoPaymentsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoPaymentsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_six_months_that_keep_the_ledger_consistent(): void
    {
        PaymentType::create(['name' => 'Monthly', 'name_ar' => 'شهري']);
        $group = Group::forceCreate([
            'name' => 'G1', 'monthly_fee' => 200,
            'sport_id' => Sport::forceCreate(['name' => 'Judo', 'name_ar' => 'جودو'])->id,
            'association_id' => Association::create(['name' => 'Club'])->id,
        ]);
        foreach (range(1, 4) as $i) {
            $group->trainees()->attach(Trainee::forceCreate([
                'full_name' => "T{$i}", 'is_active' => true, 'gender' => 'male', 'dob' => '2010-01-01', 'phone' => '0600000000', 'address' => 'x',
            ]));
        }

        $this->seed(DemoPaymentsSeeder::class);
        $this->seed(DemoPaymentsSeeder::class); // re-running adds nothing

        $this->assertSame(24, Payment::count());
        // Every dirham on payments is in the installments ledger the dashboard reads
        $this->assertEquals(Payment::sum('amount_paid'), DashboardMetrics::fromFilters(['from' => '2000-01-01', 'to' => now()->addYear()->toDateString()])->collected());
    }
}
