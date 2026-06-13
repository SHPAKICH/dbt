<?php

namespace app\controllers;

use app\models\PushSubscription;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

/**
 * Подписка на push-уведомления (PWA + мобильный браузер).
 */
class PushController extends Controller
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
     * Сохранить подписку на push (вызывается из JS после разрешения пользователя).
     */
    public function actionSubscribe()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $endpoint = Yii::$app->request->post('endpoint');
        $p256dh = Yii::$app->request->post('p256dh');
        $auth = Yii::$app->request->post('auth');

        if (empty($endpoint) || empty($p256dh) || empty($auth)) {
            return ['success' => false, 'error' => 'Не переданы данные подписки.'];
        }

        $userId = (int)Yii::$app->user->id;
        $userAgent = Yii::$app->request->userAgent;

        $existing = PushSubscription::findOne([
            'user_id' => $userId,
            'endpoint' => $endpoint,
        ]);

        if ($existing) {
            $existing->p256dh = $p256dh;
            $existing->auth = $auth;
            $existing->user_agent = $userAgent;
            $existing->save(false);
            return ['success' => true];
        }

        $sub = new PushSubscription([
            'user_id' => $userId,
            'endpoint' => $endpoint,
            'p256dh' => $p256dh,
            'auth' => $auth,
            'user_agent' => $userAgent,
        ]);

        if ($sub->save()) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => implode(', ', $sub->getFirstErrors())];
    }

    /**
     * Отписаться (удалить подписку по endpoint).
     */
    public function actionUnsubscribe()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $endpoint = Yii::$app->request->post('endpoint');
        if (empty($endpoint)) {
            return ['success' => false];
        }
        $deleted = PushSubscription::deleteAll([
            'user_id' => (int)Yii::$app->user->id,
            'endpoint' => $endpoint,
        ]);
        return ['success' => (bool)$deleted];
    }
}
