<?php

namespace App\Support;

use App\Models\InsuranceExport;
use App\Models\Payment;
use Carbon\Carbon;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * The list of insured trainees of an insurance export, as the insurer expects it, in Excel or Word.
 */
class InsuranceRoster
{
    public const FORMATS = ['xlsx' => 'Excel (.xlsx)', 'docx' => 'Word (.docx)'];

    public const HEADERS = ['N°', 'NOM', 'PRÉNOM', 'CIN', 'DATE DE NAISSANCE', 'CATÉGORIE', 'LICENCE N°'];

    // Word column widths in twips (1/1440 inch), filling an A4 page with 1000 twips margins
    private const DOCX_WIDTHS = [500, 1900, 1900, 1200, 1400, 1606, 1400];

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
        match ($format) {
            'xlsx' => self::writeXlsx(self::rows($export), $path),
            'docx' => self::writeDocx($export, self::rows($export), $path),
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

    /**
     * A .docx is a zip of XML parts; a title and one bordered table only need these three.
     */
    private static function writeDocx(InsuranceExport $export, array $rows, string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create {$path}");
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>');
        $zip->addFromString('word/document.xml', self::docxDocument($export, $rows));
        $zip->close();
    }

    private static function docxDocument(InsuranceExport $export, array $rows): string
    {
        $subtitle = 'Export n° ' . $export->id . ' du ' . $export->created_at->format('d/m/Y') . ' — ' . count($rows) . ' assuré(s)';

        $grid = implode('', array_map(fn(int $width) => "<w:gridCol w:w=\"{$width}\"/>", self::DOCX_WIDTHS));
        $border = 'w:val="single" w:sz="4" w:space="0" w:color="000000"';
        $table = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblBorders>'
            . "<w:top {$border}/><w:left {$border}/><w:bottom {$border}/><w:right {$border}/><w:insideH {$border}/><w:insideV {$border}/>"
            . '</w:tblBorders><w:tblLayout w:type="fixed"/></w:tblPr>'
            . "<w:tblGrid>{$grid}</w:tblGrid>"
            . self::docxRow(self::HEADERS, header: true)
            . implode('', array_map(fn(array $row) => self::docxRow($row), $rows))
            . '</w:tbl>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . self::docxParagraph('Liste des assurés', size: 32, bold: true)
            . self::docxParagraph($subtitle, size: 22)
            . $table
            // A4 portrait
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1000" w:right="1000" w:bottom="1000" w:left="1000" w:header="500" w:footer="500" w:gutter="0"/></w:sectPr>'
            . '</w:body></w:document>';
    }

    private static function docxParagraph(string $text, int $size, bool $bold = false): string
    {
        return '<w:p><w:pPr><w:spacing w:after="200"/><w:jc w:val="center"/></w:pPr>' . self::docxRun($text, $size, $bold) . '</w:p>';
    }

    private static function docxRow(array $cells, bool $header = false): string
    {
        // A header row is repeated at the top of every page
        $xml = '<w:tr>' . ($header ? '<w:trPr><w:tblHeader/></w:trPr>' : '');

        foreach (array_values($cells) as $index => $text) {
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . self::DOCX_WIDTHS[$index] . '" w:type="dxa"/>'
                . ($header ? '<w:shd w:val="clear" w:color="auto" w:fill="D9D9D9"/>' : '')
                . '<w:vAlign w:val="center"/></w:tcPr><w:p>' . self::docxRun($text, 20, $header) . '</w:p></w:tc>';
        }

        return $xml . '</w:tr>';
    }

    private static function docxRun(string $text, int $size, bool $bold): string
    {
        $text = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<w:r><w:rPr>' . ($bold ? '<w:b/>' : '') . "<w:sz w:val=\"{$size}\"/></w:rPr><w:t xml:space=\"preserve\">{$text}</w:t></w:r>";
    }
}
