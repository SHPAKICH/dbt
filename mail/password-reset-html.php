<?php
use yii\helpers\Html;

/** @var app\models\User $user */
/** @var string $resetUrl */
/** @var int $ttlMinutes */
?>
<p>Здравствуйте, <?= Html::encode($user->getFullName()) ?>!</p>
<p>Вы запросили сброс пароля в DBT Hub. Нажмите кнопку ниже — ссылка действует <?= (int) $ttlMinutes ?> мин.</p>
<p style="margin: 24px 0;">
    <a href="<?= Html::encode($resetUrl) ?>" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">
        Сбросить пароль
    </a>
</p>
<p>Или скопируйте ссылку в браузер:<br><?= Html::encode($resetUrl) ?></p>
<p style="color:#6b7280;font-size:14px;">Если вы не запрашивали сброс, просто проигнорируйте это письмо.</p>
