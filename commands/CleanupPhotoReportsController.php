<?php

namespace app\commands;

use app\models\PhotoReportPhoto;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Удаляет фотоотчёты старше 48 часов.
 *
 * Использование:
 *   php yii cleanup-photo-reports
 *
 * Рекомендуется добавить в cron:
 *   0 * * * * php /path/to/yii cleanup-photo-reports
 */
class CleanupPhotoReportsController extends Controller
{
    public function actionIndex(): int
    {
        $count = PhotoReportPhoto::cleanupExpired();
        $this->stdout("Удалено фото: {$count}\n");
        return ExitCode::OK;
    }
}
