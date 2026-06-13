<?php

namespace app\modules\api\controllers;

use app\models\Location;
use app\models\ManagerLocation;
use app\models\ShiftTask;
use app\models\ShiftTaskCompletion;
use app\models\ShiftTaskPhoto;
use app\services\TelegramNotificationService;
use Yii;
use yii\web\UploadedFile;

class ShiftTaskController extends BaseApiController
{
    private const ALLOWED_IMAGE_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'webp', 'heic', 'heif', 'avif', 'gif',
    ];

    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
        'image/avif' => 'avif',
        'image/gif' => 'gif',
    ];

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
            'locations'  => ['GET'],
            'tasks'      => ['GET'],
            'create'     => ['POST'],
            'complete'   => ['POST'],
            'delete'     => ['DELETE'],
            'history'    => ['GET'],
        ];
    }

    /**
     * Может ли текущий пользователь создавать задачи (manager, location_manager, admin).
     */
    private function canCreateTasks(): bool
    {
        $user = Yii::$app->user->identity;
        if (!$user) return false;
        return $user->isAdmin() || in_array($user->position, ['manager', 'location_manager'], true);
    }

    /**
     * Получить ID локаций, на которые пользователь может ставить задачи.
     */
    private function getManageableLocationIds(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return [];

        if ($user->isAdmin()) {
            return Location::find()
                ->select('id')
                ->where(['is_active' => 1])
                ->column();
        }

        if ($user->position === 'manager') {
            return ManagerLocation::find()
                ->select('location_id')
                ->where(['manager_id' => $user->id])
                ->column();
        }

        if ($user->position === 'location_manager' && $user->location_id) {
            return [(int) $user->location_id];
        }

        return [];
    }

    /**
     * Получить локации, к которым у пользователя есть доступ (для просмотра задач).
     */
    private function getAccessibleLocations(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return [];

        if ($user->isAdmin()) {
            return Location::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();
        }

        if ($user->position === 'manager') {
            $locIds = ManagerLocation::find()
                ->select('location_id')
                ->where(['manager_id' => $user->id])
                ->column();
            if (empty($locIds)) return [];
            return Location::find()->where(['id' => $locIds, 'is_active' => 1])->orderBy(['name' => SORT_ASC])->all();
        }

        if ($user->location_id) {
            $loc = Location::findOne(['id' => $user->location_id, 'is_active' => 1]);
            return $loc ? [$loc] : [];
        }

        return [];
    }

    /**
     * GET /api/v1/shift-tasks/locations
     */
    public function actionLocations(): array
    {
        $locations = $this->getAccessibleLocations();
        $list = [];
        foreach ($locations as $loc) {
            $list[] = ['id' => (int) $loc->id, 'name' => $loc->name];
        }
        return $this->success([
            'locations' => $list,
            'canCreate' => $this->canCreateTasks(),
        ]);
    }

    /**
     * GET /api/v1/shift-tasks/tasks?location_id=&shift_date=
     */
    public function actionTasks(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return $this->error('Не авторизован.', [], 401);

        $locationId = (int) Yii::$app->request->get('location_id', 0);
        $shiftDate = Yii::$app->request->get('shift_date', date('Y-m-d'));

        if ($locationId <= 0) {
            return $this->error('Не указан location_id.', [], 422);
        }

        $accessibleIds = array_map(fn($l) => (int) $l->id, $this->getAccessibleLocations());
        if (!in_array($locationId, $accessibleIds, true)) {
            return $this->error('Нет доступа к этой точке.', [], 403);
        }

        $this->cleanupPhotosSilent();

        $tasks = ShiftTask::find()
            ->with(['creator', 'completion.completedByUser', 'completion.photos'])
            ->where(['location_id' => $locationId, 'shift_date' => $shiftDate])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        $baseUrl = Yii::$app->params['frontendFileBaseUrl'] ?? Yii::$app->request->hostInfo;
        $result = [];

        foreach ($tasks as $task) {
            $item = [
                'id' => (int) $task->id,
                'title' => $task->title,
                'requiresPhoto' => (bool) $task->requires_photo,
                'shiftDate' => $task->shift_date,
                'createdBy' => $task->creator ? $task->creator->getFullName() : '',
                'createdById' => (int) $task->created_by,
                'createdAt' => $task->created_at,
                'completion' => null,
            ];

            if ($task->completion) {
                $c = $task->completion;
                $photos = [];
                if ($c->photos) {
                    $cutoff = date('Y-m-d H:i:s', strtotime('-48 hours'));
                    foreach ($c->photos as $p) {
                        if ($p->created_at >= $cutoff) {
                            $photos[] = [
                                'id' => (int) $p->id,
                                'url' => rtrim($baseUrl, '/') . '/' . ltrim($p->file_path, '/'),
                                'fileName' => $p->file_name,
                            ];
                        }
                    }
                }
                $item['completion'] = [
                    'id' => (int) $c->id,
                    'completedBy' => $c->completedByUser ? $c->completedByUser->getFullName() : '',
                    'completedAt' => $c->completed_at,
                    'comment' => $c->comment,
                    'photos' => $photos,
                ];
            }

            $result[] = $item;
        }

        return $this->success(['tasks' => $result]);
    }

    /**
     * POST /api/v1/shift-tasks/create
     * Body: { title, location_ids: [1,2,...], shift_date, requires_photo: 0|1 }
     */
    public function actionCreate(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return $this->error('Не авторизован.', [], 401);

        if (!$this->canCreateTasks()) {
            return $this->error('Нет прав для создания задач.', [], 403);
        }

        $title = trim((string) Yii::$app->request->post('title', ''));
        $locationIds = Yii::$app->request->post('location_ids', []);
        $shiftDate = Yii::$app->request->post('shift_date', date('Y-m-d'));
        $requiresPhoto = (int) Yii::$app->request->post('requires_photo', 0);

        if ($title === '') {
            return $this->error('Введите текст задачи.', [], 422);
        }

        if (!is_array($locationIds) || empty($locationIds)) {
            return $this->error('Выберите хотя бы одну точку.', [], 422);
        }

        $manageableIds = $this->getManageableLocationIds();
        $locationIds = array_map('intval', $locationIds);
        foreach ($locationIds as $lid) {
            if (!in_array($lid, $manageableIds, true)) {
                return $this->error('Нет прав для создания задач на одну из выбранных точек.', [], 403);
            }
        }

        $created = [];
        foreach ($locationIds as $lid) {
            $task = new ShiftTask();
            $task->location_id = $lid;
            $task->created_by = (int) $user->id;
            $task->title = $title;
            $task->requires_photo = $requiresPhoto ? 1 : 0;
            $task->shift_date = $shiftDate;
            if ($task->save()) {
                $created[] = (int) $task->id;
                (new TelegramNotificationService())->onShiftTaskCreated($task);
            }
        }

        if (empty($created)) {
            return $this->error('Не удалось создать задачи.', [], 500);
        }

        return $this->success(['createdIds' => $created], 'Задача создана.');
    }

    /**
     * POST /api/v1/shift-tasks/complete
     * Body (multipart): task_id, comment (optional), photos[] (files, optional)
     */
    public function actionComplete(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return $this->error('Не авторизован.', [], 401);

        $postSizeError = $this->checkPostSize();
        if ($postSizeError !== null) return $postSizeError;

        $taskId = (int) Yii::$app->request->post('task_id', 0);
        $comment = trim((string) Yii::$app->request->post('comment', ''));

        $task = ShiftTask::findOne($taskId);
        if (!$task) {
            return $this->error('Задача не найдена.', [], 404);
        }

        $accessibleIds = array_map(fn($l) => (int) $l->id, $this->getAccessibleLocations());
        if (!in_array((int) $task->location_id, $accessibleIds, true)) {
            return $this->error('Нет доступа к этой задаче.', [], 403);
        }

        if ($task->completion) {
            return $this->error('Задача уже выполнена.', [], 422);
        }

        $files = UploadedFile::getInstancesByName('photos');
        if (empty($files)) {
            $files = UploadedFile::getInstancesByName('photos[]');
        }

        foreach ($files as $file) {
            $fileErr = $this->getFileUploadError($file);
            if ($fileErr !== null) {
                return $this->error($fileErr, [], 422);
            }
        }

        if ($task->requires_photo && empty($files)) {
            return $this->error('Для этой задачи требуется фотоотчёт.', [], 422);
        }

        $completion = new ShiftTaskCompletion();
        $completion->task_id = $taskId;
        $completion->completed_by = (int) $user->id;
        $completion->comment = $comment !== '' ? $comment : null;

        if (!$completion->save()) {
            return $this->error('Ошибка сохранения.', $completion->getErrors(), 500);
        }

        $uploadDir = Yii::getAlias('@webroot/uploads/shift-tasks');
        if (!empty($files) && !is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $savedPhotos = [];
        foreach ($files as $file) {
            if ($file->error !== UPLOAD_ERR_OK) continue;

            $ext = $this->resolveImageExtension($file);
            if ($ext === null) continue;

            $safeName = uniqid('st_', true) . '.' . $ext;
            $relativePath = 'uploads/shift-tasks/' . $safeName;
            $absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

            if (!$file->saveAs($absolutePath)) continue;

            $photo = new ShiftTaskPhoto();
            $photo->completion_id = (int) $completion->id;
            $photo->file_path = $relativePath;
            $photo->file_name = $file->name;
            if ($photo->save()) {
                $savedPhotos[] = $photo->file_name;
            }
        }

        (new TelegramNotificationService())->onShiftTaskCompleted($task, $user);

        return $this->success([
            'completionId' => (int) $completion->id,
            'photosCount' => count($savedPhotos),
        ], 'Задача выполнена.');
    }

    /**
     * DELETE /api/v1/shift-tasks/delete?id=
     */
    public function actionDelete(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return $this->error('Не авторизован.', [], 401);

        $id = (int) Yii::$app->request->get('id', 0);
        $task = ShiftTask::findOne($id);
        if (!$task) {
            return $this->error('Задача не найдена.', [], 404);
        }

        $canDelete = $user->isAdmin() || (int) $task->created_by === (int) $user->id;
        if (!$canDelete) {
            return $this->error('Нет прав на удаление.', [], 403);
        }

        if ($task->completion && $task->completion->photos) {
            foreach ($task->completion->photos as $photo) {
                $absPath = Yii::getAlias('@webroot') . '/' . ltrim($photo->file_path, '/');
                if (file_exists($absPath)) {
                    @unlink($absPath);
                }
            }
        }

        $task->delete();

        return $this->success([], 'Задача удалена.');
    }

    /**
     * GET /api/v1/shift-tasks/history?location_id=&shift_date=
     */
    public function actionHistory(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return $this->error('Не авторизован.', [], 401);

        if (!$this->canCreateTasks()) {
            return $this->error('Нет прав для просмотра истории.', [], 403);
        }

        $locationId = (int) Yii::$app->request->get('location_id', 0);
        $shiftDate = Yii::$app->request->get('shift_date', '');

        $manageableIds = $this->getManageableLocationIds();

        $query = ShiftTask::find()
            ->with(['creator', 'completion.completedByUser', 'completion.photos', 'location'])
            ->orderBy(['shift_date' => SORT_DESC, 'created_at' => SORT_ASC]);

        if ($locationId > 0) {
            if (!in_array($locationId, $manageableIds, true)) {
                return $this->error('Нет доступа к этой точке.', [], 403);
            }
            $query->andWhere(['location_id' => $locationId]);
        } else {
            $query->andWhere(['location_id' => $manageableIds]);
        }

        if ($shiftDate !== '') {
            $query->andWhere(['shift_date' => $shiftDate]);
        }

        $query->limit(200);
        $tasks = $query->all();

        $baseUrl = Yii::$app->params['frontendFileBaseUrl'] ?? Yii::$app->request->hostInfo;
        $cutoff = date('Y-m-d H:i:s', strtotime('-48 hours'));
        $result = [];

        foreach ($tasks as $task) {
            $item = [
                'id' => (int) $task->id,
                'title' => $task->title,
                'requiresPhoto' => (bool) $task->requires_photo,
                'shiftDate' => $task->shift_date,
                'locationName' => $task->location ? $task->location->name : '',
                'createdBy' => $task->creator ? $task->creator->getFullName() : '',
                'createdAt' => $task->created_at,
                'completion' => null,
            ];

            if ($task->completion) {
                $c = $task->completion;
                $photos = [];
                if ($c->photos) {
                    foreach ($c->photos as $p) {
                        if ($p->created_at >= $cutoff) {
                            $photos[] = [
                                'id' => (int) $p->id,
                                'url' => rtrim($baseUrl, '/') . '/' . ltrim($p->file_path, '/'),
                                'fileName' => $p->file_name,
                            ];
                        }
                    }
                }
                $item['completion'] = [
                    'completedBy' => $c->completedByUser ? $c->completedByUser->getFullName() : '',
                    'completedAt' => $c->completed_at,
                    'comment' => $c->comment,
                    'photos' => $photos,
                ];
            }

            $result[] = $item;
        }

        return $this->success(['tasks' => $result]);
    }

    private function cleanupPhotosSilent(): void
    {
        try {
            ShiftTaskPhoto::cleanupExpired();
        } catch (\Throwable $e) {
            Yii::error('Shift task photo cleanup error: ' . $e->getMessage(), __METHOD__);
        }
    }

    private function resolveImageExtension(UploadedFile $file): ?string
    {
        $ext = strtolower((string) $file->getExtension());
        if ($ext !== '' && in_array($ext, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
            return $ext;
        }

        $mime = strtolower((string) $file->type);
        if (isset(self::MIME_TO_EXTENSION[$mime])) {
            return self::MIME_TO_EXTENSION[$mime];
        }

        if (strpos($mime, 'image/') === 0) {
            $fromMime = substr($mime, 6);
            if (in_array($fromMime, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
                return $fromMime;
            }
        }

        $tmpFile = $file->tempName;
        if ($tmpFile && file_exists($tmpFile) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = strtolower((string) finfo_file($finfo, $tmpFile));
            finfo_close($finfo);
            if (isset(self::MIME_TO_EXTENSION[$detectedMime])) {
                return self::MIME_TO_EXTENSION[$detectedMime];
            }
        }

        return null;
    }

}
