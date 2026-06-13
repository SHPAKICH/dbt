<?php

namespace app\modules\api\controllers;

use Yii;
use app\models\LoginForm;
use app\models\ForgotPasswordForm;
use app\models\ResetPasswordForm;
use app\services\ProfileCardService;
use app\services\PasswordResetService;

class AuthController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'optional' => ['login', 'forgot-password', 'reset-password', 'validate-reset-token'],
        ];
        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'login' => ['POST'],
            'logout' => ['POST'],
            'user' => ['GET'],
            'forgot-password' => ['POST'],
            'reset-password' => ['POST'],
            'validate-reset-token' => ['GET'],
        ];
    }

    /**
     * POST /api/v1/auth/login
     * Body: { "emailOrPhone": "...", "password": "...", "rememberMe": true }
     */
    public function actionLogin(): array
    {
        if (!Yii::$app->user->isGuest) {
            $user = Yii::$app->user->identity;
            return $this->success(['user' => $this->serializeUser($user), 'token' => $user->getAuthKey()]);
        }

        $model = new LoginForm();
        $body = Yii::$app->request->getBodyParams();
        if (empty($body) && Yii::$app->request->getRawBody()) {
            $decoded = json_decode(Yii::$app->request->getRawBody(), true);
            $body = is_array($decoded) ? $decoded : [];
        }
        $model->emailOrPhone = $body['emailOrPhone'] ?? $body['email_or_phone'] ?? '';
        $model->password = $body['password'] ?? '';
        $model->rememberMe = (bool)($body['rememberMe'] ?? $body['remember_me'] ?? true);

        if (!$model->validate()) {
            $message = implode(' ', $model->getFirstErrors()) ?: 'Ошибка входа';
            return $this->error($message, $model->errors, 422);
        }

        if ($model->login()) {
            $user = Yii::$app->user->identity;
            return $this->success([
                'user' => $this->serializeUser($user),
                'token' => $user->getAuthKey(),
                'message' => 'Вход выполнен',
            ]);
        }

        return $this->error('Неверный email/телефон или пароль.', [], 401);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function actionLogout(): array
    {
        $user = Yii::$app->user->identity;
        if ($user) {
            $user->generateAuthKey();
            $user->save(false, ['auth_key']);
        }
        Yii::$app->user->logout();
        return $this->success([], 'Выход выполнен');
    }

    /**
     * POST /api/v1/auth/forgot-password
     * Body: { "emailOrPhone": "...", "channel": "auto"|"email"|"telegram" }
     */
    public function actionForgotPassword(): array
    {
        @set_time_limit(30);

        $model = new ForgotPasswordForm();
        $body = $this->getJsonBody();
        $model->emailOrPhone = $body['emailOrPhone'] ?? $body['email_or_phone'] ?? '';
        $model->channel = $body['channel'] ?? 'auto';

        $genericMessage = 'Если аккаунт с такими данными существует, мы отправили инструкцию по сбросу пароля.';

        if (!$model->validate()) {
            return $this->error(implode(' ', $model->getFirstErrors()) ?: 'Ошибка', $model->errors, 422);
        }

        if ($model->isRateLimited()) {
            return $this->success(['message' => $genericMessage], $genericMessage);
        }

        $user = $model->findUser();
        if (!$user || (!$user->is_active && !$user->isAdmin())) {
            return $this->success(['message' => $genericMessage], $genericMessage);
        }

        $resetService = new PasswordResetService();
        $channel = $model->channel;

        if ($channel === 'telegram' && !$resetService->hasTelegramChannel($user)) {
            return $this->success([
                'message' => $genericMessage,
                'hint' => 'telegram_not_linked',
            ], $genericMessage);
        }

        if ($channel === 'email' && !$resetService->canSendEmail($user)) {
            return $this->success([
                'message' => $genericMessage,
                'hint' => 'email_unavailable',
            ], $genericMessage);
        }

        $resetService->requestReset($user, $channel);

        return $this->success(['message' => $genericMessage], $genericMessage);
    }

    /**
     * GET /api/v1/auth/validate-reset-token?token=...
     */
    public function actionValidateResetToken(): array
    {
        $token = trim((string) Yii::$app->request->get('token', ''));
        $user = (new PasswordResetService())->findUserByToken($token);
        if (!$user) {
            return $this->error('Ссылка недействительна или истекла', [], 400);
        }
        return $this->success(['valid' => true]);
    }

    /**
     * POST /api/v1/auth/reset-password
     * Body: { "token": "...", "password": "...", "passwordConfirm": "..." }
     */
    public function actionResetPassword(): array
    {
        $model = new ResetPasswordForm();
        $body = $this->getJsonBody();
        $model->token = $body['token'] ?? '';
        $model->password = $body['password'] ?? '';
        $model->passwordConfirm = $body['passwordConfirm'] ?? $body['password_confirm'] ?? '';

        if (!$model->validate()) {
            return $this->error(implode(' ', $model->getFirstErrors()) ?: 'Ошибка', $model->errors, 422);
        }

        $resetService = new PasswordResetService();
        $user = $resetService->findUserByToken($model->token);
        if (!$user) {
            return $this->error('Ссылка недействительна или истекла', [], 400);
        }

        if (!$resetService->resetPassword($user, $model->password)) {
            return $this->error('Не удалось сохранить пароль', [], 500);
        }

        return $this->success([], 'Пароль успешно изменён. Войдите с новым паролем.');
    }

    /**
     * GET /api/v1/auth/user
     */
    public function actionUser(): array
    {
        if (Yii::$app->user->isGuest) {
            Yii::$app->response->statusCode = 401;
            return $this->error('Не авторизован', [], 401);
        }
        return $this->success(['user' => $this->serializeUser(Yii::$app->user->identity)]);
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

    private function serializeUser(\app\models\User $user): array
    {
        $avatarUrl = $user->avatar;
        if ($avatarUrl && strpos($avatarUrl, 'http') !== 0) {
            $avatarUrl = Yii::$app->request->hostInfo . Yii::getAlias('@web') . '/' . ltrim($avatarUrl, '/');
        }
        $cardService = new ProfileCardService();
        $selectedCard = $cardService->getSelectedTemplate($user);
        $selectedCardData = null;
        if ($selectedCard) {
            $selectedCardData = [
                'id' => (int) $selectedCard->id,
                'code' => $selectedCard->code ?? '',
                'name' => $selectedCard->name ?? '',
                'cssClass' => $selectedCard->css_class ?? ('profile-card-' . ($selectedCard->code ?? 'default')),
                'styleConfig' => $selectedCard->getStyleConfigArray(),
            ];
        }
        return [
            'id' => (int) $user->id,
            'email' => $user->email,
            'phone' => $user->phone,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'fullName' => $user->getFullName(),
            'position' => $user->position,
            'positionLabel' => $user->getPositionLabel(),
            'avatar' => $avatarUrl,
            'locationId' => $user->location_id ? (int) $user->location_id : null,
            'locationName' => $user->location ? $user->location->name : null,
            'locationIikoName' => $user->location && $user->location->hasAttribute('iiko_name')
                ? ($user->location->iiko_name ?: null)
                : null,
            'isActive' => (bool) $user->is_active,
            'isAdmin' => $user->isAdmin(),
            'hasSuperAccess' => $user->hasSuperAccess(),
            'isMainAdmin' => $user->isMainAdmin(),
            'createdAt' => $user->created_at,
            'selectedCard' => $selectedCardData,
        ];
    }
}
