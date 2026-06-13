<?php

namespace app\commands;

use app\services\DatabaseBackupService;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Управление резервными копиями БД.
 */
class DbBackupController extends Controller
{
    public function actionIndex(): int
    {
        try {
            $result = (new DatabaseBackupService())->createBackup();
        } catch (\Throwable $e) {
            $this->stderr("Ошибка резервного копирования: {$e->getMessage()}\n");
            Yii::error($e, __METHOD__);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Резервная копия создана: {$result['filename']}\n");
        $this->stdout("Каталог: {$result['directory']}\n");
        $this->stdout("Удалено старых копий: {$result['deletedOldCount']}\n");

        return ExitCode::OK;
    }

    public function actionCleanup(): int
    {
        $deletedCount = (new DatabaseBackupService())->cleanupOldBackups();
        $this->stdout("Удалено старых копий: {$deletedCount}\n");

        return ExitCode::OK;
    }
}
