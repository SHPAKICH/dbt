<?php

namespace app\controllers;

use app\models\Location;
use app\services\ScheduleService;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class ScheduleController extends Controller
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

    /**
     * Отображение графика по точке и периоду.
     */
    public function actionIndex($location_id = null, $period = 'week', $date = null)
    {
        $currentUser = Yii::$app->user->identity;
        $locationsQuery = Location::find()->where(['is_active' => 1]);

        // Для управляющего показываем только его точки
        if ($currentUser && $currentUser->position === 'manager' && !$currentUser->isAdmin()) {
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
        }

        $locations = $locationsQuery->orderBy(['name' => SORT_ASC])->all();

        if ($location_id === null && $locations) {
            $location_id = $locations[0]->id;
        }

        if ($date === null) {
            $date = date('Y-m-d');
        }

        // Вычисляем период
        if ($period === 'month') {
            $from = date('Y-m-01', strtotime($date));
            $to = date('Y-m-t', strtotime($date));
        } else {
            // неделя (понедельник-воскресенье)
            $ts = strtotime($date);
            $w = (int)date('N', $ts); // 1..7
            $from = date('Y-m-d', strtotime("-" . ($w - 1) . " days", $ts));
            $to = date('Y-m-d', strtotime('+' . (7 - $w) . ' days', $ts));
        }

        $grid = null;
        if ($location_id) {
            /** @var ScheduleService $service */
            $service = Yii::$container->get(ScheduleService::class);
            $grid = $service->getScheduleGrid((int)$location_id, $from, $to);
        }

        return $this->render('index', [
            'locations' => $locations,
            'locationId' => (int)$location_id,
            'period' => $period,
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'grid' => $grid,
        ]);
    }

    /**
     * AJAX-обновление ячейки графика.
     */
    public function actionUpdateCell()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId = (int)Yii::$app->request->post('user_id');
        $locationId = (int)Yii::$app->request->post('location_id');
        $date = (string)Yii::$app->request->post('date');
        $value = Yii::$app->request->post('value');

        /** @var ScheduleService $service */
        $service = Yii::$container->get(ScheduleService::class);

        try {
            $shift = $service->updateCell($userId, $locationId, $date, $value);

            // Пересчёт агрегатов для ответа
            $from = $date;
            $to = $date;
            $grid = $service->getScheduleGrid($locationId, $from, $to);

            if ($shift->is_day_off || !$shift->time_start || !$shift->time_end) {
                $cellText = '0';
            } else {
                $startHour = substr($shift->time_start, 0, 2);
                $startMin = substr($shift->time_start, 3, 2);
                $endHour = substr($shift->time_end, 0, 2);
                $endMin = substr($shift->time_end, 3, 2);

                $startText = (int)$startHour . ($startMin === '30' ? '.5' : '');
                $endText = (int)$endHour . ($endMin === '30' ? '.5' : '');

                $cellText = $startText . '-' . $endText;
            }

            return [
                'success' => true,
                'cell' => [
                    'text' => $cellText,
                    'hours' => $shift->hours,
                    'isNight' => (bool)$shift->is_night,
                    'isOvertime' => (bool)$shift->is_overtime,
                    'isDayOff' => (bool)$shift->is_day_off,
                ],
                'totals' => [
                    'userHours' => $grid['totalsByUser'][$userId] ?? 0,
                    'dateHours' => $grid['totalsByDate'][$date] ?? 0,
                ],
            ];
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Автогенерация графика по карте возможностей.
     * Принимает from и to (Y-m-d) — период с экрана; иначе period + date.
     */
    public function actionGenerate($location_id, $from = null, $to = null, $period = 'week', $date = null)
    {
        if ($from && $to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            // Явный период с экрана
        } else {
            if ($date === null) {
                $date = date('Y-m-d');
            }
            if ($period === 'month') {
                $from = date('Y-m-01', strtotime($date));
                $to = date('Y-m-t', strtotime($date));
            } else {
                $ts = strtotime($date);
                $w = (int)date('N', $ts);
                $from = date('Y-m-d', strtotime('-' . ($w - 1) . ' days', $ts));
                $to = date('Y-m-d', strtotime('+' . (7 - $w) . ' days', $ts));
            }
        }

        /** @var ScheduleService $service */
        $service = Yii::$container->get(ScheduleService::class);
        try {
            $count = $service->generateFromAvailability((int)$location_id, $from, $to);
            if ($count > 0) {
                Yii::$app->session->setFlash('success', "Создано смен по карте возможностей: {$count}.");
            } else {
                Yii::$app->session->setFlash('warning',
                    'Смены не созданы. Создаются только те дни, где в карте указан интервал работы (например 9–18). '
                    . 'Пустые ячейки и 0 — это выходной или отпуск, так и должно быть. '
                    . 'Проверьте: 1) Есть ли в карте возможностей хотя бы несколько дат с интервалом (9–18 и т.п.) на период ' . $from . '–' . $to . '? '
                    . '2) Заполняли карту те же сотрудники, которые привязаны к выбранной точке (каждый под своим логином).');
            }
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect([
            'index',
            'location_id' => $location_id,
            'period' => $period ?: 'week',
            'date' => ($date !== null && $date !== '') ? $date : $from,
        ]);
    }
}


