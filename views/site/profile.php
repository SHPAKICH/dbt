<?php

/** @var yii\web\View $this */
/** @var app\models\User $user */
/** @var app\models\ProfileForm $model */

use app\models\UserProfileCard;
use app\services\ProfileCardService;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Личный кабинет';
$this->params['breadcrumbs'][] = $this->title;

$cardService = new ProfileCardService();
$availableTemplates = $cardService->getAvailableTemplates($user);
$selectedTemplate = $cardService->getSelectedTemplate($user);
$userCards = UserProfileCard::find()
    ->where(['user_id' => $user->id])
    ->indexBy('template_id')
    ->all();
?>
<div class="site-profile profile-modern">
    <div class="profile-header mb-4">
        <h1 class="profile-title"><?= Html::encode($this->title) ?></h1>
    </div>

    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'profile-form', 'enctype' => 'multipart/form-data'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'form-label fw-semibold'],
        ],
    ]); ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <?php
            $avatarUrl = $user->avatar;
            if ($avatarUrl && strpos($avatarUrl, 'http://') !== 0 && strpos($avatarUrl, 'https://') !== 0) {
                $avatarUrl = Yii::getAlias('@web') . '/' . ltrim($avatarUrl, '/');
            }
            ?>
            <div class="profile-card profile-card-avatar">
                <div class="profile-avatar-wrap">
                    <?php if ($user->avatar): ?>
                        <?= Html::img($avatarUrl, ['class' => 'profile-avatar-img', 'alt' => 'Аватар']) ?>
                    <?php else: ?>
                        <div class="profile-avatar-placeholder">
                            <?= strtoupper(mb_substr($user->first_name ?: $user->email, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <label class="profile-avatar-overlay profile-avatar-upload">
                        <?= Html::fileInput('ProfileForm[avatarFile]', null, [
                            'accept' => 'image/png,image/jpeg,image/jpg,image/gif,image/webp',
                            'class' => 'd-none',
                            'id' => 'profile-avatar-input',
                        ]) ?>
                        <span class="profile-avatar-btn">📷 Сменить</span>
                    </label>
                </div>
                <?php if ($model->hasErrors('avatarFile')): ?>
                    <div class="text-danger small mt-2"><?= $model->getFirstError('avatarFile') ?></div>
                <?php endif; ?>
                <p class="profile-avatar-hint mt-2">PNG, JPG, GIF до 3 МБ</p>

                <hr class="my-4">

                <h5 class="profile-name"><?= Html::encode($user->getFullName()) ?></h5>
                <p class="profile-position"><?= Html::encode($user->getPositionLabel()) ?></p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="profile-card">
                <h5 class="profile-card-heading">Основные данные</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <?= $form->field($model, 'first_name')->textInput(['class' => 'form-control form-control-lg', 'placeholder' => 'Введите имя']) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'last_name')->textInput(['class' => 'form-control form-control-lg', 'placeholder' => 'Введите фамилию']) ?>
                    </div>
                </div>
                <div class="mt-3">
                    <?= Html::submitButton('Сохранить изменения', ['class' => 'btn btn-primary btn-lg']) ?>
                </div>
            </div>

            <div class="profile-card mt-4">
                <h5 class="profile-card-heading">Карточка профиля</h5>
                <?php if ($availableTemplates): ?>
                    <div class="mb-3">
                        <div class="small text-muted mb-2">Текущая карточка (как в сайдбаре):</div>
                        <?php
                        $currentCode = $selectedTemplate ? $selectedTemplate->code : 'test_gold';
                        ?>
                        <div class="profile-card-preview mb-3">
                            <?= $this->render('@app/views/profile-card/templates/_test_gold', ['user' => $user]) ?>
                        </div>
                    </div>
                    <div class="profile-cards-list">
                        <?php foreach ($availableTemplates as $tpl): ?>
                            <?php
                            $isSelected = $selectedTemplate && $selectedTemplate->id === $tpl->id;
                            /** @var UserProfileCard|null $userCard */
                            $userCard = $userCards[$tpl->id] ?? null;
                            $isVisible = $userCard ? (bool)$userCard->is_visible : true;
                            ?>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div>
                                    <strong><?= Html::encode($tpl->name) ?></strong>
                                    <div class="text-muted small"><?= Html::encode($tpl->description) ?></div>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if ($isSelected): ?>
                                        <?= Html::beginForm(['/site/profile-card-remove'], 'post') ?>
                                        <?= Html::submitButton('Снять', ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                                        <?= Html::endForm() ?>
                                    <?php else: ?>
                                        <?= Html::beginForm(['/site/profile-card-select', 'id' => $tpl->id], 'post') ?>
                                        <?= Html::submitButton('Выбрать', ['class' => 'btn btn-primary btn-sm']) ?>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>

                                    <?php if ($isVisible): ?>
                                        <?= Html::beginForm(['/site/profile-card-visibility', 'id' => $tpl->id, 'visible' => 0], 'post') ?>
                                        <?= Html::submitButton('Скрыть', ['class' => 'btn btn-outline-light btn-sm']) ?>
                                        <?= Html::endForm() ?>
                                    <?php else: ?>
                                        <?= Html::beginForm(['/site/profile-card-visibility', 'id' => $tpl->id, 'visible' => 1], 'post') ?>
                                        <?= Html::submitButton('Показать', ['class' => 'btn btn-outline-success btn-sm']) ?>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Пока нет доступных карточек профиля.</p>
                <?php endif; ?>
            </div>

            <div class="profile-card mt-4">
                <h5 class="profile-card-heading">Контактная информация</h5>
                <div class="profile-info-table">
                    <div class="profile-info-row">
                        <span class="profile-info-label">Email</span>
                        <span class="profile-info-value"><?= Html::encode($user->email) ?></span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Телефон</span>
                        <span class="profile-info-value"><?= Html::encode($user->phone) ?></span>
                    </div>
                    <?php if ($user->location): ?>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Точка</span>
                        <span class="profile-info-value"><?= Html::encode($user->location->name) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Статус</span>
                        <span class="profile-info-value">
                            <?php if ($user->is_active): ?>
                                <span class="badge profile-badge-active">Активен</span>
                            <?php else: ?>
                                <span class="badge profile-badge-inactive">Неактивен</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">В системе с</span>
                        <span class="profile-info-value"><?= Yii::$app->formatter->asDate($user->created_at, 'long') ?></span>
                    </div>
                </div>
            </div>

            <div class="profile-card mt-4">
                <h5 class="profile-card-heading">🔔 Push-уведомления</h5>
                <p class="text-muted small mb-3">
                    Включите уведомления в браузере (или в приложении PWA на телефоне), чтобы получать напоминания о сменах и важные сообщения.
                </p>
                <button type="button" id="pushEnableBtn" class="btn btn-outline-primary">Включить уведомления</button>
                <span id="pushStatus" class="ms-2 small text-muted"></span>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$subscribeUrl = \yii\helpers\Url::to(['/push/subscribe']);
$this->registerCss(<<<CSS
.profile-modern .profile-header {
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 1rem;
}
.profile-modern .profile-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}
.profile-modern .profile-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(240, 126, 155, 0.08);
}
.profile-modern .profile-card-heading {
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0 0 1rem 0;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--color-border);
}
.profile-modern .profile-card-avatar {
    text-align: center;
    padding: 2rem 1.5rem;
}
.profile-modern .profile-avatar-wrap {
    position: relative;
    display: inline-block;
}
.profile-modern .profile-avatar-img,
.profile-modern .profile-avatar-placeholder {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    object-fit: cover;
}
.profile-modern .profile-avatar-placeholder {
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
    color: #fff;
    font-size: 4rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}
