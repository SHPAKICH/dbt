<?php

namespace app\controllers;

use app\models\Location;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

/**
 * Календарь сотрудников: день рождения, телефон, Telegram, стаж, дата аттестации.
 * Напоминание руководителю о подарке к ДР.
 */
class TeamCalendarController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    public function actionIndex($location_id = null)
    {
        $user = Yii::$app->user->identity;
        $locations = [];
        $locationId = null;

        if ($user->isAdmin()) {
            $locations = Location::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();
            $locationId = $location_id !== null ? (int)$location_id : ($locations ? (int)$locations[0]->id : null);
        } elseif ($user->position === 'manager') {
            $ids = (new \yii\db\Query())
                ->from('manager_locations')
                ->where(['manager_id' => $user->id])
                ->select('location_id')
                ->column();
            $locations = Location::find()->where(['id' => $ids, 'is_active' => 1])->orderBy(['name' => SORT_ASC])->all();
            $locationId = $location_id !== null ? (int)$location_id : ($locations ? (int)$locations[0]->id : null);
        } else {
            $locationId = (int)$user->location_id;
            if ($locationId) {
                $loc = Location::findOne($locationId);
                $locations = $loc ? [$loc] : [];
            }
        }

        $employees = [];
        if ($locationId) {
            $employees = User::find()
                ->where(['location_id' => $locationId, 'is_active' => 1])
                ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
                ->all();
        }

        $upcomingBirthdays = [];
        $now = new \DateTime();
        $next = (clone $now)->modify('+30 days');
        foreach ($employees as $emp) {
            if (empty($emp->birthday)) {
                continue;
            }
            try {
                $bday = new \DateTime($emp->birthday);
                $bdayThisYear = $bday->setDate((int)$now->format('Y'), (int)$bday->format('m'), (int)$bday->format('d'));
                if ($bdayThisYear >= $now && $bdayThisYear <= $next) {
                    $upcomingBirthdays[] = [
                        'user' => $emp,
                        'date' => $bdayThisYear->format('Y-m-d'),
                    ];
                    usort($upcomingBirthdays, function ($a, $b) {
                        return strcmp($a['date'], $b['date']);
                    });
                }
            } catch (\Throwable $e) {
                // skip invalid date
            }
        }

        return $this->render('index', [
            'locations' => $locations,
            'locationId' => $locationId,
            'employees' => $employees,
            'upcomingBirthdays' => $upcomingBirthdays,
            'isManager' => $user->isAdmin() || in_array($user->position, ['manager', 'location_manager'], true),
        ]);
    }
}
