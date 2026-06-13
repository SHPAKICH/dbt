<?php

namespace app\services;

use Yii;
use app\models\User;

class PasswordResetService
{
    public const TOKEN_TTL_SECONDS = 3600;

    public function requestReset(User $user, string $channel = 'auto'): bool
    {
        $plainToken = Yii::$app->security->generateRandomString(32);
        $user->password_reset_token_hash = hash('sha256', $plainToken);
        $user->password_reset_expires_at = date('Y-m-d H:i:s', time() + self::TOKEN_TTL_SECONDS);
        if (!$user->save(false, ['password_reset_token_hash', 'password_reset_expires_at'])) {
            return false;
        }

        $resetUrl = $this->buildResetUrl($plainToken);
        $sent = false;

        if ($channel === 'telegram' || ($channel === 'auto' && $this->hasTelegramChannel($user))) {
            $sent = $this->sendViaTelegram($user, $resetUrl);
        }

        if (!$sent && ($channel === 'email' || $channel === 'auto')) {
            $sent = $this->sendViaEmail($user, $resetUrl);
        }

        if (!$sent) {
            $user->clearPasswordResetToken();
            $user->save(false, ['password_reset_token_hash', 'password_reset_expires_at']);
        }

        return $sent;
    }

    public function findUserByToken(string $plainToken): ?User
    {
        if ($plainToken === '') {
            return null;
        }
        $hash = hash('sha256', $plainToken);
        $user = User::findOne(['password_reset_token_hash' => $hash]);
        if (!$user || !$this->isTokenValid($user)) {
            return null;
        }
        return $user;
    }

    public function resetPassword(User $user, string $newPassword): bool
    {
        $user->setPassword($newPassword);
        $user->clearPasswordResetToken();
        $user->generateAuthKey();
        return $user->save(false, ['password_hash', 'password_reset_token_hash', 'password_reset_expires_at', 'auth_key']);
    }

    public function isTokenValid(User $user): bool
    {
        if (empty($user->password_reset_token_hash) || empty($user->password_reset_expires_at)) {
            return false;
        }
        return strtotime($user->password_reset_expires_at) >= time();
    }

    public function hasTelegramChannel(User $user): bool
    {
        return !empty($user->telegram_chat_id);
    }

    public function canSendEmail(User $user): bool
    {
        if (empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (preg_match('/@placeholder\.local$/i', $user->email)) {
            return false;
        }
        return true;
    }

    private function buildResetUrl(string $plainToken): string
    {
        $frontend = rtrim(Yii::$app->params['frontendUrl'] ?? 'http://localhost:5173', '/');
        return $frontend . '/reset-password?token=' . urlencode($plainToken);
    }

    private function sendViaEmail(User $user, string $resetUrl): bool
    {
        if (!$this->canSendEmail($user)) {
            return false;
        }

        try {
            return (bool) Yii::$app->mailer->compose(
                ['html' => 'password-reset-html', 'text' => 'password-reset-text'],
                ['user' => $user, 'resetUrl' => $resetUrl, 'ttlMinutes' => (int) (self::TOKEN_TTL_SECONDS / 60)]
            )
                ->setTo($user->email)
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setSubject('Сброс пароля — DBT Hub')
                ->send();
        } catch (\Throwable $e) {
            Yii::error('Password reset email failed: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    private function sendViaTelegram(User $user, string $resetUrl): bool
    {
        if (!$this->hasTelegramChannel($user)) {
            return false;
        }

        $name = $user->getFullName();
        $text = "🔐 *Сброс пароля DBT Hub*\n\n"
            . "Здравствуйте, {$name}!\n\n"
            . "Вы запросили сброс пароля. Перейдите по ссылке (действует 1 час):\n"
            . $resetUrl . "\n\n"
            . "Если вы не запрашивали сброс — проигнорируйте это сообщение.";

        return (new TelegramBotService())->sendMessage((int) $user->telegram_chat_id, $text, 'Markdown');
    }
}
