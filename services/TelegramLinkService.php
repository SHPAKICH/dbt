<?php

namespace app\services;

use Yii;
use app\models\User;

class TelegramLinkService
{
    public const CODE_TTL_SECONDS = 900;

    public function generateLinkCode(User $user): string
    {
        $code = strtoupper(Yii::$app->security->generateRandomString(6));
        $user->telegram_link_code = $code;
        $user->telegram_link_expires_at = date('Y-m-d H:i:s', time() + self::CODE_TTL_SECONDS);
        $user->save(false, ['telegram_link_code', 'telegram_link_expires_at']);
        return $code;
    }

    public function confirmLink(string $code, int $chatId): array
    {
        $code = strtoupper(trim($code));
        if ($code === '' || $chatId <= 0) {
            return ['success' => false, 'message' => 'Неверные данные'];
        }

        $user = User::findOne(['telegram_link_code' => $code]);
        if (!$user) {
            return ['success' => false, 'message' => 'Код не найден или уже использован'];
        }

        if (empty($user->telegram_link_expires_at) || strtotime($user->telegram_link_expires_at) < time()) {
            $user->clearTelegramLinkCode();
            $user->save(false, ['telegram_link_code', 'telegram_link_expires_at']);
            return ['success' => false, 'message' => 'Срок действия кода истёк. Получите новый в настройках DBT Hub'];
        }

        $existing = User::find()
            ->where(['telegram_chat_id' => $chatId])
            ->andWhere(['<>', 'id', $user->id])
            ->one();
        if ($existing) {
            return ['success' => false, 'message' => 'Этот Telegram уже привязан к другому аккаунту'];
        }

        $user->telegram_chat_id = $chatId;
        $user->clearTelegramLinkCode();
        if (!$user->save(false, ['telegram_chat_id', 'telegram_link_code', 'telegram_link_expires_at'])) {
            return ['success' => false, 'message' => 'Не удалось сохранить привязку'];
        }

        return ['success' => true, 'message' => 'Telegram успешно привязан', 'userName' => $user->getFullName()];
    }

    public function unlink(User $user): void
    {
        $user->telegram_chat_id = null;
        $user->clearTelegramLinkCode();
        $user->save(false, ['telegram_chat_id', 'telegram_link_code', 'telegram_link_expires_at']);
    }
}
