<?php

namespace app\modules\api\controllers;

use Yii;
use app\models\ProfileForm;
use app\models\UserProfileCard;
use app\services\ProfileCardService;
use app\services\TelegramBotService;
use app\services\TelegramLinkService;

class ProfileController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'index' => ['GET'],
            'update' => ['POST'],
            'avatar' => ['POST'],
            'avatar-file' => ['GET'],
            'cards' => ['GET'],
            'card-select' => ['POST'],
            'card-remove' => ['POST'],
            'card-visibility' => ['POST'],
            'telegram' => ['GET'],
            'telegram-link' => ['POST'],
            'telegram-unlink' => ['POST'],
        ];
    }

    /**
     * GET /api/v1/profile
     */
    public function actionIndex(): array
    {
        $user = Yii::$app->user->identity;
        $cardService = new ProfileCardService();
        $selectedTemplate = $cardService->getSelectedTemplate($user);
        $availableTemplates = $cardService->getAvailableTemplates($user);
        $userCards = UserProfileCard::find()
            ->where(['user_id' => $user->id])
            ->indexBy('template_id')
            ->all();

        $avatarUrl = $user->avatar;
        if ($avatarUrl && strpos($avatarUrl, 'http') !== 0) {
            $avatarUrl = Yii::$app->request->hostInfo . Yii::getAlias('@web') . '/' . ltrim($avatarUrl, '/');
        }

        $templates = [];
        foreach ($availableTemplates as $tpl) {
            $uc = $userCards[$tpl->id] ?? null;
            $templates[] = [
                'id' => (int) $tpl->id,
                'name' => $tpl->name ?? '',
                'description' => $tpl->description ?? '',
                'code' => $tpl->code ?? '',
                'cssClass' => $tpl->css_class ?? ('profile-card-' . ($tpl->code ?? 'default')),
                'styleConfig' => $tpl->getStyleConfigArray(),
                'isSelected' => $selectedTemplate && (int)$selectedTemplate->id === (int)$tpl->id,
                'isVisible' => $uc ? (bool)$uc->is_visible : true,
            ];
        }

        $selectedCardData = null;
        if ($selectedTemplate) {
            $selectedCardData = [
                'id' => (int) $selectedTemplate->id,
                'code' => $selectedTemplate->code ?? '',
                'name' => $selectedTemplate->name ?? '',
                'cssClass' => $selectedTemplate->css_class ?? ('profile-card-' . ($selectedTemplate->code ?? 'default')),
                'styleConfig' => $selectedTemplate->getStyleConfigArray(),
            ];
        }

        return $this->success([
            'user' => [
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
            ],
            'selectedCard' => $selectedCardData,
            'templates' => $templates,
        ]);
    }

    /**
     * POST /api/v1/profile
     * Body: { "firstName": "...", "lastName": "..." }
     */
    public function actionUpdate(): array
    {
        $user = Yii::$app->user->identity;
        $model = new ProfileForm($user);
        $body = Yii::$app->request->getBodyParams();
        $model->first_name = $body['firstName'] ?? $body['first_name'] ?? $user->first_name;
        $model->last_name = $body['lastName'] ?? $body['last_name'] ?? $user->last_name;

        if (!$model->validate()) {
            return $this->error('Ошибка валидации', $model->errors, 422);
        }
        if (!$model->save()) {
            return $this->error('Не удалось сохранить профиль', [], 500);
        }
        return $this->success([], 'Профиль обновлён');
    }

    /**
     * POST /api/v1/profile/avatar
     * multipart/form-data: avatarFile (file)
     */
    public function actionAvatar(): array
    {
        $user = Yii::$app->user->identity;
        $model = new ProfileForm($user);
        $model->avatarFile = \yii\web\UploadedFile::getInstanceByName('avatarFile');
        if (!$model->avatarFile) {
            return $this->error('Файл не загружен. Отправьте поле avatarFile.', [], 422);
        }
        if (!$model->validate()) {
            return $this->error('Ошибка валидации', $model->errors, 422);
        }
        if (!$model->save()) {
            return $this->error('Не удалось загрузить аватар', [], 500);
        }
        $avatarUrl = $user->avatar;
        if ($avatarUrl && strpos($avatarUrl, 'http') !== 0) {
            $avatarUrl = Yii::$app->request->hostInfo . Yii::getAlias('@web') . '/' . ltrim($avatarUrl, '/');
        }
        return $this->success(['avatar' => $avatarUrl], 'Аватар обновлён');
    }

    /**
     * GET /api/v1/profile/avatar-file — отдать файл аватара текущего пользователя (для отображения с Bearer).
     */
    public function actionAvatarFile()
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->avatar) {
            Yii::$app->response->statusCode = 404;
            return null;
        }
        $path = Yii::getAlias('@webroot') . '/' . ltrim($user->avatar, '/');
        if (!is_file($path)) {
            Yii::$app->response->statusCode = 404;
            return null;
        }
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_RAW;
        $mime = 'image/jpeg';
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($path) ?: $mime;
        } else {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = ['png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'][$ext] ?? $mime;
        }
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Content-Disposition', 'inline');
        $response->stream = function () use ($path) {
            readfile($path);
        };
        $response->send();
        exit(0);
    }

    /**
     * GET /api/v1/profile/cards
     */
    public function actionCards(): array
    {
        $user = Yii::$app->user->identity;
        $cardService = new ProfileCardService();
        $selectedTemplate = $cardService->getSelectedTemplate($user);
        $availableTemplates = $cardService->getAvailableTemplates($user);
        $userCards = UserProfileCard::find()
            ->where(['user_id' => $user->id])
            ->indexBy('template_id')
            ->all();

        $list = [];
        foreach ($availableTemplates as $tpl) {
            $uc = $userCards[$tpl->id] ?? null;
            $list[] = [
                'id' => (int) $tpl->id,
                'name' => $tpl->name ?? '',
                'description' => $tpl->description ?? '',
                'code' => $tpl->code ?? '',
                'cssClass' => $tpl->css_class ?? ('profile-card-' . ($tpl->code ?? 'default')),
                'styleConfig' => $tpl->getStyleConfigArray(),
                'isSelected' => $selectedTemplate && (int)$selectedTemplate->id === (int)$tpl->id,
                'isVisible' => $uc ? (bool)$uc->is_visible : true,
            ];
        }
        return $this->success(['templates' => $list]);
    }

    /**
     * POST /api/v1/profile/cards/select
     * Body: { "templateId": 1 }
     */
    public function actionCardSelect(): array
    {
        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->getBodyParams();
        $id = (int)($body['templateId'] ?? $body['template_id'] ?? 0);
        if (!$id) {
            return $this->error('Укажите templateId', [], 422);
        }
        $service = new ProfileCardService();
        if ($service->selectTemplate($user, $id)) {
            return $this->success([], 'Карточка профиля выбрана');
        }
        return $this->error('Не удалось выбрать карточку профиля', [], 400);
    }

    /**
     * POST /api/v1/profile/cards/remove
     */
    public function actionCardRemove(): array
    {
        $user = Yii::$app->user->identity;
        $service = new ProfileCardService();
        $service->removeSelectedTemplate($user);
        return $this->success([], 'Карточка профиля снята');
    }

    /**
     * POST /api/v1/profile/cards/visibility
     * Body: { "templateId": 1, "visible": true }
     */
    public function actionCardVisibility(): array
    {
        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->getBodyParams();
        $id = (int)($body['templateId'] ?? $body['template_id'] ?? 0);
        $visible = (bool)($body['visible'] ?? true);
        if (!$id) {
            return $this->error('Укажите templateId', [], 422);
        }
        $service = new ProfileCardService();
        if ($service->setVisibility($user, $id, $visible)) {
            return $this->success([], $visible ? 'Карточка отображается' : 'Карточка скрыта');
        }
        return $this->error('Не удалось изменить видимость', [], 400);
    }

    /**
     * GET /api/v1/profile/telegram — статус привязки Telegram
     */
    public function actionTelegram(): array
    {
        $user = Yii::$app->user->identity;
        $bot = new TelegramBotService();
        $username = $bot->getBotUsername() ?? (Yii::$app->params['telegramBotUsername'] ?? 'dbthub_bot');

        return $this->success([
            'isLinked' => $user->hasLinkedTelegram(),
            'botUsername' => $username,
        ]);
    }

    /**
     * POST /api/v1/profile/telegram-link — сгенерировать код привязки Telegram
     */
    public function actionTelegramLink(): array
    {
        $user = Yii::$app->user->identity;
        $linkService = new TelegramLinkService();
        $code = $linkService->generateLinkCode($user);
        $bot = new TelegramBotService();
        $username = $bot->getBotUsername() ?? (Yii::$app->params['telegramBotUsername'] ?? 'dbthub_bot');

        return $this->success([
            'code' => $code,
            'expiresIn' => TelegramLinkService::CODE_TTL_SECONDS,
            'botUsername' => $username,
            'isLinked' => $user->hasLinkedTelegram(),
            'instruction' => "Откройте @{$username} в Telegram и отправьте: /link {$code}",
        ]);
    }

    /**
     * POST /api/v1/profile/telegram-unlink
     */
    public function actionTelegramUnlink(): array
    {
        $user = Yii::$app->user->identity;
        (new TelegramLinkService())->unlink($user);
        return $this->success(['isLinked' => false], 'Telegram отвязан');
    }
}
