<?php

namespace app\commands;

use app\models\ShiftTaskPhoto;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Удаляет фото задач на смену старше 48 часов.
 *
 * Использование:
 *   php yii cleanup-shift-task-photos
 *
 * Рекомендуется добавить в cron:
 *   0 * * * * php /path/to/yii cleanup-shift-task-photos
 */
class CleanupShiftTaskPhotosController extends Controller
{
    public function actionIndex(): int
    {
        $count = ShiftTaskPhoto::cleanupExpired();
        $this->stdout("Удалено фото задач: {$count}\n");
        return ExitCode::OK;
    }
}
