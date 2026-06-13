<?php
/** @var yii\web\View $this */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Вход в систему';
$this->params['breadcrumbs'] = [];

$dbIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/></svg>';
?>
<div class="login-page">
    <div class="login-left">
        <div class="login-form-wrap">
            <div class="login-logo">
                <span class="login-logo-icon"><?= $dbIcon ?></span>
                <div>
                    <span class="login-logo-title">dbt hub</span>
                    
                </div>
            </div>

            <h2 class="login-welcome">С возвращением</h2>
            <p class="login-subtitle">Войдите, чтобы получить доступ к личному кабинету</p>

            <?php if ($model->hasErrors()): ?>
                <div class="alert alert-danger">
                    <?php foreach ($model->errors as $field => $errors): ?>
                        <?php foreach ($errors as $error): ?>
                            <div><?= Html::encode($error) ?></div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'options' => ['class' => 'login-form'],
                'fieldConfig' => [
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'form-label'],
                    'inputOptions' => ['class' => 'form-control login-input'],
                    'errorOptions' => ['class' => 'invalid-feedback'],
                ],
            ]); ?>

            <?php
            $iconEnvelope = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
            $iconLock = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
            $iconEye = '<svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            $iconEyeOff = '<svg class="icon-eye-off d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
            ?>
            <div class="login-field-wrap">
                <?= $form->field($model, 'emailOrPhone', [
                    'template' => "{label}\n<div class=\"login-input-wrapper\">{input}<span class=\"login-input-icon login-input-icon-left\">{$iconEnvelope}</span></div>\n{error}",
                ])->label('Email или телефон')->textInput([
                    'autofocus' => true,
                    'placeholder' => 'Введите email или телефон',
                    'autocomplete' => 'email',
                ]) ?>
            </div>

            <div class="login-field-wrap">
                <?= $form->field($model, 'password', [
                    'template' => "{label}\n<div class=\"login-input-wrapper\">{input}<span class=\"login-input-icon login-input-icon-left\">{$iconLock}</span><button type=\"button\" class=\"login-input-icon login-input-icon-right login-password-toggle\" aria-label=\"Показать пароль\" data-target=\"login-password\">{$iconEye}{$iconEyeOff}</button></div>\n{error}",
                ])->label('Пароль')->passwordInput([
                    'placeholder' => 'Введите пароль',
                    'autocomplete' => 'current-password',
                    'id' => 'login-password',
                ]) ?>
            </div>

            <div class="login-options">
                <?= $form->field($model, 'rememberMe', [
                    'options' => ['class' => 'login-remember-wrap mb-0'],
                ])->checkbox([
                    'template' => "<div class=\"form-check\">{input} {label}</div>\n<div class=\"invalid-feedback d-block\">{error}</div>",
                    'labelOptions' => ['class' => 'form-check-label'],
                    'inputOptions' => ['class' => 'form-check-input'],
                ])->label('Запомнить меня') ?>
                <?= Html::a('Забыли пароль?', '#', [
                    'class' => 'login-forgot',
                    'onclick' => 'alert("Функция восстановления пароля будет доступна в ближайшее время"); return false;'
                ]) ?>
            </div>

            <?= Html::submitButton('Войти', ['class' => 'btn btn-login', 'name' => 'login-button']) ?>

            <p class="login-register">
                Нет аккаунта? <?= Html::a('Обратитесь к администратору', '#', ['class' => 'login-link']) ?>
            </p>

            <?php ActiveForm::end(); ?>

            <p class="login-legal">
                Входя в систему, вы соглашаетесь с нашими <a href="#">Условиями использования</a> и <a href="#">Политикой конфиденциальности</a>
            </p>
        </div>
    </div>

    <div class="login-right">
        <div class="login-promo-bg"></div>
        <div class="login-promo-content">
            <div class="login-promo-icon"><?= $dbIcon ?></div>
            <h3 class="login-promo-title">dbt hub</h3>
            <p class="login-promo-desc">Собрали все ингредиенты в один напиток!</p>
            <div class="login-promo-stats">
                <span>30+ точек</span>
                <span>150+ сотрудников</span>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerCss(<<<CSS
.login-layout-body {
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}
.login-page {
    display: flex;
    min-height: 100vh;
}
.login-left {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-bg);
    padding: 2rem;
}
.login-form-wrap {
    width: 100%;
    max-width: 400px;
}
.login-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 2.5rem;
}
.login-logo-icon {
    display: flex;
    color: var(--color-primary);
}
.login-logo-icon svg {
    width: 40px;
    height: 40px;
}
.login-logo-title {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
}
.login-logo-subtitle {
    font-size: 0.85rem;
    color: var(--color-text-muted);
}
.login-welcome {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 0.5rem 0;
}
.login-subtitle {
    color: var(--color-text-muted);
    margin: 0 0 1.5rem 0;
}
.login-form .form-group,
.login-form .login-field-wrap {
    margin-bottom: 1.25rem;
}
.login-input-wrapper {
    position: relative;
    display: block;
}
.login-input-wrapper .form-control {
    padding-left: 44px;
    padding-right: 44px;
    height: 48px;
    border-radius: 8px;
    border: 1px solid var(--color-border);
}
.login-input-wrapper .form-control:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(240, 126, 155, 0.15);
}
.login-input-icon {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    margin: 0;
}
.login-input-icon-left {
    left: 14px;
    color: var(--color-primary);
}
.login-input-icon-left svg {
    display: block;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.login-input-icon-right {
    right: 14px;
    pointer-events: auto;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    color: var(--color-text-muted);
    line-height: 0;
}
.login-input-icon-right:hover {
    color: var(--color-primary);
}
.login-input-icon-right svg {
    display: block;
}
.login-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}
.login-forgot {
    font-size: 0.9rem;
    color: var(--color-text);
    text-decoration: none;
}
.login-forgot:hover {
    text-decoration: underline;
    color: var(--color-primary);
}
.btn-login {
    width: 100%;
    height: 48px;
    background: var(--color-primary);
    color: #fff;
    border: none;
    font-weight: 600;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}
