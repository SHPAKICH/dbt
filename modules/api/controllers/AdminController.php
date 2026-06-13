<?php

namespace app\modules\api\controllers;

use app\models\KroBonusBand;
use app\models\Location;
use app\models\LocationKro;
use app\models\ManagerLocation;
use app\models\PositionRate;
use app\models\ProfileCardTemplate;
use app\models\PushSubscription;
use app\models\News;
use app\models\User;
use app\models\UserCreateForm;
use app\models\UserPositionHistory;
use app\models\UserUpdateForm;
use app\services\DatabaseBackupService;
use app\services\PushNotificationService;
use app\services\TelegramNotificationService;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdminController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['except'] = ['db-backup'];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'dashboard' => ['GET'],
            'users' => ['GET', 'POST'],
            'user' => ['GET', 'PUT', 'PATCH', 'DELETE'],
            'locations' => ['GET', 'POST'],
            'location' => ['GET', 'PUT', 'PATCH', 'DELETE'],
            'analytics-locations' => ['GET'],
            'db-backup' => ['POST'],
            'push-stats' => ['GET'],
            'push-send' => ['POST'],
            'news' => ['GET', 'POST'],
            'news-upload-image' => ['POST'],
            'news-item' => ['GET', 'PUT', 'PATCH', 'DELETE'],
            'profile-cards' => ['GET', 'POST'],
            'profile-card' => ['GET', 'PUT', 'PATCH', 'DELETE'],
            'profile-card-upload-bg' => ['POST'],
            'position-rates' => ['GET', 'PUT'],
            'kro-bands' => ['GET', 'PUT'],
            'location-kro' => ['GET', 'POST'],
        ];
    }

    private function requireAdmin(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user || (!$user->isAdmin() && !$user->isMainAdmin())) {
            Yii::$app->response->statusCode = 403;
            throw new \yii\web\ForbiddenHttpException('Доступ только для администратора.');
        }
    }

    private function requireMainAdmin(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isMainAdmin()) {
            Yii::$app->response->statusCode = 403;
            throw new \yii\web\ForbiddenHttpException('Доступ только для главного администратора.');
        }
    }

    private function requireAdminOrManager(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user || (!$user->isAdmin() && !$user->isMainAdmin() && $user->position !== 'manager')) {
            Yii::$app->response->statusCode = 403;
            throw new \yii\web\ForbiddenHttpException('Недостаточно прав.');
        }
    }

    private function getManagerLocationIds(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || $user->isMainAdmin() || $user->isAdmin()) {
            return [];
        }
        return (new \yii\db\Query())
            ->from('manager_locations')
            ->where(['manager_id' => $user->id])
            ->select('location_id')
            ->column();
    }

    private function normalizePushPath(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if ($url[0] !== '/' || strpos($url, '//') === 0 || preg_match('/[\r\n]/', $url)) {
            return null;
        }

        return $url;
    }

    /**
     * GET /api/v1/admin/dashboard
     */
    public function actionDashboard(): array
    {
        $this->requireAdmin();
        $backupService = new DatabaseBackupService();
        $usersCount = (int) User::find()->count();
        $locationsCount = (int) Location::find()->count();
        $activeUsersCount = (int) User::find()->where(['is_active' => 1])->count();
        $newsCount = (int) News::find()->count();
        return $this->success([
            'usersCount' => $usersCount,
            'locationsCount' => $locationsCount,
            'activeUsersCount' => $activeUsersCount,
            'newsCount' => $newsCount,
            'backup' => [
                'recentBackups' => $backupService->getRecentBackups(),
                'directory' => $backupService->getBackupDirectory(),
                'retentionDays' => $backupService->getRetentionDays(),
                'scheduleLabel' => $backupService->getScheduleLabel(),
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/db-backup
     */
    public function actionDbBackup()
    {
        $this->requireAdmin();

        try {
            $result = (new DatabaseBackupService())->createBackup();
            $response = Yii::$app->response;
            $response->format = Response::FORMAT_RAW;
            return $response->sendFile($result['path'], $result['filename']);
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $this->error('Не удалось создать резервную копию базы данных: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/v1/admin/users — список пользователей.
     */
    public function actionUsers(): array
    {
        $this->requireAdminOrManager();
        if (Yii::$app->request->isPost) {
            return $this->actionCreateUser();
        }
        $currentUser = Yii::$app->user->identity;
        $isManager = $currentUser->isTerritorialManagerOnly();

        $query = User::find()->with('location')->orderBy(['created_at' => SORT_DESC]);
        if ($isManager) {
            $managerLocations = $this->getManagerLocationIds();
            if (empty($managerLocations)) {
                return $this->success(['users' => [], 'isManager' => true, 'managerLocations' => [], 'locationsOptions' => []]);
            }
            $query->andWhere(['location_id' => $managerLocations]);
        }

        $users = $query->all();
        $list = [];
        foreach ($users as $u) {
            $list[] = $this->serializeUserForAdmin($u);
        }
        $managerLocations = $isManager ? $this->getManagerLocationIds() : [];
        $locationsOptions = $this->getLocationsForUserForm();
        return $this->success([
            'users' => $list,
            'isManager' => $isManager,
            'managerLocations' => $managerLocations,
            'locationsOptions' => $locationsOptions,
            'canManageSuperAccess' => $currentUser->isMainAdmin(),
        ]);
    }

    /**
     * GET /api/v1/admin/users/<id> — один пользователь.
     * PUT — обновление, DELETE — удаление (диспатч внутри).
     */
    public function actionUser($id): array
    {
        if (Yii::$app->request->isPut || Yii::$app->request->isPatch) {
            return $this->actionUpdateUser($id);
        }
        if (Yii::$app->request->isDelete) {
            return $this->actionDeleteUser($id);
        }
        $this->requireAdminOrManager();
        $user = User::find()->with('location')->where(['id' => (int) $id])->one();
        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }
        $currentUser = Yii::$app->user->identity;
        $isManager = $currentUser->isTerritorialManagerOnly();
        if ($isManager) {
            $managerLocations = $this->getManagerLocationIds();
            if (!in_array($user->location_id, $managerLocations)) {
                Yii::$app->response->statusCode = 403;
                return $this->error('Нет доступа к этому пользователю.', [], 403);
            }
        }
        $locationsOptions = $this->getLocationsForUserForm();
        return $this->success([
            'user' => $this->serializeUserForAdmin($user, true),
            'locationsOptions' => $locationsOptions,
            'canManageSuperAccess' => $currentUser->isMainAdmin(),
        ]);
    }

    /**
     * POST /api/v1/admin/users — создание пользователя (вызывается из actionUsers при POST).
     */
    public function actionCreateUser(): array
    {
        $this->requireAdminOrManager();
        $model = new UserCreateForm();
        $body = Yii::$app->request->getBodyParams();
        $model->load($body, '');
        $model->phone = $body['phone'] ?? '';
        $model->email = $body['email'] ?? '';
        $model->password = $body['password'] ?? '';
        $model->confirm_password = $body['confirmPassword'] ?? $body['confirm_password'] ?? '';
        $model->position = $body['position'] ?? '';
        $model->location_id = isset($body['locationId']) ? (int) $body['locationId'] : (isset($body['location_id']) ? (int) $body['location_id'] : null);
        $model->first_name = $body['firstName'] ?? $body['first_name'] ?? '';
        $model->last_name = $body['lastName'] ?? $body['last_name'] ?? '';
        $model->is_active = isset($body['isActive']) ? (int) $body['isActive'] : (isset($body['is_active']) ? (int) $body['is_active'] : 1);

        $currentUser = Yii::$app->user->identity;
        if ($currentUser->isTerritorialManagerOnly()) {
            $allowedPositions = ['location_manager', 'senior_teamaker', 'teamaker', 'trainee'];
            if (!in_array($model->position, $allowedPositions)) {
                return $this->error('Управляющий может создавать пользователей только до уровня "Менеджер точки".', ['position' => ['Недопустимая должность']], 422);
            }
            $managerLocations = $this->getManagerLocationIds();
            if ($model->location_id === null || !in_array($model->location_id, $managerLocations)) {
                return $this->error('Необходимо выбрать точку из ваших точек.', ['locationId' => ['Выберите точку']], 422);
            }
        }

        if (!$model->validate()) {
            return $this->error(implode(' ', $model->getFirstErrors()) ?: 'Ошибка валидации', $model->errors, 422);
        }
        $user = $model->save();
        if (!$user) {
            return $this->error(implode(' ', $model->getFirstErrors()) ?: 'Ошибка сохранения', $model->errors, 422);
        }
        // Начальная запись в истории должностей (для расчёта зарплаты по дате)
        $effectiveFrom = date('Y-m-d', $user->created_at ? strtotime($user->created_at) : time());
        $history = new UserPositionHistory([
            'user_id' => $user->id,
            'position' => $user->position,
            'effective_from' => $effectiveFrom,
        ]);
        $history->save(false);
        if ($currentUser->isAdmin() && $user->position === 'manager') {
            $this->syncManagerLocationsForAdmin($user->id, $body);
        }
        if ($currentUser->isMainAdmin()) {
            $this->applySuperAccessFromBody($user, $body);
            $user->save(false);
        }
        return $this->success(['user' => $this->serializeUserForAdmin($user, true)], 'Пользователь создан.');
    }

    /**
     * PUT /api/v1/admin/users/<id> — обновление пользователя.
     */
    public function actionUpdateUser($id): array
    {
        $this->requireAdminOrManager();
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }
        $currentUser = Yii::$app->user->identity;
        $isManager = $currentUser->isTerritorialManagerOnly();
        if ($isManager) {
            $managerLocations = $this->getManagerLocationIds();
            if (!in_array($user->location_id, $managerLocations)) {
                Yii::$app->response->statusCode = 403;
                return $this->error('Нет доступа.', [], 403);
            }
        }

        $model = new UserUpdateForm();
        $model->loadUser($user);
        $body = Yii::$app->request->getBodyParams();
        $model->phone = $body['phone'] ?? $model->phone;
        $model->email = $body['email'] ?? $model->email;
        $model->position = $body['position'] ?? $model->position;
        $model->location_id = isset($body['locationId']) ? (int) $body['locationId'] : (isset($body['location_id']) ? (int) $body['location_id'] : $model->location_id);
        $model->first_name = $body['firstName'] ?? $model->first_name;
        $model->last_name = $body['lastName'] ?? $model->last_name;
        $model->birthday = $body['birthday'] ?? $model->birthday;
        $model->telegram = $body['telegram'] ?? $model->telegram;
        $model->certification_date = $body['certificationDate'] ?? $body['certification_date'] ?? $model->certification_date;
        $model->is_active = isset($body['isActive']) ? (int) $body['isActive'] : (isset($body['is_active']) ? (int) $body['is_active'] : $model->is_active);
        $model->new_password = $body['newPassword'] ?? $body['new_password'] ?? '';
        $model->confirm_password = $body['confirmPassword'] ?? $body['confirm_password'] ?? '';

        $oldPosition = $user->position;
        $positionEffectiveFrom = null;
        if (isset($body['positionEffectiveFrom']) && $body['positionEffectiveFrom'] !== '') {
            $positionEffectiveFrom = $body['positionEffectiveFrom'];
        }
        if (isset($body['position_effective_from']) && $body['position_effective_from'] !== '') {
            $positionEffectiveFrom = $body['position_effective_from'];
        }

        if (!$model->validate()) {
            return $this->error(implode(' ', $model->getFirstErrors()) ?: 'Ошибка валидации', $model->errors, 422);
        }
        if (!$model->save()) {
            return $this->error('Ошибка сохранения', $model->errors, 422);
        }

        // При смене должности — запись в историю (для расчёта зарплаты до/после даты повышения/понижения)
        if ($oldPosition !== $user->position) {
            $effectiveFrom = $positionEffectiveFrom ?: date('Y-m-d');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $effectiveFrom)) {
                $history = new UserPositionHistory([
                    'user_id' => $user->id,
                    'position' => $user->position,
                    'effective_from' => $effectiveFrom,
                ]);
                $history->save(false);
            }
        }

        if ($currentUser->isAdmin()) {
            if ($user->position === 'manager') {
                if (array_key_exists('managedLocationIds', $body) || array_key_exists('managed_location_ids', $body)) {
                    $this->syncManagerLocationsForAdmin(
                        $user->id,
                        $body['managedLocationIds'] ?? $body['managed_location_ids'] ?? []
                    );
                }
            } else {
                ManagerLocation::deleteAll(['manager_id' => $user->id]);
            }
        }

        if ($currentUser->isMainAdmin() && $user->id !== $currentUser->id) {
            $this->applySuperAccessFromBody($user, $body);
            $user->save(false);
        }

        return $this->success(['user' => $this->serializeUserForAdmin($user, true)], 'Пользователь обновлён.');
    }

    /**
     * DELETE /api/v1/admin/users/<id>
     */
    public function actionDeleteUser($id): array
    {
        $this->requireAdminOrManager();
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }
        if ($user->id == Yii::$app->user->id) {
            return $this->error('Нельзя удалить самого себя.', [], 403);
        }
        if ($user->isMainAdmin() || ((bool) $user->is_admin && !$user->hasSuperAccess())) {
            return $this->error('Нельзя удалить администратора.', [], 403);
        }
        $currentUser = Yii::$app->user->identity;
        $isManager = $currentUser->isTerritorialManagerOnly();
        if ($isManager) {
            $managerLocations = $this->getManagerLocationIds();
            if (!in_array($user->location_id, $managerLocations)) {
                Yii::$app->response->statusCode = 403;
                return $this->error('Нет доступа.', [], 403);
            }
        }
        $user->delete();
        return $this->success([], 'Пользователь удалён.');
    }

    /**
     * GET /api/v1/admin/locations — список. POST — создание (диспатч).
     */
    public function actionLocations(): array
    {
        $this->requireAdmin();
        if (Yii::$app->request->isPost) {
            return $this->actionCreateLocation();
        }
        $locations = Location::find()->orderBy(['name' => SORT_ASC])->all();
        $list = [];
        foreach ($locations as $loc) {
            $list[] = [
                'id' => (int) $loc->id,
                'name' => $loc->name,
                'iikoName' => $loc->hasAttribute('iiko_name') ? ($loc->iiko_name ?: null) : null,
                'address' => $loc->address,
                'phone' => $loc->phone,
                'isActive' => (bool) $loc->is_active,
                'createdAt' => $loc->created_at,
            ];
        }
        return $this->success(['locations' => $list]);
    }

    /**
     * GET /api/v1/admin/analytics-locations — список локаций для карты аналитики (админ — все, менеджер — свои).
     */
    public function actionAnalyticsLocations(): array
    {
        $this->requireAdminOrManager();
        $query = Location::find()->orderBy(['name' => SORT_ASC]);
        $managerIds = $this->getManagerLocationIds();
        if (!empty($managerIds)) {
            $query->andWhere(['id' => $managerIds]);
        }
        $locations = $query->all();
        $list = [];
        foreach ($locations as $loc) {
            $list[] = [
                'id' => (int) $loc->id,
                'name' => $loc->name,
                'address' => $loc->address,
            ];
        }
        return $this->success(['locations' => $list]);
    }

    /**
     * GET /api/v1/admin/locations/<id>. PUT/PATCH — обновление, DELETE — удаление.
     */
    public function actionLocation($id): array
    {
        if (Yii::$app->request->isPut || Yii::$app->request->isPatch) {
            return $this->actionUpdateLocation($id);
        }
        if (Yii::$app->request->isDelete) {
            return $this->actionDeleteLocation($id);
        }
        $this->requireAdmin();
        $location = Location::findOne($id);
        if (!$location) {
            throw new NotFoundHttpException('Точка не найдена.');
        }
        return $this->success([
            'id' => (int) $location->id,
            'name' => $location->name,
            'iikoName' => $location->hasAttribute('iiko_name') ? ($location->iiko_name ?: null) : null,
            'address' => $location->address,
            'phone' => $location->phone,
            'isActive' => (bool) $location->is_active,
            'createdAt' => $location->created_at,
        ]);
    }

    /**
     * POST /api/v1/admin/locations (вызывается из actionLocations при POST).
     */
    public function actionCreateLocation(): array
    {
        $this->requireAdmin();
        $location = new Location();
        $body = Yii::$app->request->getBodyParams();
        $location->name = $body['name'] ?? '';
        $location->address = $body['address'] ?? '';
        $location->phone = $body['phone'] ?? '';
        if ($location->hasAttribute('iiko_name')) {
            $location->iiko_name = array_key_exists('iikoName', $body)
                ? trim((string) ($body['iikoName'] ?? ''))
                : (array_key_exists('iiko_name', $body) ? trim((string) ($body['iiko_name'] ?? '')) : null);
            if ($location->iiko_name === '') {
                $location->iiko_name = null;
            }
        }
        $location->is_active = isset($body['isActive']) ? (int) $body['isActive'] : (isset($body['is_active']) ? (int) $body['is_active'] : 1);
        if (!$location->validate()) {
            return $this->error(implode(' ', $location->getFirstErrors()) ?: 'Ошибка валидации', $location->errors, 422);
        }
        $location->save(false);
        return $this->success(['location' => [
            'id' => (int) $location->id,
            'name' => $location->name,
            'iikoName' => $location->hasAttribute('iiko_name') ? ($location->iiko_name ?: null) : null,
            'address' => $location->address,
            'phone' => $location->phone,
            'isActive' => (bool) $location->is_active,
        ]], 'Точка создана.');
    }

    /**
     * PUT /api/v1/admin/locations/<id> (вызывается из actionLocation при PUT).
     */
    public function actionUpdateLocation($id): array
    {
        $this->requireAdmin();
        $location = Location::findOne($id);
        if (!$location) {
            throw new NotFoundHttpException('Точка не найдена.');
        }
        $body = Yii::$app->request->getBodyParams();
        $location->name = $body['name'] ?? $location->name;
        $location->address = $body['address'] ?? $location->address;
        $location->phone = $body['phone'] ?? $location->phone;
        if ($location->hasAttribute('iiko_name')) {
            if (array_key_exists('iikoName', $body) || array_key_exists('iiko_name', $body)) {
                $raw = array_key_exists('iikoName', $body) ? ($body['iikoName'] ?? '') : ($body['iiko_name'] ?? '');
                $location->iiko_name = trim((string) $raw) !== '' ? trim((string) $raw) : null;
            }
        }
        $location->is_active = isset($body['isActive']) ? (int) $body['isActive'] : (isset($body['is_active']) ? (int) $body['is_active'] : $location->is_active);
        if (!$location->validate()) {
            return $this->error(implode(' ', $location->getFirstErrors()) ?: 'Ошибка валидации', $location->errors, 422);
        }
        $location->save(false);
        return $this->success(['location' => [
            'id' => (int) $location->id,
            'name' => $location->name,
            'iikoName' => $location->hasAttribute('iiko_name') ? ($location->iiko_name ?: null) : null,
            'address' => $location->address,
            'phone' => $location->phone,
            'isActive' => (bool) $location->is_active,
        ]], 'Точка обновлена.');
    }

    /**
     * DELETE /api/v1/admin/locations/<id> (вызывается из actionLocation при DELETE).
     */
    public function actionDeleteLocation($id): array
    {
        $this->requireAdmin();
        $location = Location::findOne($id);
        if (!$location) {
            throw new NotFoundHttpException('Точка не найдена.');
        }
        $location->delete();
        return $this->success([], 'Точка удалена.');
    }

    /**
     * GET /api/v1/admin/news — список новостей для админки.
     * POST /api/v1/admin/news — создание новости.
     */
    public function actionNews(): array
    {
        $this->requireAdmin();

        if (Yii::$app->request->isPost) {
            return $this->actionCreateNews();
        }

        $items = News::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(200)
            ->all();

        $list = [];
        foreach ($items as $n) {
            $list[] = [
                'id' => (int)$n->id,
                'title' => $n->title,
                'body' => $n->body,
                'isPublished' => (bool)$n->is_published,
                'createdAt' => $n->created_at,
                'publishedAt' => $n->published_at,
            ];
        }

        return $this->success(['items' => $list]);
    }

    /**
     * POST /api/v1/admin/news
     */
    public function actionCreateNews(): array
    {
        $this->requireAdmin();

        $body = Yii::$app->request->getBodyParams();
        $model = new News();
        $model->title = trim((string)($body['title'] ?? ''));
        $model->body = (string)($body['body'] ?? '');
        $model->author_id = (int)Yii::$app->user->id;
        $model->is_published = (int)($body['isPublished'] ?? 1);
        $model->published_at = $model->is_published ? date('Y-m-d H:i:s') : null;

        if (!$model->validate()) {
            return $this->error('Ошибка валидации', $model->errors, 422);
        }
        if (!$model->save(false)) {
            return $this->error('Не удалось сохранить новость', [], 500);
        }

        if ($model->is_published) {
            (new TelegramNotificationService())->onNewsPublished($model);
        }

        return $this->success([
            'item' => [
                'id' => (int)$model->id,
                'title' => $model->title,
                'body' => $model->body,
                'isPublished' => (bool)$model->is_published,
                'createdAt' => $model->created_at,
                'publishedAt' => $model->published_at,
            ],
        ], 'Новость создана.');
    }

    /**
     * GET /api/v1/admin/news/<id>
     * PUT/PATCH /api/v1/admin/news/<id>
     * DELETE /api/v1/admin/news/<id>
     */
    public function actionNewsItem(int $id): array
    {
        $this->requireAdmin();

        $model = News::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Новость не найдена.');
        }

        $request = Yii::$app->request;

        if ($request->isDelete) {
            $model->delete();
            return $this->success([], 'Новость удалена.');
        }

        if ($request->isPut || $request->isPatch) {
            $body = $request->getBodyParams();
            $wasPublished = (bool) $model->is_published;
            if (array_key_exists('title', $body)) {
                $model->title = trim((string) $body['title']);
            }
            if (array_key_exists('body', $body)) {
                $model->body = (string) $body['body'];
            }
            if (array_key_exists('isPublished', $body)) {
                $isPublished = (int) $body['isPublished'];
                // если новость только что публикуем — проставляем published_at
                if (!$model->is_published && $isPublished) {
                    $model->published_at = date('Y-m-d H:i:s');
                }
                $model->is_published = $isPublished;
                // если снимаем с публикации — published_at можно обнулить
                if (!$isPublished) {
                    $model->published_at = null;
                }
            }

            if (!$model->validate()) {
                return $this->error('Ошибка валидации', $model->errors, 422);
            }
            if (!$model->save(false)) {
                return $this->error('Не удалось сохранить новость', [], 500);
            }

            if ($model->is_published && !$wasPublished) {
                (new TelegramNotificationService())->onNewsPublished($model);
            }

            return $this->success([
                'item' => [
                    'id' => (int)$model->id,
                    'title' => $model->title,
                    'body' => $model->body,
                    'isPublished' => (bool)$model->is_published,
                    'createdAt' => $model->created_at,
                    'publishedAt' => $model->published_at,
                ],
            ], 'Новость обновлена.');
        }

        // GET
        return $this->success([
            'item' => [
                'id' => (int)$model->id,
                'title' => $model->title,
                'body' => $model->body,
                'isPublished' => (bool)$model->is_published,
                'createdAt' => $model->created_at,
                'publishedAt' => $model->published_at,
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/news/upload-image
     * Загрузка изображения для вставки в новость (multipart/form-data, поле "file").
     * Возвращает { url } — полный URL для вставки в контент.
     */
    public function actionNewsUploadImage(): array
    {
        $this->requireAdmin();

        $file = \yii\web\UploadedFile::getInstanceByName('file');
        if (!$file || !$file->tempName) {
            return $this->error('Файл не загружен', [], 422);
        }
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->type, $allowed, true)) {
            return $this->error('Допустимы только изображения (JPEG, PNG, GIF, WebP)', [], 422);
        }
        $ext = strtolower(pathinfo($file->name, PATHINFO_EXTENSION)) ?: 'jpg';
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $ext = 'jpg';
        }
        $dir = Yii::getAlias('@webroot') . '/uploads/news';
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                return $this->error('Не удалось создать каталог загрузок. Проверьте права на web/uploads (chown www-data:www-data, chmod 755).', [], 500);
            }
        }
        if (!is_writable($dir)) {
            return $this->error('Каталог web/uploads/news недоступен для записи. Выполните на сервере: chown -R www-data:www-data web/uploads && chmod -R 755 web/uploads', [], 500);
        }
        $name = sprintf('%s_%s.%s', date('YmdHis'), substr(md5(uniqid((string)mt_rand(), true)), 0, 8), $ext);
        $path = $dir . '/' . $name;
        if (!$file->saveAs($path)) {
            return $this->error('Не удалось сохранить файл (Permission denied). Выполните: chown -R www-data:www-data web/uploads && chmod -R 755 web/uploads', [], 500);
        }
        $baseUrl = rtrim(Yii::$app->request->hostInfo, '/');
        $url = $baseUrl . '/uploads/news/' . $name;
        return $this->success(['url' => $url]);
    }

    /**
     * GET /api/v1/admin/push/stats
     */
    public function actionPushStats(): array
    {
        $this->requireAdmin();
        $service = new PushNotificationService();
        $stats = $service->getStats();

        return $this->success($stats);
    }

    /**
     * POST /api/v1/admin/push/send
     * Body: { title, body, url?, tag?, requireInteraction?, mode: self|all }
     */
    public function actionPushSend(): array
    {
        $this->requireAdmin();

        $body = Yii::$app->request->getBodyParams();
        $title = trim((string)($body['title'] ?? ''));
        $message = trim((string)($body['body'] ?? ''));
        $url = trim((string)($body['url'] ?? ''));
        $tag = trim((string)($body['tag'] ?? 'admin-news'));
        $mode = trim((string)($body['mode'] ?? 'self'));
        $requireInteraction = (bool)($body['requireInteraction'] ?? false);

        if ($title === '' || $message === '') {
            return $this->error('Заполните заголовок и текст уведомления.', [], 422);
        }
        if (!in_array($mode, ['self', 'all'], true)) {
            return $this->error('Недопустимый режим рассылки.', [], 422);
        }

        $payload = [
            'title' => $title,
            'body' => $message,
            'tag' => $tag !== '' ? $tag : 'admin-news',
            'requireInteraction' => $requireInteraction,
        ];
        $normalizedUrl = $this->normalizePushPath($url);
        if ($normalizedUrl === null) {
            return $this->error('Разрешены только внутренние пути приложения, например /learning.', [], 422);
        }
        if ($normalizedUrl !== '') {
            $payload['url'] = $normalizedUrl;
        }

        $service = new PushNotificationService();

        try {
            if ($mode === 'self') {
                $result = $service->sendToUser((int)Yii::$app->user->id, $payload);
            } else {
                $result = $service->sendToAll($payload);
            }
        } catch (\Throwable $e) {
            Yii::error('Push send failed: ' . $e->getMessage(), __METHOD__);
            return $this->error($e->getMessage() ?: 'Ошибка отправки push.', [], 500);
        }

        return $this->success(
            [
                'mode' => $mode,
                'result' => $result,
                'subscriptionsCount' => (int)PushSubscription::find()->count(),
            ],
            'Отправка завершена.'
        );
    }

    // ─── Profile Card Templates CRUD ─────────────────────────────────

    /**
     * GET /api/v1/admin/profile-cards
     * POST — создание нового шаблона.
     */
    public function actionProfileCards(): array
    {
        $this->requireAdmin();

        if (Yii::$app->request->isPost) {
            return $this->actionCreateProfileCard();
        }

        $items = ProfileCardTemplate::find()->orderBy(['id' => SORT_DESC])->all();
        $list = [];
        foreach ($items as $tpl) {
            $list[] = $this->serializeCardTemplate($tpl);
        }
        return $this->success(['items' => $list]);
    }

    private function actionCreateProfileCard(): array
    {
        $body = Yii::$app->request->getBodyParams();
        $model = new ProfileCardTemplate();
        $model->name = trim((string)($body['name'] ?? ''));
        $model->code = trim((string)($body['code'] ?? ''));
        $model->description = trim((string)($body['description'] ?? ''));
        $model->css_class = trim((string)($body['cssClass'] ?? $body['css_class'] ?? ''));
        $model->is_active = (int)($body['isActive'] ?? 1);

        if (!empty($body['styleConfig']) && is_array($body['styleConfig'])) {
            $model->setStyleConfigFromArray($body['styleConfig']);
        } elseif (!empty($body['style_config']) && is_string($body['style_config'])) {
            $model->style_config = $body['style_config'];
        }

        if (!$model->validate()) {
            return $this->error(implode(' ', $model->getFirstErrors()), $model->errors, 422);
        }
        if (!$model->save(false)) {
            return $this->error('Не удалось создать шаблон', [], 500);
        }
        return $this->success(['item' => $this->serializeCardTemplate($model)], 'Шаблон создан.');
    }

    /**
     * GET/PUT/DELETE /api/v1/admin/profile-cards/<id>
     */
    public function actionProfileCard(int $id): array
    {
        $this->requireAdmin();

        $model = ProfileCardTemplate::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Шаблон не найден.');
        }

        if (Yii::$app->request->isDelete) {
            $model->delete();
            return $this->success([], 'Шаблон удалён.');
        }

        if (Yii::$app->request->isPut || Yii::$app->request->isPatch) {
            $body = Yii::$app->request->getBodyParams();
            if (array_key_exists('name', $body)) $model->name = trim((string)$body['name']);
            if (array_key_exists('code', $body)) $model->code = trim((string)$body['code']);
            if (array_key_exists('description', $body)) $model->description = trim((string)$body['description']);
            if (array_key_exists('cssClass', $body) || array_key_exists('css_class', $body)) {
                $model->css_class = trim((string)($body['cssClass'] ?? $body['css_class']));
            }
            if (array_key_exists('isActive', $body) || array_key_exists('is_active', $body)) {
                $model->is_active = (int)($body['isActive'] ?? $body['is_active']);
            }
            if (!empty($body['styleConfig']) && is_array($body['styleConfig'])) {
                $model->setStyleConfigFromArray($body['styleConfig']);
            } elseif (array_key_exists('style_config', $body)) {
                $model->style_config = $body['style_config'];
            }
            if (array_key_exists('previewImage', $body) || array_key_exists('preview_image', $body)) {
                $model->preview_image = $body['previewImage'] ?? $body['preview_image'];
            }

            if (!$model->validate()) {
                return $this->error(implode(' ', $model->getFirstErrors()), $model->errors, 422);
            }
            if (!$model->save(false)) {
                return $this->error('Не удалось сохранить шаблон', [], 500);
            }
            return $this->success(['item' => $this->serializeCardTemplate($model)], 'Шаблон обновлён.');
        }

        return $this->success(['item' => $this->serializeCardTemplate($model)]);
    }

    /**
     * POST /api/v1/admin/profile-cards/upload-bg — загрузка фонового PNG.
     */
    public function actionProfileCardUploadBg(): array
    {
        $this->requireAdmin();

        $file = \yii\web\UploadedFile::getInstanceByName('file');
        if (!$file || !$file->tempName) {
            return $this->error('Файл не загружен', [], 422);
        }
        if (!in_array($file->type, ['image/png'], true)) {
            return $this->error('Допустим только формат PNG', [], 422);
        }
        $ext = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
        if ($ext !== 'png') {
            return $this->error('Допустим только формат PNG', [], 422);
        }

        $dir = Yii::getAlias('@webroot') . '/uploads/profile-cards';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return $this->error('Не удалось создать каталог загрузок', [], 500);
        }
        if (!is_writable($dir)) {
            return $this->error('Каталог web/uploads/profile-cards недоступен для записи', [], 500);
        }

        $name = sprintf('bg_%s_%s.png', date('YmdHis'), substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
        $path = $dir . '/' . $name;
        if (!$file->saveAs($path)) {
            return $this->error('Не удалось сохранить файл', [], 500);
        }
        $baseUrl = rtrim(Yii::$app->request->hostInfo, '/');
        $url = $baseUrl . '/uploads/profile-cards/' . $name;
        return $this->success(['url' => $url]);
    }

    private function serializeCardTemplate(ProfileCardTemplate $tpl): array
    {
        return [
            'id' => (int) $tpl->id,
            'name' => $tpl->name,
            'code' => $tpl->code,
            'description' => $tpl->description ?? '',
            'cssClass' => $tpl->css_class ?? '',
            'previewImage' => $tpl->preview_image ?? '',
            'isActive' => (bool) $tpl->is_active,
            'styleConfig' => $tpl->getStyleConfigArray(),
            'createdAt' => $tpl->created_at,
            'updatedAt' => $tpl->updated_at,
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    private function applySuperAccessFromBody(User $user, array $body): void
    {
        if (!$user->hasAttribute('has_super_access')) {
            return;
        }
        if ($user->isMainAdmin() || (bool) $user->is_admin) {
            return;
        }
        if (!array_key_exists('hasSuperAccess', $body) && !array_key_exists('has_super_access', $body)) {
            return;
        }
        $raw = array_key_exists('hasSuperAccess', $body)
            ? $body['hasSuperAccess']
            : $body['has_super_access'];
        $user->has_super_access = $raw ? 1 : 0;
    }

    private function serializeUserForAdmin(User $u, bool $full = false): array
    {
        $data = [
            'id' => (int) $u->id,
            'firstName' => $u->first_name,
            'lastName' => $u->last_name,
            'fullName' => $u->getFullName(),
            'email' => $u->email,
            'phone' => $u->phone,
            'position' => $u->position,
            'positionLabel' => $u->getPositionLabel(),
            'isAdmin' => $u->isAdmin(),
            'hasSuperAccess' => $u->hasSuperAccess(),
            'isMainAdmin' => $u->isMainAdmin(),
            'isActive' => (bool) $u->is_active,
            'locationId' => $u->location_id ? (int) $u->location_id : null,
            'locationName' => $u->location ? $u->location->name : null,
        ];
        if ($full) {
            $data['birthday'] = $u->hasAttribute('birthday') ? $u->birthday : null;
            $data['telegram'] = $u->hasAttribute('telegram') ? $u->telegram : null;
            $data['certificationDate'] = $u->hasAttribute('certification_date') ? $u->certification_date : null;
            $data['createdAt'] = $u->created_at;
            $data['updatedAt'] = $u->updated_at;
            $data['managedLocationIds'] = $u->position === 'manager'
                ? array_map(
                    'intval',
                    (new \yii\db\Query())
                        ->from('manager_locations')
                        ->where(['manager_id' => $u->id])
                        ->select('location_id')
                        ->column()
                )
                : [];
        }
        return $data;
    }

    private function getLocationsForUserForm(): array
    {
        $currentUser = Yii::$app->user->identity;
        $isManager = $currentUser->isTerritorialManagerOnly();
        if ($isManager) {
            $ids = $this->getManagerLocationIds();
            $locations = Location::find()->where(['id' => $ids])->orderBy('name')->all();
        } else {
            $locations = Location::find()->orderBy('name')->all();
        }
        $out = [];
        foreach ($locations as $loc) {
            $out[] = ['id' => (int) $loc->id, 'name' => $loc->name];
        }
        return $out;
    }

    /**
     * Заменяет набор точек для территориального управляющего (только вызов из методов, где уже проверен isAdmin).
     *
     * @param int $managerId
     * @param mixed $rawIds массив id точек
     */
    /**
     * GET /api/v1/admin/position-rates
     * PUT /api/v1/admin/position-rates — body: { rates: [{ code, hourly_rate }] }
     */
    public function actionPositionRates(): array
    {
        $this->requireAdmin();
        if (Yii::$app->request->isGet) {
            $rates = PositionRate::find()->orderBy(['name' => SORT_ASC])->all();
            $list = [];
            foreach ($rates as $rate) {
                $list[] = [
                    'id' => (int) $rate->id,
                    'code' => $rate->code,
                    'name' => $rate->name,
                    'hourly_rate' => (float) $rate->hourly_rate,
                ];
            }

            return $this->success(['rates' => $list]);
        }

        $body = Yii::$app->request->getBodyParams();
        if (empty($body) && Yii::$app->request->getRawBody()) {
            $decoded = json_decode(Yii::$app->request->getRawBody(), true);
            $body = is_array($decoded) ? $decoded : [];
        }
        $items = $body['rates'] ?? [];
        if (!is_array($items)) {
            return $this->error('Укажите массив rates.', [], 422);
        }

        $db = Yii::$app->db;
        $db->transaction(function () use ($items) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $code = trim((string) ($item['code'] ?? ''));
                if ($code === '') {
                    continue;
                }
                $model = PositionRate::findOne(['code' => $code]);
                if (!$model) {
                    continue;
                }
                if (isset($item['hourly_rate'])) {
                    $model->hourly_rate = max(0, (float) str_replace(',', '.', (string) $item['hourly_rate']));
                }
                if (isset($item['name']) && trim((string) $item['name']) !== '') {
                    $model->name = trim((string) $item['name']);
                }
                $model->save(false);
            }
        });

        return $this->actionPositionRates();
    }

    /**
     * GET /api/v1/admin/kro-bands
     * PUT /api/v1/admin/kro-bands — body: { bands: [{ min_percent, hourly_bonus }] }
     */
    public function actionKroBands(): array
    {
        $this->requireAdmin();
        if (Yii::$app->request->isGet) {
            $bands = KroBonusBand::find()
                ->orderBy(['sort_order' => SORT_DESC, 'min_percent' => SORT_DESC])
                ->asArray()
                ->all();
            if ($bands === []) {
                $bands = KroBonusBand::DEFAULT_BANDS;
            }

            return $this->success(['bands' => $bands]);
        }

        $body = Yii::$app->request->getBodyParams();
        if (empty($body) && Yii::$app->request->getRawBody()) {
            $decoded = json_decode(Yii::$app->request->getRawBody(), true);
            $body = is_array($decoded) ? $decoded : [];
        }
        $items = $body['bands'] ?? [];
        if (!is_array($items) || $items === []) {
            return $this->error('Укажите массив bands.', [], 422);
        }

        $db = Yii::$app->db;
        $db->transaction(function () use ($items) {
            KroBonusBand::deleteAll();
            $order = count($items) * 10;
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $band = new KroBonusBand();
                $band->min_percent = max(0, min(100, (float) str_replace(',', '.', (string) ($item['min_percent'] ?? 0))));
                $band->hourly_bonus = max(0, (float) str_replace(',', '.', (string) ($item['hourly_bonus'] ?? 0)));
                $band->sort_order = $order;
                $band->save(false);
                $order -= 10;
            }
        });

        return $this->actionKroBands();
    }

    /**
     * GET /api/v1/admin/location-kro?location_id=&year=&month=
     * POST /api/v1/admin/location-kro — body: { location_id, year, month, pass_percent }
     */
    public function actionLocationKro(): array
    {
        $this->requireAdmin();
        if (Yii::$app->request->isGet) {
            $locationId = (int) (Yii::$app->request->get('location_id') ?? 0);
            $year = (int) (Yii::$app->request->get('year') ?? date('Y'));
            $month = (int) (Yii::$app->request->get('month') ?? date('n'));
            if (!$locationId) {
                return $this->error('Укажите location_id.', [], 422);
            }
            $kro = LocationKro::getForLocationMonth($locationId, $year, $month);

            return $this->success([
                'location_id' => $locationId,
                'year' => $year,
                'month' => $month,
                'pass_percent' => $kro ? (float) $kro->pass_percent : null,
                'kro_bonus' => $kro ? KroBonusBand::passPercentToBonus((float) $kro->pass_percent) : 0,
            ]);
        }

        $body = Yii::$app->request->getBodyParams();
        if (empty($body) && Yii::$app->request->getRawBody()) {
            $decoded = json_decode(Yii::$app->request->getRawBody(), true);
            $body = is_array($decoded) ? $decoded : [];
        }
        $locationId = (int) ($body['location_id'] ?? 0);
        $year = (int) ($body['year'] ?? date('Y'));
        $month = (int) ($body['month'] ?? date('n'));
        $passPercent = isset($body['pass_percent']) ? (float) str_replace(',', '.', (string) $body['pass_percent']) : null;
        if (!$locationId) {
            return $this->error('Укажите точку.', [], 422);
        }
        if ($passPercent === null || $passPercent < 0 || $passPercent > 100) {
            return $this->error('Укажите процент прохождения от 0 до 100.', [], 422);
        }

        $kro = LocationKro::getForLocationMonth($locationId, $year, $month);
        if (!$kro) {
            $kro = new LocationKro();
            $kro->location_id = $locationId;
            $kro->year = $year;
            $kro->month = $month;
        }
        $kro->pass_percent = $passPercent;
        if (!$kro->save()) {
            return $this->error(implode(', ', $kro->getFirstErrors()), $kro->errors, 422);
        }

        return $this->success([
            'id' => $kro->id,
            'location_id' => $kro->location_id,
            'year' => $kro->year,
            'month' => $kro->month,
            'pass_percent' => (float) $kro->pass_percent,
            'kro_bonus' => KroBonusBand::passPercentToBonus((float) $kro->pass_percent),
        ]);
    }

    private function syncManagerLocationsForAdmin(int $managerId, $rawIds): void
    {
        if (!is_array($rawIds)) {
            $rawIds = [];
        }
        $ids = [];
        foreach ($rawIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        $ids = array_keys($ids);
        if ($ids !== []) {
            $existing = Location::find()->select('id')->where(['id' => $ids])->column();
            $ids = array_values(array_intersect($ids, array_map('intval', $existing)));
        }
        $db = Yii::$app->db;
        $db->transaction(function () use ($managerId, $ids) {
            ManagerLocation::deleteAll(['manager_id' => $managerId]);
            foreach ($ids as $locId) {
                $row = new ManagerLocation(['manager_id' => $managerId, 'location_id' => $locId]);
                $row->save(false);
            }
        });
    }
}