.profile-modern .profile-avatar-overlay {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
    cursor: pointer;
    margin: 0;
}
.profile-modern .profile-avatar-wrap:hover .profile-avatar-overlay {
    opacity: 1;
}
@media (hover: none) {
    .profile-modern .profile-avatar-overlay {
        opacity: 1;
        background: rgba(0,0,0,0.2);
    }
}
.profile-modern .profile-avatar-btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #fff;
    color: var(--color-primary);
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 500;
}
.profile-modern .profile-avatar-hint {
    font-size: 0.8rem;
    color: var(--color-text-muted);
    margin: 0;
}
.profile-modern .profile-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}
.profile-modern .profile-position {
    font-size: 0.9rem;
    color: var(--color-text-muted);
    margin: 0.25rem 0 0 0;
}
.profile-modern .profile-info-table {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.profile-modern .profile-info-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--color-border);
}
.profile-modern .profile-info-row:last-child {
    border-bottom: none;
}
.profile-modern .profile-info-label {
    flex: 0 0 140px;
    font-size: 0.9rem;
    color: var(--color-text-muted);
}
.profile-modern .profile-info-value {
    flex: 1;
    font-weight: 500;
}
.profile-modern .profile-badge-active {
    background: #198754;
}
.profile-modern .profile-badge-inactive {
    background: #dc3545;
}
@media (max-width: 767px) {
    .profile-modern .profile-avatar-img,
    .profile-modern .profile-avatar-placeholder {
        width: 120px;
        height: 120px;
        font-size: 3rem;
    }
}
CSS);
$this->registerJs("
document.getElementById('profile-avatar-input')?.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        document.querySelector('.profile-form')?.submit();
    }
});
");
$this->registerJs("
(function() {
    var btn = document.getElementById('pushEnableBtn');
    var status = document.getElementById('pushStatus');
    if (!btn || !status) return;
    function setStatus(text) { status.textContent = text; }
    btn.addEventListener('click', function() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            setStatus('Ваш браузер не поддерживает push.');
            return;
        }
        setStatus('Проверка…');
        navigator.serviceWorker.ready.then(function(reg) {
            return reg.pushManager.getSubscription().then(function(sub) {
                if (sub) {
                    setStatus('Уведомления уже включены.');
                    return;
                }
                return reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: null
                }).then(function(sub) {
                    var key = sub.getKey('p256dh');
                    var auth = sub.getKey('auth');
                    var data = new FormData();
                    data.append('endpoint', sub.endpoint);
                    data.append('p256dh', key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : '');
                    data.append('auth', auth ? btoa(String.fromCharCode.apply(null, new Uint8Array(auth))) : '');
                    data.append('_csrf', document.querySelector('meta[name=csrf-token]').content);
                    return fetch(" . \yii\helpers\Json::encode($subscribeUrl) . ", { method: 'POST', body: data, credentials: 'same-origin' }).then(function(r) { return r.json(); }).then(function(res) {
                        if (res.success) { setStatus('Уведомления включены.'); } else { setStatus(res.error || 'Ошибка'); }
                    });
                });
            });
        }).catch(function(err) {
            setStatus('Ошибка: ' + (err.message || 'нет поддержки'));
        });
    });
    if (navigator.serviceWorker) {
        navigator.serviceWorker.register('/sw.js').catch(function() {});
    }
})();
");
?>
