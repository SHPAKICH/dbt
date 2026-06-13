<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

/**
 * Документация и отчёты для ролей выше тимейкера.
 * Содержимое (отчёты по сменам, выручка и т.д.) будет дополняться позже.
 */
class DocumentationController extends Controller
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
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            if (!$user) {
                                return false;
                            }
                            return $user->isAdmin()
                                || in_array($user->position, ['manager', 'location_manager', 'senior_teamaker'], true);
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Вкладка «Документация» — раздел для ответственных лиц.
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}
