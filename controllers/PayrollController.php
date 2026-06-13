<?php

namespace app\controllers;

use app\models\Location;
use app\services\PayrollExportService;
use app\services\PayrollService;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class PayrollController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex($location_id = null, $period = 'month', $date = null)
    {
        $currentUser = Yii::$app->user->identity;

        $locations = [];
        $isAdmin = $currentUser && $currentUser->isAdmin();
        $pos = $currentUser ? $currentUser->position : null;

        // Локации доступны только администратору, управляющим и менеджерам точек
        if ($currentUser) {
            $locationsQuery = Location::find()->where(['is_active' => 1]);

            if ($isAdmin) {
                // все точки
            } elseif ($pos === 'manager') {
                // Управляющий — только свои точки из manager_locations
                $managerLocationIds = (new \yii\db\Query())
                    ->from('manager_locations')
                    ->where(['manager_id' => $currentUser->id])
                    ->select('location_id')
                    ->column();
                if ($managerLocationIds) {
                    $locationsQuery->andWhere(['id' => $managerLocationIds]);
                } else {
                    $locationsQuery->andWhere('0=1');
                }
            } elseif ($pos === 'location_manager' && $currentUser->location_id) {
                // Менеджер точки — только своя точка
                $locationsQuery->andWhere(['id' => $currentUser->location_id]);
            } else {
                // Остальные (сотрудники) — выбор точки не нужен
                $locationsQuery->andWhere('0=1');
            }

            $locations = $locationsQuery->orderBy(['name' => SORT_ASC])->all();
        }

        if ($location_id === null && $locations) {
            $location_id = $locations[0]->id;
        }

        if ($date === null) {
            $date = date('Y-m-d');
        }

        if ($period === 'week') {
            $ts = strtotime($date);
            $w = (int)date('N', $ts);
            $from = date('Y-m-d', strtotime('-' . ($w - 1) . ' days', $ts));
            $to = date('Y-m-d', strtotime('+' . (7 - $w) . ' days', $ts));
        } else {
            $from = date('Y-m-01', strtotime($date));
            $to = date('Y-m-t', strtotime($date));
        }

        /** @var PayrollService $service */
        $service = Yii::$container->get(PayrollService::class);

        // Стажёр, тимейкер, старший тимейкер — видят только свою зарплату
        if (!$isAdmin && in_array($pos, ['trainee', 'teamaker', 'senior_teamaker'], true)) {
            $result = $service->calculate($from, $to, null);
            $rows = array_values(array_filter($result['rows'], static function ($row) use ($currentUser) {
                return $row['user']->id === $currentUser->id;
            }));
            $totalHours = 0;
            $totalAmount = 0;
            foreach ($rows as $row) {
                $totalHours += $row['hours'];
                $totalAmount += $row['amount'];
            }
            $result = [
                'rows' => $rows,
                'totalHours' => $totalHours,
                'totalAmount' => $totalAmount,
            ];
            // Локации и фильтры не нужны
            $locations = [];
            $location_id = null;
        } else {
            // Админ, управляющие и менеджеры точек — по выбранной точке
            $result = $service->calculate($from, $to, $location_id ? (int)$location_id : null);
        }

        return $this->render('index', [
            'locations' => $locations,
            'locationId' => (int)$location_id,
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'result' => $result,
        ]);
    }

    public function actionExport($location_id = null, $period = 'month', $date = null): Response
    {
        $currentUser = Yii::$app->user->identity;
        $isAdmin = $currentUser && $currentUser->isAdmin();
        $pos = $currentUser ? $currentUser->position : null;

        if ($date === null) {
            $date = date('Y-m-d');
        }

        if ($period === 'week') {
            $ts = strtotime($date);
            $w = (int)date('N', $ts);
            $from = date('Y-m-d', strtotime('-' . ($w - 1) . ' days', $ts));
            $to = date('Y-m-d', strtotime('+' . (7 - $w) . ' days', $ts));
        } else {
            $from = date('Y-m-01', strtotime($date));
            $to = date('Y-m-t', strtotime($date));
        }

        /** @var PayrollService $service */
        $service = Yii::$container->get(PayrollService::class);

        // Стажёр, тимейкер, старший тимейкер — экспорт только своей зарплаты
        if (!$isAdmin && in_array($pos, ['trainee', 'teamaker', 'senior_teamaker'], true)) {
            $result = $service->calculate($from, $to, null);
            $rows = array_values(array_filter($result['rows'], static function ($row) use ($currentUser) {
                return $row['user']->id === $currentUser->id;
            }));
            $totalHours = 0;
            $totalAmount = 0;
            foreach ($rows as $row) {
                $totalHours += $row['hours'];
                $totalAmount += $row['amount'];
            }
            $result = [
                'rows' => $rows,
                'totalHours' => $totalHours,
                'totalAmount' => $totalAmount,
            ];

            /** @var PayrollExportService $exporter */
            $exporter = Yii::$container->get(PayrollExportService::class);

            return $exporter->export($result, $from, $to, null);
        }

        $location = null;
        if ($location_id) {
            $location = Location::findOne((int)$location_id);
        }

        $result = $service->calculate($from, $to, $location ? (int)$location->id : null);

        /** @var PayrollExportService $exporter */
        $exporter = Yii::$container->get(PayrollExportService::class);

        return $exporter->export($result, $from, $to, $location ? $location->name : null);
    }
}



