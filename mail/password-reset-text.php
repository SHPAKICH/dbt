<?php
/** @var app\models\User $user */
/** @var string $resetUrl */
/** @var int $ttlMinutes */
?>
Здравствуйте, <?= $user->getFullName() ?>!

Вы запросили сброс пароля в DBT Hub. Перейдите по ссылке (действует <?= (int) $ttlMinutes ?> мин.):

<?= $resetUrl ?>


Если вы не запрашивали сброс, проигнорируйте это письмо.
