<?php

namespace app\modules\api\controllers;

use app\models\PushSubscription;
use Yii;

class PushController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'subscribe' => ['POST'],
            'unsubscribe' => ['POST'],
        ];
    }

    /**
     * POST /api/v1/push/subscribe
     * Body: { endpoint, p256dh, auth }
     */
    public function actionSubscribe(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $endpoint = (string)($body['endpoint'] ?? '');
        $p256dh = (string)($body['p256dh'] ?? '');
        $auth = (string)($body['auth'] ?? '');

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            return $this->error('Не переданы данные подписки.', [], 422);
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
            return $this->success([], 'Подписка обновлена');
        }

        $sub = new PushSubscription([
            'user_id' => $userId,
            'endpoint' => $endpoint,
            'p256dh' => $p256dh,
            'auth' => $auth,
            'user_agent' => $userAgent,
        ]);

        if ($sub->save()) {
            return $this->success([], 'Подписка создана');
        }

        return $this->error(
            implode(', ', $sub->getFirstErrors()) ?: 'Не удалось сохранить подписку.',
            $sub->errors,
            422
        );
    }

    /**
     * POST /api/v1/push/unsubscribe
     * Body: { endpoint }
     */
    public function actionUnsubscribe(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $endpoint = (string)($body['endpoint'] ?? '');

        if ($endpoint === '') {
            return $this->error('Не передан endpoint.', [], 422);
        }

        $deleted = PushSubscription::deleteAll([
            'user_id' => (int)Yii::$app->user->id,
            'endpoint' => $endpoint,
        ]);

        return $this->success(['deleted' => (int)$deleted]);
    }
}
