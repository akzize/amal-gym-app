<?php

namespace App\Support;

use Carbon\CarbonInterface;
use RuntimeException;
use ZipArchive;

/**
 * The federation's insurance form ("bordereau") as a Word file: one A4 page per 25 trainees,
 * laid out like the paper form (logos, association box, disciplines, numbered table, signature boxes).
 *
 * A .docx is a zip of XML parts. Everything is laid out with tables, the layout Word renders most reliably.
 */
class InsuranceBordereau
{
    // Twips (1/1440 inch): A4 with narrow margins leaves 10706 across
    private const PAGE_WIDTH = 11906;
    private const PAGE_HEIGHT = 16838;
    private const MARGIN_X = 600;
    private const MARGIN_Y = 400;
    private const CONTENT_WIDTH = self::PAGE_WIDTH - 2 * self::MARGIN_X;

    private const GREEN = '70AD47';
    private const BLACK = '000000';

    private const HEADERS = ['Ordre', 'Nom', 'Prénom', 'CIN N°', 'Date de Naissance', 'Catégorie', 'Licence N°'];
    private const COLUMN_WIDTHS = [650, 2356, 2300, 1300, 1500, 1500, 1100];

    private const DISCIPLINES = [
        ['Karaté Tradi', 'Karaté-Jutsu', 'Karaté-contact'],
        ['Karaté-Fitness', 'Karaté- Artistique', 'Karaté-Taichi'],
        ['Kenpo', 'Kokushnkai', 'Takeda Budo'],
    ];

    private const EMU_PER_CM = 360000;

    /** @var array<string, string> image key => relationship id, for the images found on disk */
    private array $images = [];

    private int $drawings = 0;

    /**
     * @param  list<list<string>>  $rows  nom, prénom, cin, date de naissance, catégorie, licence
     */
    public static function write(array $rows, CarbonInterface $date, string $path): void
    {
        (new self())->save($rows, $date, $path);
    }

    private function save(array $rows, CarbonInterface $date, string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create {$path}");
        }

