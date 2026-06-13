<?php

/** @var \yii\web\View $this */
/** @var \app\models\User $user */
?>
<div class="profile-card profile-card-test-gold">
    <div class="profile-card-header">Test Gold</div>
    <div class="profile-card-body">
        <div class="profile-card-name">
            <?= htmlspecialchars($user->getFullName(), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="profile-card-status">
            Тестовая карточка для проверки системы.
        </div>
    </div>
</div>

