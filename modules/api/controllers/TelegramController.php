<?php

namespace app\modules\api\controllers;

use Yii;
use app\services\TelegramLinkService;

class TelegramController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'confirm-link' => ['POST'],
        ];
    }

    /**
     * POST /api/v1/telegram/confirm-link
     * Вызывается ботом при /link CODE
     */
    public function actionConfirmLink(): array
    {
        $secret = trim((string) (Yii::$app->params['telegramWebhookSecret'] ?? getenv('TELEGRAM_WEBHOOK_SECRET') ?: ''));
        $body = $this->getJsonBody();
        $provided = trim((string) ($body['secret'] ?? Yii::$app->request->headers->get('X-Telegram-Secret', '')));

        if ($secret === '' || !hash_equals($secret, $provided)) {
            return $this->error('Доступ запрещён', [], 403);
        }

        $code = (string) ($body['code'] ?? '');
        $chatId = (int) ($body['chatId'] ?? $body['chat_id'] ?? 0);

        $result = (new TelegramLinkService())->confirmLink($code, $chatId);
        if (!$result['success']) {
            return $this->error($result['message'], [], 422);
        }

        return $this->success(['userName' => $result['userName'] ?? ''], $result['message']);
    }

    private function getJsonBody(): array
    {
        $body = Yii::$app->request->getBodyParams();
        if (empty($body) && Yii::$app->request->getRawBody()) {
            $decoded = json_decode(Yii::$app->request->getRawBody(), true);
            $body = is_array($decoded) ? $decoded : [];
        }
        return $body;
    }
}
