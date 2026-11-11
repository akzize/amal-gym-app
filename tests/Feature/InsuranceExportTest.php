<?php

namespace Tests\Feature;

use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Association;
use App\Models\Group;
use App\Models\InsuranceExport;
use App\Models\Payment;
use App\Models\Sport;
use App\Models\Trainee;
use App\Models\User;
use App\Support\InsuranceRoster;
use Database\Seeders\PaymentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Tests\TestCase;
use ZipArchive;

class InsuranceExportTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
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
    }

    private function trainee(string $firstName, string $lastName, ?string $cin = null): Trainee
    {
        return Trainee::forceCreate([
            'full_name' => $firstName, 'last_name' => $lastName, 'cin' => $cin,
            'full_arabic_name' => $firstName, 'gender' => 'male', 'dob' => '2018-04-04',
        ]);
    }

    private function payment(Trainee $trainee, float $paid = 120, int $type = Payment::TYPE_INSURANCE): Payment
    {
        return Payment::create([
            'trainee_id' => $trainee->id, 'group_id' => $this->group->id, 'payment_type_id' => $type,
            'amount_gross' => 120, 'amount_due' => 120, 'amount_paid' => $paid, 'payment_date' => '2026-11-01',
        ]);
    }

    public function test_a_new_export_takes_each_paid_insurance_once(): void
    {
        $paid = $this->payment($this->trainee('hoda', 'el khyati'));
        $partial = $this->payment($this->trainee('adam', 'ouchna'), paid: 50);
        $this->payment($this->trainee('safa', 'ourahou'), type: Payment::TYPE_MONTHLY);

        $first = InsuranceExport::createFromPending(auth()->id());
        $this->assertEquals([$paid->id], $first->payments()->pluck('id')->all());

        // nothing new: no empty export is kept
        $this->assertNull(InsuranceExport::createFromPending(auth()->id()));
        $this->assertSame(1, InsuranceExport::count());

        // paid in full later, so it goes in the next export
        $partial->correctAmountPaid(120);
        $second = InsuranceExport::createFromPending(auth()->id());
        $this->assertEquals([$partial->id], $second->payments()->pluck('id')->all());
        $this->assertSame($first->id, $paid->fresh()->insurance_export_id);
    }

    public function test_rows_follow_the_insurer_columns_sorted_by_name(): void
    {
        $this->payment($this->trainee('safa', 'ourahou'));
        $this->payment($this->trainee('hoda', 'el khyati', 'AB123'));

        $rows = InsuranceRoster::rows(InsuranceExport::createFromPending(auth()->id()));

        $this->assertSame([
            ['1', 'EL KHYATI', 'Hoda', 'AB123', '04/04/2018', 'Judo', ''],
            ['2', 'OURAHOU', 'Safa', '', '04/04/2018', 'Judo', ''],
        ], $rows);
    }

    public function test_excel_and_word_files_hold_the_list(): void
    {
        $this->payment($this->trainee('hoda', 'el khyati & co', 'AB123'));
        $export = InsuranceExport::createFromPending(auth()->id());

        $xlsx = tempnam(sys_get_temp_dir(), 'test');
        InsuranceRoster::write($export, 'xlsx', $xlsx);
        $reader = new XlsxReader();
        $reader->open($xlsx);
        $lines = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $lines[] = $row->toArray();
            }
        }
        $reader->close();
        $this->assertSame(InsuranceRoster::HEADERS, $lines[0]);
        $this->assertSame('EL KHYATI & CO', $lines[1][1]);
        $this->assertSame('AB123', $lines[1][3]);

        $docx = tempnam(sys_get_temp_dir(), 'test');
        InsuranceRoster::write($export, 'docx', $docx);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($docx));
        $document = $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertNotFalse(simplexml_load_string($document), 'document.xml is well-formed');
        $this->assertStringContainsString('EL KHYATI &amp; CO', $document);
        $this->assertStringContainsString('LICENCE N°', $document);

        unlink($xlsx);
        unlink($docx);
    }

    public function test_the_payments_page_downloads_a_new_or_a_previous_export(): void
    {
        $payment = $this->payment($this->trainee('hoda', 'el khyati'));

        Livewire::test(ListPayments::class)
            ->callAction('insuranceExport', data: ['export' => 'new', 'format' => 'docx'])
            ->assertFileDownloaded();

        $export = InsuranceExport::sole();
        $this->assertSame($export->id, $payment->fresh()->insurance_export_id);

        // downloading it again doesn't create another export
        Livewire::test(ListPayments::class)
            ->callAction('insuranceExport', data: ['export' => (string) $export->id, 'format' => 'xlsx'])
            ->assertFileDownloaded(InsuranceRoster::fileName($export, 'xlsx'));
        $this->assertSame(1, InsuranceExport::count());

        // nothing left: a warning, no file
        Livewire::test(ListPayments::class)
            ->callAction('insuranceExport', data: ['export' => 'new', 'format' => 'xlsx'])
            ->assertNotified(__('resources.payment.insurance_export.nothing'))
            ->assertNoFileDownloaded();
    }
}
