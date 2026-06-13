<?php

namespace app\services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use yii\base\Component;
use yii\web\Response;

/**
 * Экспорт Дейли в XLSX для бухгалтерии.
 */
class DailyExportService extends Component
{
    /**
     * Экспорт данных в XLSX и отдать файл пользователю.
     *
     * @param \app\models\DailyReport[] $reports
     * @param string $locationName
     * @param string $yearMonth Y-m
     * @return Response
     */
    public function export(array $reports, string $locationName, string $yearMonth): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Дейли');

        $sheet->setCellValue('A1', 'Дейли');
        $sheet->setCellValue('A2', 'Точка: ' . $locationName);
        $sheet->setCellValue('A3', 'Месяц: ' . $yearMonth);

        $headers = [
            'A' => 'Дата', 'B' => 'День', 'C' => 'План на день', 'D' => 'ТО', 'E' => 'DELTA',
            'F' => 'БАР', 'G' => 'ДОСТАВКА', 'H' => 'САМОВЫВОЗ', 'I' => 'БОНУСЫ',
            'J' => 'Чеки БАР', 'K' => 'Чеки ДОСТ', 'L' => 'Чеки САМ',
            'M' => 'Заказов', 'N' => 'Ср. чек БАР', 'O' => 'Ср. чек ДОСТ', 'P' => 'Ср. чек САМ',
            'Q' => 'Часы', 'R' => 'Произв. зак', 'S' => 'Произв. ₽', 'T' => 'Менеджер',
        ];

        $row = 5;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $row, $label);
        }
        $row++;

        foreach ($reports as $r) {
            $sheet->setCellValue('A' . $row, $r->report_date ? date('d.m.Y', strtotime($r->report_date)) : '');
            $sheet->setCellValue('B' . $row, $r->getDayOfWeekShort());
            $sheet->setCellValue('C' . $row, $r->plan_daily ?? '');
            $sheet->setCellValue('D' . $row, $r->to_revenue ?? '');
            $sheet->setCellValue('E' . $row, $r->delta_plan ?? '');
            $sheet->setCellValue('F' . $row, $r->bar ?? '');
            $sheet->setCellValue('G' . $row, $r->delivery ?? '');
            $sheet->setCellValue('H' . $row, $r->self_pickup ?? '');
            $sheet->setCellValue('I' . $row, $r->bonuses ?? '');
            $sheet->setCellValue('J' . $row, $r->checks_bar ?? '');
            $sheet->setCellValue('K' . $row, $r->checks_delivery ?? '');
            $sheet->setCellValue('L' . $row, $r->checks_self_pickup ?? '');
            $sheet->setCellValue('M' . $row, $r->orders_count ?? '');
            $sheet->setCellValue('N' . $row, $r->avg_check_bar ?? '');
            $sheet->setCellValue('O' . $row, $r->avg_check_delivery ?? '');
            $sheet->setCellValue('P' . $row, $r->avg_check_self_pickup ?? '');
            $sheet->setCellValue('Q' . $row, $r->worker_hours ?? '');
            $sheet->setCellValue('R' . $row, $r->productivity_orders ?? '');
            $sheet->setCellValue('S' . $row, $r->productivity_money ?? '');
            $sheet->setCellValue('T' . $row, $r->manager ? $r->manager->getFullName() : '');
            $row++;
        }

        foreach (range('A', 'T') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'daily_' . $locationName . '_' . $yearMonth . '.xlsx';
        $filename = preg_replace('/[^a-z0-9_\-\.]/i', '_', $filename);

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        $response->stream = static function () use ($writer) {
            $writer->save('php://output');
        };

        return $response;
    }
}
