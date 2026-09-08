<?php

namespace App\Services\Report;

use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExcelExporter
{
    private const GREEN = '1D6A4A';

    private const DARK = '163D2D';

    private const BORDER = 'E5E7EB';

    public function make(
        User $user,
        string $startDate,
        string $endDate,
        object $summary,
        Collection $transactions,
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Dompetra')
            ->setTitle("Laporan Keuangan {$startDate} - {$endDate}")
            ->setSubject('Laporan dan backup transaksi')
            ->setDescription('Workbook laporan Dompetra yang dapat diedit dan dihitung ulang.');

        $this->buildTransactionSheet($spreadsheet, $startDate, $endDate, $transactions);
        $this->buildSummarySheet($spreadsheet, $user, $startDate, $endDate, $summary, $transactions->count());

        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getCalculationEngine()->clearCalculationCache();

        return $spreadsheet;
    }

    private function buildSummarySheet(
        Spreadsheet $spreadsheet,
        User $user,
        string $startDate,
        string $endDate,
        object $summary,
        int $transactionCount,
    ): void {
        $sheet = $spreadsheet->getSheet(0);
        $sheet->setTitle('Ringkasan');
        $sheet->setShowGridlines(false);
        $sheet->freezePane('A5');

        $sheet->mergeCells('A1:H2');
        $sheet->setCellValue('A1', 'LAPORAN KEUANGAN DOMPETRA');
        $sheet->getStyle('A1:H2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::DARK]],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 19],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue('A3', "Periode {$startDate} sampai {$endDate}  |  {$user->name}");
        $sheet->getStyle('A3:H3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN]],
            'font' => ['color' => ['rgb' => 'D1FAE5'], 'size' => 10],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $this->summaryCard($sheet, 'A5:B5', 'A6:B7', 'TOTAL PEMASUKAN', $summary->income, 'DCFCE7', '166534');
        $this->summaryCard($sheet, 'D5:E5', 'D6:E7', 'TOTAL PENGELUARAN', $summary->expense, 'FEE2E2', '991B1B');
        $this->summaryCard($sheet, 'G5:H5', 'G6:H7', 'SELISIH PERIODE', $summary->balance, 'DBEAFE', '1E40AF');

        $sheet->mergeCells('A10:H10');
        $sheet->setCellValue('A10', 'WORKBOOK YANG DAPAT DIKELOLA');
        $sheet->getStyle('A10:H10')->applyFromArray($this->sectionStyle());
        $sheet->mergeCells('A11:H13');
        $sheet->setCellValue(
            'A11',
            "Sheet Transaksi berisi {$transactionCount} baris data dan dapat difilter, diurutkan, atau diedit. " .
            'Ringkasan dinamis di bawah dihitung dengan formula, sehingga diperbarui oleh Excel ketika data diubah.'
        );
        $sheet->getStyle('A11:H13')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'font' => ['color' => ['rgb' => '475569'], 'size' => 10],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER]]],
        ]);

        $sheet->mergeCells('A15:H15');
        $sheet->setCellValue('A15', 'RINGKASAN DINAMIS');
        $sheet->getStyle('A15:H15')->applyFromArray($this->sectionStyle());
        $sheet->fromArray([
            ['Pemasukan', null, null, 'Jumlah transaksi', null],
            ['Pengeluaran', null, null, 'Periode mulai', null],
            ['Selisih', null, null, 'Periode akhir', null],
        ], null, 'A16');
        $sheet->setCellValue('B16', '=SUMIFS(\'Transaksi\'!$G$6:$G$10005,\'Transaksi\'!$F$6:$F$10005,"Pemasukan")');
        $sheet->setCellValue('B17', '=-SUMIFS(\'Transaksi\'!$G$6:$G$10005,\'Transaksi\'!$F$6:$F$10005,"Pengeluaran")');
        $sheet->setCellValue('B18', '=B16-B17');
        $sheet->setCellValue('E16', '=COUNTA(\'Transaksi\'!$A$6:$A$10005)');
        $sheet->setCellValue('E17', Date::PHPToExcel(new DateTimeImmutable($startDate)));
        $sheet->setCellValue('E18', Date::PHPToExcel(new DateTimeImmutable($endDate)));
        $sheet->getStyle('A16:E18')->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => self::BORDER]]],
            'font' => ['size' => 10],
        ]);
        $sheet->getStyle('A16:A18')->getFont()->setBold(true)->getColor()->setRGB('475569');
        $sheet->getStyle('D16:D18')->getFont()->setBold(true)->getColor()->setRGB('475569');
        $sheet->getStyle('B16:B18')->getNumberFormat()->setFormatCode('"Rp" #,##0;[Red]-"Rp" #,##0');
        $sheet->getStyle('E16')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E17:E18')->getNumberFormat()->setFormatCode('dd mmm yyyy');

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setWidth(15);
        }
        $sheet->getColumnDimension('C')->setWidth(3);
        $sheet->getColumnDimension('F')->setWidth(3);
        foreach ([1 => 25, 2 => 25, 3 => 24, 11 => 22, 12 => 22, 13 => 22] as $row => $height) {
            $sheet->getRowDimension($row)->setRowHeight($height);
        }
        $this->configurePage($sheet, true);
    }

    private function buildTransactionSheet(
        Spreadsheet $spreadsheet,
        string $startDate,
        string $endDate,
        Collection $transactions,
    ): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Transaksi');
        $sheet->setShowGridlines(false);
        $sheet->mergeCells('A1:G2');
        $sheet->setCellValue('A1', 'DETAIL TRANSAKSI');
        $sheet->getStyle('A1:G2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::DARK]],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 17],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', "Periode {$startDate} sampai {$endDate}");
        $sheet->getStyle('A3:G3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN]],
            'font' => ['color' => ['rgb' => 'D1FAE5'], 'size' => 10],
        ]);

        $sheet->fromArray(['Tanggal', 'Deskripsi', 'Detail', 'Kategori', 'Dompet', 'Jenis', 'Jumlah'], null, 'A5');
        $sheet->getStyle('A5:G5')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'font' => ['bold' => true, 'color' => ['rgb' => '334155'], 'size' => 10],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::GREEN]]],
        ]);

        $row = 6;
        foreach ($transactions as $transaction) {
            [$type, $category] = $this->transactionLabels($transaction);
            $sheet->setCellValue('A' . $row, Date::PHPToExcel($transaction->transaction_date));
            foreach ([
                'B' => $transaction->description ?: '—',
                'C' => $transaction->detail ?: '—',
                'D' => $category,
                'E' => $transaction->wallet?->name ?? '—',
                'F' => $type,
            ] as $column => $value) {
                $sheet->setCellValueExplicit($column . $row, $value, DataType::TYPE_STRING);
            }
            $sheet->setCellValue('G' . $row, (float) $transaction->amount);

            [$fill, $color] = match ($type) {
                'Pemasukan' => ['DCFCE7', '166534'],
                'Pengeluaran' => ['FEE2E2', '991B1B'],
                'Transfer' => ['DBEAFE', '1D4ED8'],
                default => ['EDE9FE', '6D28D9'],
            };
            $sheet->getStyle('F' . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
                'font' => ['bold' => true, 'color' => ['rgb' => $color], 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $row++;
        }

        $lastRow = max(6, $row - 1);
        $sheet->getStyle("A6:G{$lastRow}")->applyFromArray([
            'font' => ['color' => ['rgb' => '334155'], 'size' => 10],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => self::BORDER]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A6:A{$lastRow}")->getNumberFormat()->setFormatCode('dd mmm yyyy');
        $sheet->getStyle("G6:G{$lastRow}")->getNumberFormat()->setFormatCode('"Rp" #,##0;[Red]-"Rp" #,##0');
        $sheet->getStyle("G6:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("C6:C{$lastRow}")->getFont()->getColor()->setRGB('64748B');
        $sheet->getStyle("B6:C{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->setAutoFilter("A5:G{$lastRow}");
        $sheet->freezePane('A6');
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 5);

        foreach (['A' => 14, 'B' => 28, 'C' => 34, 'D' => 20, 'E' => 20, 'F' => 17, 'G' => 20] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        for ($currentRow = 6; $currentRow <= $lastRow; $currentRow++) {
            $sheet->getRowDimension($currentRow)->setRowHeight(28);
        }
        foreach ([1 => 23, 2 => 23, 3 => 22, 5 => 24] as $headerRow => $height) {
            $sheet->getRowDimension($headerRow)->setRowHeight($height);
        }
        $this->configurePage($sheet, false);
        $sheet->getHeaderFooter()
            ->setOddFooter('&LDompetra - Backup transaksi&C&P / &N&R' . $startDate . ' - ' . $endDate);
    }

    private function transactionLabels(object $transaction): array
    {
        $isTransfer = !is_null($transaction->transfer_group_id);
        $isAdjustment = (bool) $transaction->is_balance_adjustment;
        $isIncome = !$isTransfer && !$isAdjustment && $transaction->amount >= 0;
        $type = match (true) {
            $isAdjustment => 'Koreksi Saldo',
            $isTransfer => 'Transfer',
            $isIncome => 'Pemasukan',
            default => 'Pengeluaran',
        };
        $category = match (true) {
            $isAdjustment => 'Koreksi Saldo',
            $isTransfer => 'Transfer',
            default => $transaction->category?->name ?? 'Tanpa kategori',
        };

        return [$type, $category];
    }

    private function summaryCard(
        Worksheet $sheet,
        string $labelRange,
        string $valueRange,
        string $label,
        float $value,
        string $fill,
        string $color,
    ): void {
        $sheet->mergeCells($labelRange);
        $sheet->mergeCells($valueRange);
        $sheet->setCellValue(explode(':', $labelRange)[0], $label);
        $sheet->setCellValue(explode(':', $valueRange)[0], $value);
        $sheet->getStyle($labelRange)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
            'font' => ['bold' => true, 'color' => ['rgb' => $color], 'size' => 9],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle($valueRange)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
            'font' => ['bold' => true, 'color' => ['rgb' => $color], 'size' => 15],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'numberFormat' => ['formatCode' => '"Rp" #,##0;[Red]-"Rp" #,##0'],
        ]);
    }

    private function sectionStyle(): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GREEN]],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    private function configurePage(Worksheet $sheet, bool $singlePage): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight($singlePage ? 1 : 0);
        $sheet->getPageMargins()->setTop(0.4)->setBottom(0.45)->setLeft(0.35)->setRight(0.35);
    }
}
