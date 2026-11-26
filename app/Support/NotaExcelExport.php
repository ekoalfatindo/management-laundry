<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use RuntimeException;
use XMLWriter;
use ZipArchive;

final class NotaExcelExport
{
    private const SHEET_NAME = 'Laporan Nota';

    private const STYLE_TITLE = 1;

    private const STYLE_META = 2;

    private const STYLE_HEADER = 3;

    private const STYLE_TEXT = 4;

    private const STYLE_CENTER = 5;

    private const STYLE_CURRENCY = 6;

    private const STYLE_DATE = 7;

    private const STYLE_DECIMAL = 8;

    private const STYLE_INTEGER = 9;

    private const STYLE_TEXT_ZEBRA = 10;

    private const STYLE_CENTER_ZEBRA = 11;

    private const STYLE_CURRENCY_ZEBRA = 12;

    private const STYLE_DATE_ZEBRA = 13;

    private const STYLE_DECIMAL_ZEBRA = 14;

    private const STYLE_INTEGER_ZEBRA = 15;

    private const STYLE_TOTAL_LABEL = 16;

    private const STYLE_TOTAL_CURRENCY = 17;

    private const STYLE_TOTAL_DECIMAL = 18;

    /**
     * @var array<int, array{label: string, width: int, kind: string}>
     */
    private const COLUMNS = [
        ['label' => 'No Nota', 'width' => 16, 'kind' => 'text'],
        ['label' => 'Jenis', 'width' => 12, 'kind' => 'center'],
        ['label' => 'Nama Pelanggan', 'width' => 28, 'kind' => 'text'],
        ['label' => 'Layanan', 'width' => 24, 'kind' => 'text'],
        ['label' => 'Waktu', 'width' => 18, 'kind' => 'date'],
        ['label' => 'Berat (kg)', 'width' => 11, 'kind' => 'decimal'],
        ['label' => 'Harga per kg', 'width' => 15, 'kind' => 'currency'],
        ['label' => 'Biaya Layanan', 'width' => 15, 'kind' => 'currency'],
        ['label' => 'Total Harga', 'width' => 15, 'kind' => 'currency'],
        ['label' => 'Diskon (%)', 'width' => 11, 'kind' => 'integer'],
        ['label' => 'Jumlah', 'width' => 15, 'kind' => 'currency'],
        ['label' => 'Status', 'width' => 13, 'kind' => 'center'],
        ['label' => 'Pembayaran', 'width' => 13, 'kind' => 'center'],
    ];

    public function __construct(
        private readonly Builder $query,
        private readonly CarbonInterface $generatedAt,
        private readonly string $filterSummary,
        private readonly string $companyName,
    ) {}

    public function create(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nota-export-');

        if ($path === false) {
            throw new RuntimeException('Tidak dapat membuat berkas sementara untuk ekspor.');
        }

        $sheetPath = $this->writeWorksheet();

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($sheetPath);
            @unlink($path);

            throw new RuntimeException('Tidak dapat membuat berkas Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRelations());
        $zip->addFromString('docProps/core.xml', $this->coreProperties());
        $zip->addFromString('docProps/app.xml', $this->appProperties());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelations());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
        $zip->close();

        @unlink($sheetPath);

        return $path;
    }

