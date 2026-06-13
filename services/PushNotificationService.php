<?php

namespace app\services;

use app\models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;
use Yii;

class PushNotificationService
{
    /**
     * @return array{subscriptionsCount:int, usersCount:int}
     */
    public function getStats(): array
    {
        return [
            'subscriptionsCount' => (int)PushSubscription::find()->count(),
            'usersCount' => (int)PushSubscription::find()->select('user_id')->distinct()->count('user_id'),
        ];
    }

    /**
     * @param int $userId
     * @param array<string,mixed> $payload
     * @return array{queued:int,sent:int,failed:int,removed:int}
     */
    public function sendToUser(int $userId, array $payload): array
    {
        $subs = PushSubscription::find()->where(['user_id' => $userId])->all();
        return $this->sendBySubscriptions($subs, $payload);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{queued:int,sent:int,failed:int,removed:int}
     */
    public function sendToAll(array $payload): array
    {
        $subs = PushSubscription::find()->all();
        return $this->sendBySubscriptions($subs, $payload);
    }

    /**
     * @param PushSubscription[] $subscriptions
     * @param array<string,mixed> $payload
     * @return array{queued:int,sent:int,failed:int,removed:int}
     */
    private function sendBySubscriptions(array $subscriptions, array $payload): array
    {
        if (empty($subscriptions)) {
            return ['queued' => 0, 'sent' => 0, 'failed' => 0, 'removed' => 0];
        }

        $webPush = $this->createWebPush();
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($jsonPayload === false) {
            throw new RuntimeException('Не удалось сформировать payload push-уведомления.');
        }

        $queued = 0;
        $failed = 0;
        $removed = 0;
        $sent = 0;

        foreach ($subscriptions as $sub) {
            try {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => (string)$sub->endpoint,
                        // Browser may send classic base64; normalize to base64url for library compatibility.
                        'publicKey' => $this->normalizeBase64Url((string)$sub->p256dh),
                        'authToken' => $this->normalizeBase64Url((string)$sub->auth),
                    ]),
                    $jsonPayload
                );
                $queued++;
            } catch (\Throwable $e) {
                $failed++;
                Yii::warning('Push subscription skipped: ' . $e->getMessage(), __METHOD__);
                if (PushSubscription::deleteAll(['id' => (int)$sub->id]) > 0) {
                    $removed++;
                }
            }
        }

        if ($queued === 0) {
            return ['queued' => 0, 'sent' => 0, 'failed' => $failed, 'removed' => $removed];
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
                continue;
            }

            $failed++;
            if ($report->isSubscriptionExpired()) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                if (PushSubscription::deleteAll(['endpoint' => $endpoint]) > 0) {
                    $removed++;
                }
            }
        }

        return [
            'queued' => $queued,
            'sent' => $sent,
            'failed' => $failed,
            'removed' => $removed,
        ];
    }

    private function createWebPush(): WebPush
    {
        $publicKey = (string)($this->param('vapidPublicKey'));
        $privateKey = (string)($this->param('vapidPrivateKey'));
        $subject = (string)($this->param('vapidSubject'));

        if ($publicKey === '' || $privateKey === '') {
            throw new RuntimeException('VAPID ключи не настроены на сервере.');
        }
        if ($subject === '') {
            $subject = 'mailto:admin@example.com';
        }

        return new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);
    }

    /**
     * @param string $name
     * @return mixed
     */
    private function param(string $name)
    {
        return Yii::$app->params[$name] ?? null;
    }

    private function normalizeBase64Url(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return '';
        }
        $normalized = str_replace(['+', '/'], ['-', '_'], $normalized);
        return rtrim($normalized, '=');
    }
}
