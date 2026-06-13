<?php

namespace app\modules\api\controllers;

use app\models\Location;
use app\models\ManagerLocation;
use app\models\PhotoReportPhoto;
use app\services\TelegramNotificationService;
use Yii;
use yii\web\UploadedFile;

class PhotoReportController extends BaseApiController
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
            'locations' => ['GET'],
            'items'     => ['GET'],
            'photos'    => ['GET'],
            'upload'    => ['POST'],
            'delete'    => ['DELETE'],
            'overview'  => ['GET'],
        ];
    }

    private const CLOSING_ITEMS = [
        'shelves_syrups'        => 'Полки с сиропами',
        'freezer'               => 'Морозильная камера',
        'salad_containers'      => 'Салатницы и гастроёмкости без липких пятен',
        'write_off_sheet'       => 'Лист списаний',
        'fridge_cabinet'        => 'Шкафчик холодильника',
        'fridge_inside'         => 'Внутренность холодильника',
        'cashbox_rest_area'     => 'Касса с картой и зона отдыха',
        'microwave'             => 'Микроволновка внутри и снаружи',
        'cup_stand'             => 'Чёрная стойка для стаканов',
        'syrup_dispensers'      => 'Гейзеры от сиропов',
        'coffee_zone'           => 'Кофейная зона',
        'boilers_no_tea'        => 'Бойлеры без чая',
        'fruit_dispenser'       => 'Дозатор от фруктовницы',
        'tayashnitsa'           => 'Таяшница внутри и снаружи',
        'tapioshnitsa'          => 'Тапиошница внутри и снаружи',
        'waffle_iron'           => 'Вафельница внутри и снаружи',
        'kitchen_zone_fridge'   => 'Кухонная зона и холодильник',
        'blenders'              => 'Блендеры внутри и снаружи',
        'sinks'                 => 'Раковины',
        'sealing_machine'       => 'Запечатывающая машина',
        'siphon_nozzles'        => 'Носики у сифона',
        'locators'              => 'Локаторы',
        'heater'                => 'Подогрев',
        'hall'                  => 'Зал',
    ];

    private const OPENING_ITEMS = [
        'staff_clean_uniform'     => 'Все сотрудники на смене в чистой форме, сменной обуви',
        'clean_cashbox_zone'      => 'Фотография чистой кассовой зоны',
        'tvs_on'                  => 'Включённые телевизоры',
        'grater_zone_clean'       => 'Затирочная зона чиста на выдаче',
        'all_preparations'        => 'Наличие всех заготовок в достаточном количестве',
        'clean_bar'               => 'Фотография чистого бара',
        'boilers_clean'           => 'Бойлеры чистые и без разводов',
        'markings_fresh'          => 'Фотографии маркировки на свежих заготовках',
        'cleaning_after_workers'  => 'Уборка за работниками (за холодильником, полы и стены)',
        'ttk_prices'              => 'ТТК расценяты',
        'clean_windows_doors'     => 'Чистые окна и двери входные',
        'clean_showcase_cashbox'  => 'Чистая витрина, касса',
        'storage_no_boxes'        => 'Складское помещение без коробок, всё организовано',
        'coffee_zone_machine'     => 'Кофе зона (кофемашина чистая, детали)',
        'markings_all'            => 'Маркировки на заготовках, молочко, масло',
        'all_fridge_equipment'    => 'Фото всего холодильного оборудования',
        'clean_shelves'           => 'Чистые полки',
        'toilet_clean'            => 'Фото туалета, стены и двери чистые',
        'waffle_parchment'        => 'Вафельницы (чистый пергамент, снаружи)',
        'multicooker_microwave'   => 'Мультиварка и микроволновка внутри и снаружи',
        'clean_radiators'         => 'Чистые батареи',
        'staff_photo_shift'       => 'Фото сотрудников на смене (чистая форма, волосы убраны)',
        'trash_bins'              => 'Мусорные баки внутри и снаружи',
        'fan_spatulas'            => 'Вентилятор, лопатки и особенности',
        'clean_bar_zone'          => 'Фотография чистой барной зоны',
    ];

    private function getItems(string $type): array
    {
        return $type === 'closing' ? self::CLOSING_ITEMS : self::OPENING_ITEMS;
    }

    /**
     * Returns locations the current user has access to.
     */
    private function getAccessibleLocationIds(): array
    {
        return array_map(static function (Location $location): int {
            return (int) $location->id;
        }, $this->getUserLocations());
    }

    private function canAccessLocation(int $locationId): bool
    {
        return in_array($locationId, $this->getAccessibleLocationIds(), true);
    }

    private function getUserLocations(): array
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

    public function actionLocations(): array
    {
        $locations = $this->getUserLocations();
        $list = [];
        foreach ($locations as $loc) {
            $list[] = ['id' => (int)$loc->id, 'name' => $loc->name];
        }
        return $this->success(['locations' => $list]);
    }

    public function actionItems(): array
    {
        $type = Yii::$app->request->get('report_type', 'closing');
        if (!in_array($type, ['closing', 'opening'])) {
            return $this->error('Неверный тип отчёта.', [], 422);
        }
        $items = $this->getItems($type);
        $result = [];
        foreach ($items as $key => $label) {
            $result[] = ['key' => $key, 'label' => $label];
        }
        return $this->success(['items' => $result, 'report_type' => $type]);
    }

    public function actionPhotos(): array
    {
        $locationId = (int)Yii::$app->request->get('location_id', 0);
        $reportType = Yii::$app->request->get('report_type', 'closing');
        $reportDate = Yii::$app->request->get('report_date', date('Y-m-d'));

        if ($locationId <= 0) {
            return $this->error('Не указан location_id.', [], 422);
        }
        if (!in_array($reportType, ['closing', 'opening'])) {
            return $this->error('Неверный тип отчёта.', [], 422);
        }
        if (!$this->isValidReportDate($reportDate)) {
            return $this->error('Неверный формат report_date. Используйте YYYY-MM-DD.', [], 422);
        }
        if (!$this->canAccessLocation($locationId)) {
            return $this->error('Нет доступа к выбранной точке.', [], 403);
        }

        $this->cleanupExpiredSilent();

        $cutoff = date('Y-m-d H:i:s', strtotime('-48 hours'));
        $photos = PhotoReportPhoto::find()
            ->with('user')
            ->where([
                'location_id' => $locationId,
                'report_type' => $reportType,
                'report_date' => $reportDate,
            ])
            ->andWhere(['>=', 'created_at', $cutoff])
            ->orderBy(['item_key' => SORT_ASC, 'created_at' => SORT_DESC])
            ->all();

        $baseUrl = Yii::$app->params['frontendFileBaseUrl'] ?? Yii::$app->request->hostInfo;
        $grouped = [];
        foreach ($photos as $p) {
            $grouped[$p->item_key][] = [
                'id'        => (int)$p->id,
                'url'       => rtrim($baseUrl, '/') . '/' . ltrim($p->file_path, '/'),
                'file_name' => $p->file_name,
                'author'    => $p->user ? $p->user->getFullName() : '',
                'created_at'=> $p->created_at,
            ];
        }

        return $this->success(['photos' => $grouped, 'report_date' => $reportDate]);
    }

    public function actionUpload(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return $this->error('Не авторизован.', [], 401);

        $postSizeError = $this->checkPostSize();
        if ($postSizeError !== null) return $postSizeError;

        $locationId = (int)Yii::$app->request->post('location_id', 0);
        $reportType = Yii::$app->request->post('report_type', '');
        $itemKey    = Yii::$app->request->post('item_key', '');
        $reportDate = Yii::$app->request->post('report_date', date('Y-m-d'));

        if ($locationId <= 0 || !in_array($reportType, ['closing', 'opening']) || empty($itemKey)) {
            return $this->error('Не указаны обязательные параметры.', [], 422);
        }
        if (!$this->isValidReportDate($reportDate)) {
            return $this->error('Неверный формат report_date. Используйте YYYY-MM-DD.', [], 422);
        }
        if (!$this->canAccessLocation($locationId)) {
            return $this->error('Нет доступа к выбранной точке.', [], 403);
        }

        $items = $this->getItems($reportType);
        if (!isset($items[$itemKey])) {
            return $this->error('Неверный пункт отчёта.', [], 422);
        }

        $this->cleanupExpiredSilent();

        $cutoff = date('Y-m-d H:i:s', strtotime('-48 hours'));
        $existing = PhotoReportPhoto::find()->where([
            'location_id' => $locationId,
            'report_type' => $reportType,
            'item_key'    => $itemKey,
            'report_date' => $reportDate,
        ])->andWhere(['>=', 'created_at', $cutoff])->count();

        $files = UploadedFile::getInstancesByName('photos');
        if (empty($files)) {
            $files = UploadedFile::getInstancesByName('photos[]');
        }
        if (empty($files)) {
            return $this->error('Не прикреплены файлы.', [], 422);
        }

        foreach ($files as $file) {
            $fileErr = $this->getFileUploadError($file);
            if ($fileErr !== null) {
                return $this->error($fileErr, [], 422);
            }
        }

        if ($existing + count($files) > 5) {
            $canUpload = 5 - (int)$existing;
            return $this->error("Можно прикрепить ещё {$canUpload} фото (максимум 5).", [], 422);
        }

        $uploadDir = Yii::getAlias('@webroot/uploads/photo-reports');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $baseUrl  = Yii::$app->params['frontendFileBaseUrl'] ?? Yii::$app->request->hostInfo;
        $saved = [];
        foreach ($files as $file) {
            if ($file->error !== UPLOAD_ERR_OK) continue;

            $ext = $this->resolveImageExtension($file);
            if ($ext === null) {
                continue;
            }
            $safeName     = uniqid('pr_', true) . '.' . $ext;
            $relativePath = 'uploads/photo-reports/' . $safeName;
            $absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
            if (!$file->saveAs($absolutePath)) continue;

            $model = new PhotoReportPhoto();
            $model->location_id = $locationId;
            $model->user_id     = (int)$user->id;
            $model->report_type = $reportType;
            $model->item_key    = $itemKey;
            $model->report_date = $reportDate;
            $model->file_path   = $relativePath;
            $model->file_name   = $file->name;
            if ($model->save()) {
                $saved[] = [
                    'id'        => (int)$model->id,
                    'url'       => rtrim($baseUrl, '/') . '/' . ltrim($relativePath, '/'),
                    'file_name' => $file->name,
                    'author'    => $user->getFullName(),
                    'created_at'=> $model->created_at,
                ];
            }
        }

        if (empty($saved)) {
            return $this->error('Не удалось сохранить фотографии.', [], 500);
        }

        (new TelegramNotificationService())->onPhotoReportMaybeComplete(
            $locationId,
            $reportType,
            $reportDate,
            count($items)
        );

        return $this->success(['uploaded' => $saved]);
    }

    public function actionDelete(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return $this->error('Не авторизован.', [], 401);

        $id = (int)Yii::$app->request->get('id', 0);
        if ($id <= 0) {
            return $this->error('Не указан id фото.', [], 422);
        }

        $photo = PhotoReportPhoto::findOne($id);
        if (!$photo) {
            return $this->error('Фото не найдено.', [], 404);
        }

        $canDelete = $user->isAdmin()
            || (int)$photo->user_id === (int)$user->id
            || ($user->position === 'manager' && $this->canAccessLocation((int) $photo->location_id));

        if (!$canDelete) {
            return $this->error('Нет прав на удаление.', [], 403);
        }

        $absPath = Yii::getAlias('@webroot') . '/' . ltrim($photo->file_path, '/');
        if (file_exists($absPath)) {
            @unlink($absPath);
        }
        $photo->delete();

        return $this->success([], 'Фото удалено.');
    }

    /**
     * Admin overview: all locations with photo counts.
     */
    public function actionOverview(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user || !$user->isAdmin()) {
            return $this->error('Только для администратора.', [], 403);
        }
        $reportDate = Yii::$app->request->get('report_date', date('Y-m-d'));
        if (!$this->isValidReportDate($reportDate)) {
            return $this->error('Неверный формат report_date. Используйте YYYY-MM-DD.', [], 422);
        }

        $cutoff = date('Y-m-d H:i:s', strtotime('-48 hours'));
        $locations = Location::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();
        $baseUrl = Yii::$app->params['frontendFileBaseUrl'] ?? Yii::$app->request->hostInfo;

        $result = [];
        foreach ($locations as $loc) {
            $photos = PhotoReportPhoto::find()
                ->with('user')
                ->where([
                    'location_id' => $loc->id,
                    'report_type' => 'closing',
                    'report_date' => $reportDate,
                ])
                ->andWhere(['>=', 'created_at', $cutoff])
                ->orderBy(['report_type' => SORT_ASC, 'item_key' => SORT_ASC, 'created_at' => SORT_DESC])
                ->all();

            $closingItems = self::CLOSING_ITEMS;

            $closingData = [];
            foreach ($photos as $p) {
                $entry = [
                    'id'        => (int)$p->id,
                    'url'       => rtrim($baseUrl, '/') . '/' . ltrim($p->file_path, '/'),
                    'file_name' => $p->file_name,
                    'author'    => $p->user ? $p->user->getFullName() : '',
                    'created_at'=> $p->created_at,
                ];
                $closingData[$p->item_key][] = $entry;
            }

            $closingTotal  = count($closingItems);
            $closingFilled = count($closingData);

            $result[] = [
                'location'       => ['id' => (int)$loc->id, 'name' => $loc->name],
                'closing'        => ['filled' => $closingFilled, 'total' => $closingTotal, 'photos' => $closingData],
            ];
        }

        return $this->success([
            'overview'     => $result,
            'closingItems' => self::CLOSING_ITEMS,
            'report_date'  => $reportDate,
        ]);
    }

    private function cleanupExpiredSilent(): void
    {
        try {
            PhotoReportPhoto::cleanupExpired();
        } catch (\Throwable $e) {
            Yii::error('Photo cleanup error: ' . $e->getMessage(), __METHOD__);
        }
    }

    private function isValidReportDate(string $reportDate): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $reportDate);
        return $dt !== false && $dt->format('Y-m-d') === $reportDate;
    }

    private function resolveImageExtension(UploadedFile $file): ?string
    {
        $ext = strtolower((string)$file->getExtension());
        if ($ext !== '' && in_array($ext, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
            return $ext;
        }

        $mime = strtolower((string)$file->type);
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