    private function writeWorksheet(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nota-sheet-');

        if ($path === false) {
            throw new RuntimeException('Tidak dapat membuat lembar Excel.');
        }

        $writer = new XMLWriter;
        $writer->openURI($path);
        $writer->startDocument('1.0', 'UTF-8', 'yes');
        $writer->startElement('worksheet');
        $writer->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $writer->startElement('sheetPr');
        $writer->startElement('pageSetUpPr');
        $writer->writeAttribute('fitToPage', '1');
        $writer->endElement();
        $writer->endElement();

        $writer->startElement('dimension');

        $count = 0;
        $sumBerat = 0.0;
        $sumTotal = 0;
        $sumJumlah = 0;

        $this->query->chunk(500, function ($notas) use (&$count, &$sumBerat, &$sumTotal, &$sumJumlah): void {
            foreach ($notas as $nota) {
                $count++;
                $sumBerat += (float) ($nota->berat ?? 0);
                $sumTotal += (int) $nota->total_harga;
                $sumJumlah += (int) $nota->jumlah;
            }
        });

        $lastDataRow = 4 + $count;
        $totalRow = $lastDataRow + 2;

        $writer->writeAttribute('ref', 'A1:M'.$totalRow);
        $writer->endElement();

        $writer->startElement('sheetViews');
        $writer->startElement('sheetView');
        $writer->writeAttribute('showGridLines', '0');
        $writer->writeAttribute('tabSelected', '1');
        $writer->writeAttribute('workbookViewId', '0');

        $writer->startElement('pane');
        $writer->writeAttribute('ySplit', '4');
        $writer->writeAttribute('topLeftCell', 'A5');
        $writer->writeAttribute('activePane', 'bottomLeft');
        $writer->writeAttribute('state', 'frozen');
        $writer->endElement();

        $writer->startElement('selection');
        $writer->writeAttribute('pane', 'bottomLeft');
        $writer->writeAttribute('activeCell', 'A5');
        $writer->writeAttribute('sqref', 'A5');
        $writer->endElement();
        $writer->endElement();
        $writer->endElement();

        $writer->startElement('sheetFormatPr');
        $writer->writeAttribute('defaultRowHeight', '16');
        $writer->endElement();

        $writer->startElement('cols');

        foreach (self::COLUMNS as $index => $column) {
            $writer->startElement('col');
            $writer->writeAttribute('min', (string) ($index + 1));
            $writer->writeAttribute('max', (string) ($index + 1));
            $writer->writeAttribute('width', (string) $column['width']);
            $writer->writeAttribute('customWidth', '1');
            $writer->endElement();
        }

        $writer->endElement();

        $writer->startElement('sheetData');

        $this->writeRow($writer, 1, 24, [
            ['A', self::STYLE_TITLE, 'Laporan Nota Transaksi', 'string'],
        ]);

        $this->writeRow($writer, 2, 16, [
            ['A', self::STYLE_META, $this->metaLine($count), 'string'],
        ]);

        $writer->startElement('row');
        $writer->writeAttribute('r', '3');
        $writer->writeAttribute('ht', '8');
        $writer->writeAttribute('customHeight', '1');
        $writer->endElement();

        $headerCells = [];

        foreach (self::COLUMNS as $index => $column) {
            $headerCells[] = [$this->column($index), self::STYLE_HEADER, $column['label'], 'string'];
        }

        $this->writeRow($writer, 4, 30, $headerCells);

        $rowNumber = 4;
        $notaIndex = 0;

        $this->query->chunk(500, function ($notas) use ($writer, &$rowNumber, &$notaIndex): void {
            foreach ($notas as $nota) {
                $rowNumber++;
                $zebra = $notaIndex % 2 === 1;
                $notaIndex++;

                $layanan = $nota->jenis->isKiloan()
                    ? ($nota->kiloan?->paket ?? '-')
                    : ($nota->jenisLayanan?->jenis_cuci ?? '-');

                $this->writeRow($writer, $rowNumber, 18, [
                    ['A', $this->style($zebra, self::STYLE_TEXT, self::STYLE_TEXT_ZEBRA), $nota->no_nota, 'string'],
                    ['B', $this->style($zebra, self::STYLE_CENTER, self::STYLE_CENTER_ZEBRA), $nota->jenis->value, 'string'],
                    ['C', $this->style($zebra, self::STYLE_TEXT, self::STYLE_TEXT_ZEBRA), $nota->nama_pelanggan, 'string'],
                    ['D', $this->style($zebra, self::STYLE_TEXT, self::STYLE_TEXT_ZEBRA), $layanan, 'string'],
                    ['E', $this->style($zebra, self::STYLE_DATE, self::STYLE_DATE_ZEBRA), $nota->waktu, 'date'],
                    ['F', $this->style($zebra, self::STYLE_DECIMAL, self::STYLE_DECIMAL_ZEBRA), $nota->berat, 'decimal'],
                    ['G', $this->style($zebra, self::STYLE_CURRENCY, self::STYLE_CURRENCY_ZEBRA), $nota->harga_per_kg, 'currency'],
                    ['H', $this->style($zebra, self::STYLE_CURRENCY, self::STYLE_CURRENCY_ZEBRA), $nota->biaya_layanan, 'currency'],
                    ['I', $this->style($zebra, self::STYLE_CURRENCY, self::STYLE_CURRENCY_ZEBRA), $nota->total_harga, 'currency'],
                    ['J', $this->style($zebra, self::STYLE_INTEGER, self::STYLE_INTEGER_ZEBRA), $nota->diskon, 'integer'],
                    ['K', $this->style($zebra, self::STYLE_CURRENCY, self::STYLE_CURRENCY_ZEBRA), $nota->jumlah, 'currency'],
                    ['L', $this->style($zebra, self::STYLE_CENTER, self::STYLE_CENTER_ZEBRA), $nota->status->value, 'string'],
                    ['M', $this->style($zebra, self::STYLE_CENTER, self::STYLE_CENTER_ZEBRA), $nota->status_pembayaran->value, 'string'],
                ]);
            }
        });

        $writer->startElement('row');
        $writer->writeAttribute('r', (string) ($lastDataRow + 1));
        $writer->writeAttribute('ht', '8');
        $writer->writeAttribute('customHeight', '1');
        $writer->endElement();

        $this->writeRow($writer, $totalRow, 20, [
            ['A', self::STYLE_TOTAL_LABEL, null, 'empty'],
            ['B', self::STYLE_TOTAL_LABEL, null, 'empty'],
            ['C', self::STYLE_TOTAL_LABEL, null, 'empty'],
            ['D', self::STYLE_TOTAL_LABEL, 'TOTAL ('.$count.' nota)', 'string'],
            ['E', self::STYLE_TOTAL_LABEL, null, 'empty'],
            ['F', self::STYLE_TOTAL_DECIMAL, round($sumBerat, 2), 'decimal'],
            ['G', self::STYLE_TOTAL_LABEL, null, 'empty'],
            ['H', self::STYLE_TOTAL_LABEL, null, 'empty'],
            ['I', self::STYLE_TOTAL_CURRENCY, $sumTotal, 'currency'],
            ['J', self::STYLE_TOTAL_LABEL, null, 'empty'],
            ['K', self::STYLE_TOTAL_CURRENCY, $sumJumlah, 'currency'],
            ['L', self::STYLE_TOTAL_LABEL, null, 'empty'],
            ['M', self::STYLE_TOTAL_LABEL, null, 'empty'],
        ]);

        $writer->endElement();

        $writer->startElement('autoFilter');
        $writer->writeAttribute('ref', 'A4:M'.$lastDataRow);
        $writer->endElement();

        $writer->startElement('mergeCells');
        $writer->writeAttribute('count', '2');
        $writer->startElement('mergeCell');
        $writer->writeAttribute('ref', 'A1:M1');
        $writer->endElement();
        $writer->startElement('mergeCell');
        $writer->writeAttribute('ref', 'A2:M2');
        $writer->endElement();
        $writer->endElement();

        $writer->startElement('pageMargins');
        $writer->writeAttribute('left', '0.4');
        $writer->writeAttribute('right', '0.4');
        $writer->writeAttribute('top', '0.6');
        $writer->writeAttribute('bottom', '0.6');
        $writer->writeAttribute('header', '0.3');
        $writer->writeAttribute('footer', '0.3');
        $writer->endElement();

        $writer->startElement('pageSetup');
        $writer->writeAttribute('orientation', 'landscape');
        $writer->writeAttribute('paperSize', '9');
        $writer->writeAttribute('fitToWidth', '1');
        $writer->writeAttribute('fitToHeight', '0');
        $writer->endElement();

        $writer->startElement('headerFooter');
        $writer->writeAttribute('alignWithMargins', '1');
        $writer->startElement('oddFooter');
        $writer->text('&L'.$this->clean($this->companyName).'&RHalaman &P dari &N');
        $writer->endElement();
        $writer->endElement();

        $writer->endElement();
        $writer->endDocument();
        $writer->flush();

        return $path;
    }

