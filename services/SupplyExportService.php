<?php

namespace app\services;

use app\models\SupplyOrder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\base\Component;

class SupplyExportService extends Component
{
    private const CATEGORY_ORDER = [
        'Ингредиенты' => 1,
        'Витрина' => 2,
        'Оборудование и расходные материалы' => 3,
        'Можно приобрести со склада ДБТ' => 4,
    ];

    /**
     * Формирует XLSX-файл и возвращает путь к временному файлу.
     */
    public function exportToFile(SupplyOrder $order): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Заказ');

        $sheet->setCellValue('A1', 'Точка');
        $sheet->setCellValue('B1', $order->location ? $order->location->name : '');
        $sheet->setCellValue('A2', 'Дата');
        $sheet->setCellValue('B2', $order->created_at);
        $sheet->setCellValue('A3', 'Ответственный');
        $sheet->setCellValue('B3', $order->creator ? $order->creator->getFullName() : '');
        if ($order->comment) {
            $sheet->setCellValue('A4', 'Комментарий');
            $sheet->setCellValue('B4', $order->comment);
        }

        $sheet->getStyle('A1:A4')->getFont()->setBold(true);

        $itemsByCategory = [];
        foreach ($order->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }
            $cat = $product->category ?: 'Без категории';
            if (!isset($itemsByCategory[$cat])) {
                $itemsByCategory[$cat] = [];
            }
            $itemsByCategory[$cat][] = $item;
        }

        uksort($itemsByCategory, function ($a, $b) {
            $oa = self::CATEGORY_ORDER[$a] ?? 999;
            $ob = self::CATEGORY_ORDER[$b] ?? 999;
            return $oa <=> $ob;
        });

        $row = 6;
        $grandTotal = 0;
        $num = 1;

        foreach ($itemsByCategory as $categoryName => $items) {
            $sheet->setCellValue('A' . $row, $categoryName);
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A' . $row . ':G' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E2E8F0');
            $row++;

            $headerRow = $row;
            $headers = ['#', 'Наименование', 'Кол-во в упаковке', 'Фасовка', 'Цена за уп., руб.', 'Заказ', 'Итого, руб.'];
            foreach ($headers as $i => $header) {
                $col = chr(65 + $i);
                $sheet->setCellValue($col . $headerRow, $header);
            }
            $sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F1F5F9');
            $sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_THIN);
            $row++;

            foreach ($items as $item) {
                $product = $item->product;
                $price = $product->price_per_unit !== null ? (float) $product->price_per_unit : 0;
                $qty = (float) $item->quantity;
                $total = $price * $qty;
                $grandTotal += $total;

                $sheet->setCellValue('A' . $row, $num);
                $sheet->setCellValue('B' . $row, $product->name);
                $sheet->setCellValue('C' . $row, $product->package_quantity);
                $sheet->setCellValue('D' . $row, $product->package_description);
                $sheet->setCellValue('E' . $row, $price > 0 ? $price : '');
                $sheet->setCellValue('F' . $row, $qty);
                $sheet->setCellValue('G' . $row, $total > 0 ? $total : '');

                if ($price > 0) {
                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                if ($total > 0) {
                    $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_HAIR);

                $num++;
                $row++;
            }

            $row++;
        }

        $sheet->setCellValue('F' . $row, 'ИТОГО:');
        $sheet->setCellValue('G' . $row, $grandTotal);
        $sheet->getStyle('F' . $row . ':G' . $row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(42);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(26);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(16);

        $sheet->getStyle('A6:A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E6:E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F6:F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G6:G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'supply_') . '.xlsx';
        $writer->save($tmpFile);

        return $tmpFile;
    }
}