        $relationships = '';
        foreach (config('insurance.bordereau.images') as $key => $file) {
            // a missing image leaves its place empty instead of breaking the export
            if (is_file(public_path($file))) {
                $id = 'rId' . (count($this->images) + 1);
                $this->images[$key] = $id;
                $zip->addFile(public_path($file), "word/media/{$key}.png");
                $relationships .= "<Relationship Id=\"{$id}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/image\" Target=\"media/{$key}.png\"/>";
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $zip->addFromString('[Content_Types].xml', $xml
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Default Extension="png" ContentType="image/png"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', $xml
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>');
        $zip->addFromString('word/_rels/document.xml.rels', $xml
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $relationships . '</Relationships>');
        $zip->addFromString('word/document.xml', $xml . $this->document($rows, $date));
        $zip->close();
    }

    private function document(array $rows, CarbonInterface $date): string
    {
        // an export without trainees still gives one blank form
        $pages = array_chunk($rows, config('insurance.bordereau.rows_per_page')) ?: [[]];
        $body = implode($this->pageBreak(), array_map(fn(array $page) => $this->page($page, $date), $pages));

        return '<w:document'
            . ' xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
            . ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
            . ' xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"'
            . ' xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<w:body>' . $body
            . '<w:sectPr><w:pgSz w:w="' . self::PAGE_WIDTH . '" w:h="' . self::PAGE_HEIGHT . '"/>'
            . '<w:pgMar w:top="' . self::MARGIN_Y . '" w:right="' . self::MARGIN_X . '" w:bottom="' . self::MARGIN_Y . '" w:left="' . self::MARGIN_X . '" w:header="0" w:footer="0" w:gutter="0"/></w:sectPr>'
            . '</w:body></w:document>';
    }

    /**
     * One form. Word merges tables that touch, so a thin empty paragraph separates them.
     */
    private function page(array $rows, CarbonInterface $date): string
    {
        return $this->logos()
            . $this->title($date)
            . $this->associationBox()
            . $this->gap()
            . $this->disciplines()
            . $this->gap()
            . $this->trainees($rows)
            . $this->gap()
            . $this->declaration(count($rows))
            . $this->gap()
            . $this->signatures()
            . $this->federationAddress();
    }

    // ---- Sections of the form ---------------------------------------------------------

    private function logos(): string
    {
        return $this->table([2600, 5506, 2600], $this->row([
            $this->cell(2600, $this->paragraph($this->image('insurer', 1.7), 'left')),
            $this->cell(5506, $this->paragraph($this->image('federation', 2.0), 'center')),
            $this->cell(2600, $this->paragraph($this->run('ASSURANCES', 22, true, '595959'), 'right')
                . $this->paragraph($this->image('insurer', 1.2), 'right')),
        ]));
    }

    private function title(CarbonInterface $date): string
    {
        // a sport season runs from September to August
        $start = $date->month >= 9 ? $date->year : $date->year - 1;
        $border = '<w:pBdr><w:top w:val="single" w:sz="8" w:space="3" w:color="' . self::GREEN . '"/>'
            . '<w:bottom w:val="single" w:sz="8" w:space="3" w:color="' . self::GREEN . '"/></w:pBdr>';

        return $this->paragraph(
            $this->run("SAISON SPORTIVE {$start}–" . ($start + 1) . ' | N° Bordereau : ….', 30, true),
            'center',
            before: 60,
            after: 100,
            properties: $border,
        );
    }

    private function associationBox(): string
    {
        $form = config('insurance.bordereau');
        $line = fn(string $label, string $value, string $more = ''): string => $this->paragraph(
            $this->run("| {$label} : ", 17, true) . $this->run($value, 22, true) . $more,
            after: 40,
        );

        return $this->table([2300, 6106, 2300], $this->row([
            $this->cell(2300, $this->paragraph($this->run($form['federation'], 22, true), 'center')
                . $this->paragraph($this->image('karate', 1.2), 'center'), ['top', 'left', 'bottom']),
            $this->cell(6106, $line('ASSOCIATION', $form['association'])
                . $line('ADRESSE', $form['address'])
                . $line('LIGUE', $form['league'], $this->run('   Tél : ', 17, true) . $this->run($form['phone'], 22)), ['top', 'bottom']),
            $this->cell(2300, $this->paragraph($this->run('Contrat N° ' . $form['contract'], 21, true), 'center'), ['top', 'right', 'bottom'], align: 'bottom'),
        ]));
    }

    private function disciplines(): string
    {
        $all = ['top', 'left', 'bottom', 'right'];
        $type = $this->paragraph($this->run('TYPE A', 28, true), 'center') . $this->paragraph($this->run('☐', 36), 'center');

        $cells = [$this->cell(2000, $type, $all)];
        foreach (self::DISCIPLINES as $index => $names) {
            $width = $index === 0 ? 2236 : 2235;
            $cells[] = $this->cell($width, implode('', array_map(
                fn(string $name) => $this->paragraph($this->run('☐  ', 22) . $this->run($name, 17), after: 30, indent: 300),
                $names,
            )), $all);
        }
        $cells[] = $this->cell(2000, $type, $all);

        return $this->table([2000, 2236, 2235, 2235, 2000], $this->row($cells));
    }

    private function trainees(array $rows): string
    {
        $all = ['top', 'left', 'bottom', 'right'];
        $cells = fn(array $texts, bool $header = false): array => array_map(
            fn(string $text, int $index) => $this->cell(
                self::COLUMN_WIDTHS[$index],
                $this->paragraph($this->run($text, $header ? 19 : 18, $header || $index === 0), $header || $index === 0 ? 'center' : 'left'),
                $all,
                self::BLACK,
            ),
            $texts,
            array_keys($texts),
        );

        $xml = $this->row($cells(self::HEADERS, header: true), 300);

        // every line of the paper form is printed, the ones without a trainee stay empty
        for ($line = 0; $line < config('insurance.bordereau.rows_per_page'); $line++) {
            $xml .= $this->row($cells([(string) ($line + 1), ...($rows[$line] ?? ['', '', '', '', '', ''])]), 290);
        }

        return $this->table(self::COLUMN_WIDTHS, $xml);
    }

    private function declaration(int $count): string
    {
        $form = config('insurance.bordereau');
        $all = ['top', 'left', 'bottom', 'right'];

        return $this->table([5606, 5100], $this->row([
            $this->cell(5606, $this->paragraph($this->run('* ', 20) . $this->run($form['bank'], 20, true) . $this->run(' : ' . $form['bank_account'], 20), before: 60, after: 60), $all),
            $this->cell(5100, $this->paragraph($this->run("Nombre d’Adhérents déclarés sur le Bordereau : {$count}", 20), before: 40, after: 60)
                . $this->paragraph($this->run('Le', 20, true) . $this->run(' : …. / … / …..          ', 20) . $this->run('Reçu', 20, true) . $this->run(' :', 20), after: 40), $all),
        ]));
    }

    private function signatures(): string
    {
        $all = ['top', 'left', 'bottom', 'right'];
        $widths = [3000, 853, 3000, 853, 3000];
        $cells = fn(callable $content): array => array_map(
            fn(int $width, int $index) => $index % 2 === 0 ? $this->cell($width, $content($index), $all) : $this->cell($width, $this->paragraph('')),
            $widths,
            array_keys($widths),
        );
        $labels = ['FÉDÉRATION', 2 => 'ASSOCIATION', 4 => 'ASSURANCES'];

        return $this->table($widths, $this->row($cells(fn(int $index) => $this->paragraph($this->run($labels[$index], 20, true), 'center', before: 40, after: 40)))
            // the boxes that are signed and stamped
            . $this->row($cells(fn() => $this->paragraph('')), 1500));
    }

    private function federationAddress(): string
    {
        return $this->paragraph($this->run('Fédération Royale Marocaine de Karaté et Disciplines Associées', 16, true, '1F3864'), 'center', before: 120)
            . $this->paragraph($this->run('Adresse : Siège des Fédérations -Complexe Sportif Prince Moulay Abdellah, Rabat - Tél : +212(0) 537-28-26-58 - Fax : +212(0) 537-29-20-29', 15, false, 'FF0000'), 'center')
            . $this->paragraph($this->run('Email : secretariatfrmk@yahoo.fr - Site Web : www.frmk.ma', 15, false, 'FF0000'), 'center');
    }

    // ---- WordprocessingML pieces (child elements are in the order the schema requires) --

    private function run(string $text, int $halfPoints, bool $bold = false, ?string $color = null): string
    {
        $text = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>'
            . ($bold ? '<w:b/>' : '')
            . ($color ? "<w:color w:val=\"{$color}\"/>" : '')
            . "<w:sz w:val=\"{$halfPoints}\"/><w:szCs w:val=\"{$halfPoints}\"/></w:rPr>"
            . "<w:t xml:space=\"preserve\">{$text}</w:t></w:r>";
    }

    private function paragraph(string $runs, string $align = 'left', int $before = 0, int $after = 0, int $indent = 0, string $properties = ''): string
    {
        return "<w:p><w:pPr>{$properties}<w:spacing w:before=\"{$before}\" w:after=\"{$after}\"/>"
            . ($indent ? "<w:ind w:left=\"{$indent}\"/>" : '')
            . "<w:jc w:val=\"{$align}\"/></w:pPr>{$runs}</w:p>";
    }

    /**
     * A paragraph as thin as Word allows.
     */
    private function gap(): string
    {
        return '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="100" w:lineRule="exact"/><w:rPr><w:sz w:val="2"/></w:rPr></w:pPr></w:p>';
    }

    private function pageBreak(): string
    {
        return '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="20" w:lineRule="exact"/></w:pPr><w:r><w:br w:type="page"/></w:r></w:p>';
    }

    private function table(array $widths, string $rows): string
    {
        $grid = implode('', array_map(fn(int $width) => "<w:gridCol w:w=\"{$width}\"/>", $widths));

        return '<w:tbl><w:tblPr><w:tblW w:w="' . array_sum($widths) . '" w:type="dxa"/><w:tblLayout w:type="fixed"/>'
            . '<w:tblCellMar><w:left w:w="80" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tblCellMar></w:tblPr>'
            . "<w:tblGrid>{$grid}</w:tblGrid>{$rows}</w:tbl>";
    }

    private function row(array $cells, ?int $minHeight = null): string
    {
        return '<w:tr>' . ($minHeight ? "<w:trPr><w:trHeight w:val=\"{$minHeight}\" w:hRule=\"atLeast\"/></w:trPr>" : '') . implode('', $cells) . '</w:tr>';
    }

    /**
     * @param  list<string>  $borders  the sides that get a line: top, left, bottom, right
     */
    private function cell(int $width, string $paragraphs, array $borders = [], string $color = self::GREEN, string $align = 'center'): string
    {
        $lines = '';
        foreach (['top', 'left', 'bottom', 'right'] as $side) {
            $lines .= in_array($side, $borders, true)
                ? "<w:{$side} w:val=\"single\" w:sz=\"8\" w:space=\"0\" w:color=\"{$color}\"/>"
                : "<w:{$side} w:val=\"nil\"/>";
        }

        return "<w:tc><w:tcPr><w:tcW w:w=\"{$width}\" w:type=\"dxa\"/><w:tcBorders>{$lines}</w:tcBorders><w:vAlign w:val=\"{$align}\"/></w:tcPr>{$paragraphs}</w:tc>";
    }

    /**
     * A square picture placed in the text line, or nothing when its file is missing.
     */
    private function image(string $key, float $cm): string
    {
        if (! isset($this->images[$key])) {
            return '';
        }

        $id = ++$this->drawings;
        $size = (int) round($cm * self::EMU_PER_CM);
        $extent = "cx=\"{$size}\" cy=\"{$size}\"";

        return '<w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            . "<wp:extent {$extent}/><wp:docPr id=\"{$id}\" name=\"Image {$id}\"/>"
            . '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic>'
            . "<pic:nvPicPr><pic:cNvPr id=\"{$id}\" name=\"{$key}.png\"/><pic:cNvPicPr/></pic:nvPicPr>"
            . "<pic:blipFill><a:blip r:embed=\"{$this->images[$key]}\"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>"
            . "<pic:spPr><a:xfrm><a:off x=\"0\" y=\"0\"/><a:ext {$extent}/></a:xfrm><a:prstGeom prst=\"rect\"><a:avLst/></a:prstGeom></pic:spPr>"
            . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
    }
}
