<?php

namespace app\services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use yii\base\Component;
use yii\web\Response;

/**
 * Сервис экспорта расчёта ЗП в XLSX.
 */
class PayrollExportService extends Component
{
    /**
     * Экспортировать данные в XLSX и отправить файл пользователю.
     *
     * @param array $data результат PayrollService::calculate()
     * @param string $from
     * @param string $to
     * @param string|null $locationName
     * @return Response
     */
    public function export(array $data, string $from, string $to, ?string $locationName = null): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Зарплата');

        $sheet->setCellValue('A1', 'Период');
        $sheet->setCellValue('B1', $from . ' — ' . $to);
        if ($locationName !== null) {
            $sheet->setCellValue('A2', 'Точка');
            $sheet->setCellValue('B2', $locationName);
            $startRow = 4;
        } else {
            $startRow = 3;
        }

        $headers = ['Сотрудник', 'Должность', 'Точка', 'Часы', 'Ставка (₽/час)', 'Сумма (₽)'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $startRow, $header);
            $col++;
        }

        $row = $startRow + 1;
        foreach ($data['rows'] as $item) {
            $sheet->setCellValue('A' . $row, $item['user']->getFullName());
            $sheet->setCellValue('B' . $row, $item['positionLabel']);
            $sheet->setCellValue('C' . $row, $item['location'] ? $item['location']->name : '');
            $sheet->setCellValue('D' . $row, $item['hours']);
            $sheet->setCellValue('E' . $row, $item['rate']);
            $sheet->setCellValue('F' . $row, $item['amount']);
            $row++;
        }

        $sheet->setCellValue('C' . $row, 'Итого:');
        $sheet->setCellValue('D' . $row, $data['totalHours']);
        $sheet->setCellValue('F' . $row, $data['totalAmount']);

        foreach (range('A', 'F') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'payroll_' . $from . '_' . $to . '.xlsx';

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        $response->stream = function () use ($writer) {
            $writer->save('php://output');
        };

        return $response;
    }
}



