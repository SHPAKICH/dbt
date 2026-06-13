<?php

namespace app\modules\api\controllers;

use app\models\AccountingMessage;
use app\models\AccountingMessageFile;
use app\models\AccountingMessageReaction;
use app\models\Location;
use app\models\ManagerLocation;
use Yii;
use yii\web\UploadedFile;

/**
 * API для чата «Бухгалтерия».
 *
 * Маршруты:
 * - GET  /api/v1/accounting/locations
 * - GET  /api/v1/accounting/messages?location_id=N
 * - POST /api/v1/accounting/messages        (FormData: text, location_id, files[])
 */
class AccountingController extends BaseApiController
{
    private const ALLOWED_REACTIONS = ['👍', '❤️', '🔥', '😂', '👏', '😢', '😡', '🎉'];
    private const ALLOWED_UPLOAD_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'avif',
        'pdf', 'txt', 'csv', 'json', 'xml', 'doc', 'docx', 'xls', 'xlsx',
    ];
    private const ALLOWED_UPLOAD_MIME_TYPES = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif',
        'image/heic', 'image/heif', 'image/avif',
        'application/pdf', 'text/plain', 'text/csv', 'application/json', 'application/xml', 'text/xml',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream',
    ];
    private const MAX_UPLOAD_SIZE = 20971520; // 20 MB

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
            'locations'     => ['GET'],
            'last-messages' => ['GET'],
            'messages'      => ['GET', 'POST'],
            'react'         => ['POST'],
        ];
    }

    private function getAccessibleLocationIds(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return [];
        }

        if ($user->isAdmin()) {
            return Location::find()->select('id')->where(['is_active' => 1])->column();
        }

        if ($user->position === 'manager') {
            return ManagerLocation::find()
                ->select('location_id')
                ->where(['manager_id' => $user->id])
                ->column();
        }

        return $user->location_id ? [(int) $user->location_id] : [];
    }

    private function canAccessLocation(int $locationId): bool
    {
        return in_array($locationId, array_map('intval', $this->getAccessibleLocationIds()), true);
    }

    private function isAllowedUpload(UploadedFile $file): bool
    {
        if ($file->size > self::MAX_UPLOAD_SIZE) {
            return false;
        }

        $extension = strtolower((string) $file->getExtension());
        $mimeType = strtolower((string) $file->type);

        if ($extension !== '' && in_array($extension, self::ALLOWED_UPLOAD_EXTENSIONS, true)) {
            return true;
        }

        if ($mimeType !== '' && in_array($mimeType, self::ALLOWED_UPLOAD_MIME_TYPES, true)) {
            return true;
        }

        $tmpFile = $file->tempName;
        if ($tmpFile && file_exists($tmpFile) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = strtolower((string) finfo_file($finfo, $tmpFile));
            finfo_close($finfo);
            if (in_array($detectedMime, self::ALLOWED_UPLOAD_MIME_TYPES, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * GET /api/v1/accounting/locations
     * Все активные точки для левой панели чата.
     */
    public function actionLocations(): array
    {
        $locationIds = array_map('intval', $this->getAccessibleLocationIds());
        if (empty($locationIds)) {
            return $this->success(['locations' => []]);
        }

        $locations = Location::find()
            ->where(['is_active' => 1])
            ->andWhere(['id' => $locationIds])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        $list = [];
        foreach ($locations as $loc) {
            $list[] = [
                'id'   => (int) $loc->id,
                'name' => $loc->name,
            ];
        }

        return $this->success(['locations' => $list]);
    }

    /**
     * GET /api/v1/accounting/last-messages
     * Последнее сообщение для каждой локации — для живых превью в сайдбаре.
     */
    public function actionLastMessages(): array
    {
        $locationIds = array_map('intval', $this->getAccessibleLocationIds());
        if (empty($locationIds)) {
            return $this->success(['lastMessages' => []]);
        }

        $subQuery = AccountingMessage::find()
            ->select(['location_id', 'max_id' => 'MAX(id)'])
            ->where(['location_id' => $locationIds])
            ->groupBy('location_id');

        $items = AccountingMessage::find()
            ->alias('am')
            ->with(['user'])
            ->innerJoin(['latest' => $subQuery], 'am.id = latest.max_id')
            ->all();

        $result = [];
        foreach ($items as $m) {
            $preview = $m->text ? mb_substr($m->text, 0, 60) : null;
            $result[(string) $m->location_id] = [
                'locationId' => (int) $m->location_id,
                'text'       => $preview,
                'author'     => $m->user ? $m->user->getFullName() : '',
                'createdAt'  => $m->created_at,
                'hasFiles'   => (bool) AccountingMessageFile::find()->where(['message_id' => $m->id])->exists(),
            ];
        }

        return $this->success(['lastMessages' => $result]);
    }

    /**
     * GET  /api/v1/accounting/messages?location_id=N
     * POST /api/v1/accounting/messages
     */
    public function actionMessages(): array
    {
        if (Yii::$app->request->isGet) {
            return $this->listMessages();
        }
        return $this->createMessage();
    }

    /**
     * POST /api/v1/accounting/react
     * Переключение реакции пользователя на сообщении.
     */
    public function actionReact(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return $this->error('Не авторизован.', [], 401);
        }

        $messageId = (int) Yii::$app->request->post('message_id', 0);
        $emoji = trim((string) Yii::$app->request->post('emoji', ''));
        if ($messageId <= 0 || $emoji === '') {
            return $this->error('Не указаны message_id и emoji.', [], 422);
        }
        if (!in_array($emoji, self::ALLOWED_REACTIONS, true)) {
            return $this->error('Недопустимая реакция.', [], 422);
        }

        $message = AccountingMessage::findOne($messageId);
        if (!$message) {
            return $this->error('Сообщение не найдено.', [], 404);
        }
        if (!$this->canAccessLocation((int) $message->location_id)) {
            return $this->error('Нет доступа к выбранной точке.', [], 403);
        }

        $reaction = AccountingMessageReaction::findOne([
            'message_id' => $messageId,
            'user_id' => (int) $user->id,
        ]);

        if ($reaction && $reaction->emoji === $emoji) {
            $reaction->delete();
        } else {
            if (!$reaction) {
                $reaction = new AccountingMessageReaction();
                $reaction->message_id = $messageId;
                $reaction->user_id = (int) $user->id;
            }
            $reaction->emoji = $emoji;
            if (!$reaction->save()) {
                return $this->error('Не удалось сохранить реакцию.', $reaction->errors, 422);
            }
        }

        $message = AccountingMessage::find()->with(['reactions'])->where(['id' => $messageId])->one();
        return $this->success([
            'message_id' => $messageId,
            'reactions' => $this->buildReactions($message, (int) $user->id),
        ]);
    }

    private function listMessages(): array
    {
        $locationId = (int) (Yii::$app->request->get('location_id') ?? 0);
        if ($locationId <= 0) {
            return $this->error('Не указан location_id.', [], 422);
        }
        if (!$this->canAccessLocation($locationId)) {
            return $this->error('Нет доступа к выбранной точке.', [], 403);
        }

        $limit = (int) (Yii::$app->request->get('limit') ?? 100);
        if ($limit <= 0 || $limit > 500) {
            $limit = 100;
        }

        $items = AccountingMessage::find()
            ->with(['user', 'files', 'reactions'])
            ->where(['location_id' => $locationId])
            ->orderBy(['created_at' => SORT_ASC])
            ->limit($limit)
            ->all();

        $baseUrl  = Yii::$app->params['frontendFileBaseUrl'] ?? Yii::$app->request->hostInfo;
        $messages = [];
        foreach ($items as $m) {
            $files = [];
            foreach ($m->files as $f) {
                $files[] = [
                    'id'       => (int) $f->id,
                    'url'      => rtrim($baseUrl, '/') . '/' . ltrim($f->path, '/'),
                    'name'     => $f->name,
                    'mimeType' => $f->mime_type,
                ];
            }
            $messages[] = [
                'id'         => (int) $m->id,
                'text'       => $m->text,
                'author'     => $m->user ? $m->user->getFullName() : 'Пользователь #' . $m->user_id,
                'user_id'    => (int) $m->user_id,
                'location_id'=> (int) $m->location_id,
                'created_at' => $m->created_at,
                'files'      => $files,
                'reactions'  => $this->buildReactions($m, (int) Yii::$app->user->id),
            ];
        }

        return $this->success(['messages' => $messages]);
    }

    private function createMessage(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            return $this->error('Не авторизован.', [], 401);
        }

        $postSizeError = $this->checkPostSize();
        if ($postSizeError !== null) return $postSizeError;

        $locationId = (int) Yii::$app->request->post('location_id', 0);
        if ($locationId <= 0) {
            return $this->error('Не указан location_id.', [], 422);
        }
        if (!$this->canAccessLocation($locationId)) {
            return $this->error('Нет доступа к выбранной точке.', [], 403);
        }

        $location = Location::findOne(['id' => $locationId, 'is_active' => 1]);
        if (!$location) {
            return $this->error('Точка не найдена.', [], 404);
        }

        $text  = trim((string) Yii::$app->request->post('text', ''));
        $rawFiles = UploadedFile::getInstancesByName('files');

        foreach ($rawFiles as $file) {
            $fileErr = $this->getFileUploadError($file);
            if ($fileErr !== null) {
                return $this->error($fileErr, [], 422);
            }
        }

        $files = array_values(array_filter(
            $rawFiles,
            fn (UploadedFile $file): bool => $this->isAllowedUpload($file)
        ));
        if (empty($files) && !empty($rawFiles) && $text === '') {
            return $this->error('Формат файла не поддерживается.', [], 422);
        }
        if (empty($rawFiles) && $text === '') {
            return $this->error('Сообщение пустое.', [], 422);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $message              = new AccountingMessage();
            $message->user_id     = (int) $user->id;
            $message->location_id = $locationId;
            $message->text        = $text ?: null;
            if (!$message->save()) {
                $transaction->rollBack();
                return $this->error('Не удалось сохранить сообщение.', $message->errors, 422);
            }

            $savedFiles = [];
            if (!empty($files)) {
                $uploadDir = Yii::getAlias('@webroot/uploads/accounting');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                foreach ($files as $file) {
                    if ($file->error !== UPLOAD_ERR_OK) continue;
                    $ext = $file->getExtension() ?: 'bin';
                    $safeName     = uniqid('acc_', true) . '.' . $ext;
                    $relativePath = 'uploads/accounting/' . $safeName;
                    $absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
                    if (!$file->saveAs($absolutePath)) {
                        continue;
                    }
                    $fileModel             = new AccountingMessageFile();
                    $fileModel->message_id = $message->id;
                    $fileModel->path       = $relativePath;
                    $fileModel->name       = $file->name;
                    $fileModel->mime_type  = $file->type;
                    if ($fileModel->save()) {
                        $savedFiles[] = $fileModel;
                    }
                }
            }

            $transaction->commit();

            $baseUrl  = Yii::$app->params['frontendFileBaseUrl'] ?? Yii::$app->request->hostInfo;
            $filesOut = [];
            foreach ($savedFiles as $f) {
                $filesOut[] = [
                    'id'       => (int) $f->id,
                    'url'      => rtrim($baseUrl, '/') . '/' . ltrim($f->path, '/'),
                    'name'     => $f->name,
                    'mimeType' => $f->mime_type,
                ];
            }

            return $this->success([
                'message' => [
                    'id'          => (int) $message->id,
                    'text'        => $message->text,
                    'author'      => $user->getFullName(),
                    'user_id'     => (int) $message->user_id,
                    'location_id' => (int) $message->location_id,
                    'created_at'  => $message->created_at,
                    'files'       => $filesOut,
                    'reactions'   => [],
                ],
            ]);
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            return $this->error('Ошибка при сохранении сообщения.', [], 500);
        }
    }

    private function buildReactions(AccountingMessage $message, int $currentUserId): array
    {
        $grouped = [];
        foreach ($message->reactions as $reaction) {
            if (!isset($grouped[$reaction->emoji])) {
                $grouped[$reaction->emoji] = [
                    'emoji' => $reaction->emoji,
                    'count' => 0,
                    'reactedByMe' => false,
                ];
            }
            $grouped[$reaction->emoji]['count']++;
            if ((int) $reaction->user_id === $currentUserId) {
                $grouped[$reaction->emoji]['reactedByMe'] = true;
            }
        }

        return array_values($grouped);
    }
}