    /**
     * @param  array<int, array{0: string, 1: int, 2: mixed, 3: string}>  $cells
     */
    private function writeRow(XMLWriter $writer, int $rowNumber, int $height, array $cells): void
    {
        $writer->startElement('row');
        $writer->writeAttribute('r', (string) $rowNumber);
        $writer->writeAttribute('ht', (string) $height);
        $writer->writeAttribute('customHeight', '1');

        foreach ($cells as [$column, $style, $value, $type]) {
            $reference = $column.$rowNumber;

            if ($type === 'empty' || $value === null || $value === '') {
                $writer->startElement('c');
                $writer->writeAttribute('r', $reference);
                $writer->writeAttribute('s', (string) $style);
                $writer->endElement();

                continue;
            }

            if ($type === 'string') {
                $writer->startElement('c');
                $writer->writeAttribute('r', $reference);
                $writer->writeAttribute('s', (string) $style);
                $writer->writeAttribute('t', 'inlineStr');
                $writer->startElement('is');
                $writer->startElement('t');
                $writer->writeAttribute('xml:space', 'preserve');
                $writer->text($this->clean((string) $value));
                $writer->endElement();
                $writer->endElement();
                $writer->endElement();

                continue;
            }

            $writer->startElement('c');
            $writer->writeAttribute('r', $reference);
            $writer->writeAttribute('s', (string) $style);
            $writer->startElement('v');
            $writer->text(match ($type) {
                'date' => $this->dateSerial($value),
                'decimal' => $this->decimal($value),
                'currency', 'integer' => (string) (int) $value,
                default => (string) $value,
            });
            $writer->endElement();
            $writer->endElement();
        }

        $writer->endElement();
    }

