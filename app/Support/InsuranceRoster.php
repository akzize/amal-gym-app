<?php

namespace App\Support;

use App\Models\InsuranceExport;
use App\Models\Payment;
use Carbon\Carbon;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The list of insured trainees of an insurance export: a plain Excel sheet, or the federation's form in Word.
 */
class InsuranceRoster
{
    public const FORMATS = ['xlsx' => 'Excel (.xlsx)', 'docx' => 'Word (.docx)'];

    public const HEADERS = ['N°', 'NOM', 'PRÉNOM', 'CIN', 'DATE DE NAISSANCE', 'CATÉGORIE', 'LICENCE N°'];

    /**
     * One row per payment, sorted by name. The licence number is left blank for now.
     *
     * @return list<list<string>>
     */
    public static function rows(InsuranceExport $export): array
    {
        $rows = $export->payments()
            ->with(['trainee', 'group.sport'])
            ->get()
            ->map(fn(Payment $payment) => [
                mb_strtoupper(trim((string) $payment->trainee?->last_name)),
                mb_convert_case(trim((string) $payment->trainee?->full_name), MB_CASE_TITLE),
                (string) $payment->trainee?->cin,
                $payment->trainee?->dob ? Carbon::parse($payment->trainee->dob)->format('d/m/Y') : '',
                (string) $payment->group?->sport?->name,
                '',
            ])
            ->sortBy([[0, 'asc'], [1, 'asc']])
            ->values();

        return $rows->map(fn(array $row, int $index) => [(string) ($index + 1), ...$row])->all();
    }

    public static function fileName(InsuranceExport $export, string $format): string
    {
        return "assurance-export-{$export->id}-{$export->created_at->format('Y-m-d')}.{$format}";
    }

    public static function write(InsuranceExport $export, string $format, string $path): void
    {
        $rows = self::rows($export);

        match ($format) {
            'xlsx' => self::writeXlsx($rows, $path),
            // the federation's form numbers its own lines
            'docx' => InsuranceBordereau::write(array_map(fn(array $row) => array_slice($row, 1), $rows), $export->created_at, $path),
            default => throw new InvalidArgumentException("Unknown insurance export format [{$format}]"),
        };
    }

    public static function download(InsuranceExport $export, string $format): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'insurance');
        self::write($export, $format, $path);

        return response()->download($path, self::fileName($export, $format))->deleteFileAfterSend();
    }

    private static function writeXlsx(array $rows, string $path): void
    {
        $writer = new XlsxWriter();
        $writer->getOptions()->setColumnWidth(6, 1);
        $writer->getOptions()->setColumnWidth(22, 2, 3, 6);
        $writer->getOptions()->setColumnWidth(16, 4, 5, 7);
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(self::HEADERS, (new Style())->setFontBold()));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();
    }
}
