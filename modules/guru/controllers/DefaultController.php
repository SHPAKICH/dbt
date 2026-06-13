<?php

namespace app\modules\guru\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\TrainingTest;
use app\models\TechCard;
use app\models\TrainingResult;

/**
 * Дашборд модуля Меню Гуру.
 */
class DefaultController extends Controller
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

    /**
     * Главная: обзор тестов, карточек, быстрые ссылки.
     */
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();

        $tests = TrainingTest::find()
            ->where(['is_active' => 1])
            ->orderBy(['title' => SORT_ASC])
            ->limit(8)
            ->all();

        $cardsCount = TechCard::find()->where(['is_active' => 1])->count();
        $myResults = [];
        if ($user) {
            $myResults = TrainingResult::find()
                ->where(['user_id' => $user->id])
                ->with('test')
                ->orderBy(['finished_at' => SORT_DESC])
                ->limit(5)
                ->all();
        }

        return $this->render('index', [
            'tests' => $tests,
            'cardsCount' => $cardsCount,
            'myResults' => $myResults,
            'isAdmin' => $isAdmin,
        ]);
    }
}