    private function style(bool $zebra, int $normal, int $striped): int
    {
        return $zebra ? $striped : $normal;
    }

    private function metaLine(int $count): string
    {
        return $this->clean($this->companyName)
            .' · Dicetak '.$this->generatedAt->format('d/m/Y H:i')
            .' · '.$count.' nota · '.$this->filterSummary;
    }

    private function dateSerial(mixed $value): string
    {
        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        $days = Carbon::create(1899, 12, 30)
            ->diffInDays($date->copy()->startOfDay());

        $fraction = ((int) $date->format('H') * 3600
            + (int) $date->format('i') * 60
            + (int) $date->format('s')) / 86400;

        return rtrim(rtrim(sprintf('%.10F', $days + $fraction), '0'), '.');
    }

    private function decimal(mixed $value): string
    {
        return rtrim(rtrim(sprintf('%.2F', (float) $value), '0'), '.') ?: '0';
    }

    private function clean(string $value): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';
    }

    private function column(int $index): string
    {
        $letters = '';
        $index++;

        while ($index > 0) {
            $letters = chr(65 + ($index - 1) % 26).$letters;
            $index = intdiv($index - 1, 26);
        }

        return $letters;
    }

    private function contentTypes(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML;
    }

    private function rootRelations(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;
    }

    private function workbook(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<bookViews><workbookView activeTab="0"/></bookViews>
<sheets><sheet name="Laporan Nota" sheetId="1" r:id="rId1"/></sheets>
</workbook>
XML;
    }

    private function workbookRelations(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function coreProperties(): string
    {
        $timestamp = $this->generatedAt->copy()->utc()->format('Y-m-d\TH:i:s\Z');
        $company = $this->clean($this->companyName);

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:creator>{$company}</dc:creator>
<cp:lastModifiedBy>{$company}</cp:lastModifiedBy>
<dcterms:created xsi:type="dcterms:W3CDTF">{$timestamp}</dcterms:created>
<dcterms:modified xsi:type="dcterms:W3CDTF">{$timestamp}</dcterms:modified>
</cp:coreProperties>
XML;
    }

    private function appProperties(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
<Application>Management Laundry</Application>
</Properties>
XML;
    }

    private function styles(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="3">
<numFmt numFmtId="164" formatCode="&quot;Rp&quot; #,##0"/>
<numFmt numFmtId="165" formatCode="dd/mm/yyyy hh:mm"/>
<numFmt numFmtId="166" formatCode="0.00"/>
</numFmts>
<fonts count="5">
<font><sz val="11"/><color rgb="FF1E293B"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="14"/><color rgb="FF9A3412"/><name val="Calibri"/><family val="2"/></font>
<font><i/><sz val="9"/><color rgb="FF64748B"/><name val="Calibri"/><family val="2"/></font>
<font><b/><sz val="11"/><color rgb="FF7C2D12"/><name val="Calibri"/><family val="2"/></font>
</fonts>
<fills count="4">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFEA580C"/><bgColor indexed="64"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFFFF7ED"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="3">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom><diagonal/></border>
<border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="double"><color rgb="FFEA580C"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom><diagonal/></border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="19">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="165" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="166" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
<xf numFmtId="0" fontId="4" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="164" fontId="4" fillId="0" borderId="2" xfId="0" applyFont="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
<xf numFmtId="166" fontId="4" fillId="0" borderId="2" xfId="0" applyFont="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
</cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML;
    }
}