.btn-login:hover {
    background: var(--color-primary-dark);
    color: #fff;
}
.login-register {
    text-align: center;
    color: var(--color-text-muted);
    font-size: 0.9rem;
    margin: 0 0 2rem 0;
}
.login-link {
    color: var(--color-primary);
    font-weight: 500;
    text-decoration: none;
}
.login-link:hover {
    text-decoration: underline;
    color: var(--color-primary-dark);
}
.login-legal {
    font-size: 0.75rem;
    color: var(--color-text-muted);
    margin: 0;
}
.login-legal a {
    color: var(--color-text-muted);
}
.login-legal a:hover {
    color: var(--color-primary);
}
.login-right {
    flex: 1;
    position: relative;
    display: none;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-accent) 100%);
    padding: 2rem;
}
.login-promo-bg {
    position: absolute;
    inset: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
    opacity: 0.5;
}
.login-promo-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 360px;
}
.login-promo-icon {
    color: #fff;
    margin-bottom: 1.5rem;
}
.login-promo-icon svg {
    width: 64px;
    height: 64px;
}
.login-promo-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 1rem 0;
}
.login-promo-desc {
    color: rgba(255,255,255,0.85);
    font-size: 1rem;
    line-height: 1.5;
    margin: 0 0 2rem 0;
}
.login-promo-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    color: rgba(255,255,255,0.9);
    font-weight: 600;
}
@media (min-width: 992px) {
    .login-right {
        display: flex;
    }
}
CSS
);
$this->registerJs(<<<JS
(function() {
    var btn = document.querySelector('.login-password-toggle');
    if (!btn) return;
    btn.addEventListener('click', function() {
        var target = document.getElementById(btn.getAttribute('data-target'));
        if (!target) return;
        var eye = btn.querySelector('.icon-eye');
        var eyeOff = btn.querySelector('.icon-eye-off');
        if (target.type === 'password') {
            target.type = 'text';
            eye.classList.add('d-none');
            eyeOff.classList.remove('d-none');
        } else {
            target.type = 'password';
            eye.classList.remove('d-none');
            eyeOff.classList.add('d-none');
        }
    });
})();
JS
);
?>
