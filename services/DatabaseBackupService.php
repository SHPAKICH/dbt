<?php

namespace app\services;

use RuntimeException;
use Yii;
use yii\base\Component;
use yii\db\ColumnSchema;
use yii\db\Query;
use yii\helpers\FileHelper;

/**
 * Создаёт SQL-дампы MySQL и очищает старые резервные копии.
 */
class DatabaseBackupService extends Component
{
    private const BATCH_SIZE = 200;

    /**
     * Создать резервную копию БД.
     *
     * @return array<string, mixed>
     */
    public function createBackup(): array
    {
        $backupDirectory = $this->getBackupDirectory();
        FileHelper::createDirectory($backupDirectory);

        $databaseName = $this->getDatabaseName();
        $safeDatabaseName = preg_replace('/[^a-z0-9_-]+/i', '_', $databaseName) ?: 'database';
        $baseFileName = $safeDatabaseName . '_backup_' . date('Ymd_His') . '.sql';
        $sqlPath = $backupDirectory . DIRECTORY_SEPARATOR . $baseFileName;
        $tempPath = $sqlPath . '.tmp';

        $handle = fopen($tempPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Не удалось создать временный файл резервной копии.');
        }

        try {
            $this->writeDump($handle);
        } catch (\Throwable $e) {
            fclose($handle);
            @unlink($tempPath);
            throw $e;
        }

        fclose($handle);

        if (!@rename($tempPath, $sqlPath)) {
            @unlink($tempPath);
            throw new RuntimeException('Не удалось сохранить SQL-дамп базы данных.');
        }

        $finalPath = $sqlPath;
        if (function_exists('gzopen')) {
            $gzipPath = $sqlPath . '.gz';
            $this->compressFile($sqlPath, $gzipPath);
            @unlink($sqlPath);
            $finalPath = $gzipPath;
        }

        $deletedCount = $this->cleanupOldBackups();

        return [
            'path' => $finalPath,
            'filename' => basename($finalPath),
            'size' => is_file($finalPath) ? filesize($finalPath) : 0,
            'createdAt' => date('c'),
            'deletedOldCount' => $deletedCount,
            'retentionDays' => $this->getRetentionDays(),
            'directory' => $backupDirectory,
        ];
    }

    /**
     * Удалить старые резервные копии.
     */
    public function cleanupOldBackups(): int
    {
        $backupDirectory = $this->getBackupDirectory();
        if (!is_dir($backupDirectory)) {
            return 0;
        }

        $retentionDays = $this->getRetentionDays();
        if ($retentionDays < 1) {
            return 0;
        }

        $expireAt = time() - ($retentionDays * 86400);
        $deletedCount = 0;

        foreach ($this->findBackupFiles() as $filePath) {
            $modifiedAt = @filemtime($filePath);
            if ($modifiedAt === false || $modifiedAt >= $expireAt) {
                continue;
            }

            if (@unlink($filePath)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Получить последние резервные копии.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentBackups(int $limit = 5): array
    {
        $files = $this->findBackupFiles();
        $files = array_slice($files, 0, $limit);

        $items = [];
        foreach ($files as $filePath) {
            $items[] = [
                'filename' => basename($filePath),
                'path' => $filePath,
                'size' => is_file($filePath) ? (int) filesize($filePath) : 0,
                'modifiedAt' => is_file($filePath) ? date('d.m.Y H:i', (int) filemtime($filePath)) : null,
            ];
        }

        return $items;
    }

    public function getBackupDirectory(): string
    {
        $path = Yii::$app->params['dbBackup']['path'] ?? '@runtime/backups/db';
        return Yii::getAlias($path);
    }

    public function getRetentionDays(): int
    {
        $days = (int) (Yii::$app->params['dbBackup']['retentionDays'] ?? 14);
        return max(1, $days);
    }

    public function getScheduleLabel(): string
    {
        return (string) (Yii::$app->params['dbBackup']['scheduleLabel'] ?? 'Каждые 12 часов');
    }

    private function writeDump($handle): void
    {
        $dbName = $this->getDatabaseName();

        $this->writeText($handle, "-- DBT backup\n");
        $this->writeText($handle, '-- Database: ' . $dbName . "\n");
        $this->writeText($handle, '-- Generated at: ' . date('c') . "\n\n");
        $this->writeText($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        $this->writeText($handle, "SET time_zone = '+00:00';\n");
        $this->writeText($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

        foreach ($this->getDatabaseObjects('BASE TABLE') as $tableName) {
            $this->writeText($handle, $this->buildStructureDump($tableName, false));
            $this->writeText($handle, $this->buildDataDump($tableName));
        }

        foreach ($this->getDatabaseObjects('VIEW') as $viewName) {
            $this->writeText($handle, $this->buildStructureDump($viewName, true));
        }

        $this->writeText($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
    }

    private function buildStructureDump(string $objectName, bool $isView): string
    {
        $db = Yii::$app->db;
        $quotedObject = $db->quoteTableName($objectName);
        $row = $db->createCommand('SHOW CREATE TABLE ' . $quotedObject)->queryOne();

        $createSql = $row['Create Table'] ?? $row['Create View'] ?? null;
        if ($createSql === null) {
            throw new RuntimeException('Не удалось получить структуру объекта ' . $objectName . '.');
        }

        $dropSql = $isView
            ? 'DROP VIEW IF EXISTS ' . $quotedObject . ';'
            : 'DROP TABLE IF EXISTS ' . $quotedObject . ';';

        return '-- Structure for ' . $objectName . "\n"
            . $dropSql . "\n"
            . $createSql . ";\n\n";
    }

    private function buildDataDump(string $tableName): string
    {
        $db = Yii::$app->db;
        $tableSchema = $db->schema->getTableSchema($tableName, true);
        if ($tableSchema === null || empty($tableSchema->columns)) {
            return '';
        }

        $rowsSql = [];
        $columnNames = array_keys($tableSchema->columns);
        $quotedColumns = array_map([$db, 'quoteColumnName'], $columnNames);
        $insertPrefix = 'INSERT INTO ' . $db->quoteTableName($tableName)
            . ' (' . implode(', ', $quotedColumns) . ') VALUES ';

        $hasRows = false;
        foreach ((new Query())->from($tableName)->batch(self::BATCH_SIZE, $db) as $batch) {
            if (empty($batch)) {
                continue;
            }

            $hasRows = true;
            $valueRows = [];
            foreach ($batch as $row) {
                $values = [];
                foreach ($columnNames as $columnName) {
                    $values[] = $this->formatValue($row[$columnName] ?? null, $tableSchema->columns[$columnName]);
                }
                $valueRows[] = '(' . implode(', ', $values) . ')';
            }

            $rowsSql[] = $insertPrefix . implode(",\n", $valueRows) . ';';
        }

        if (!$hasRows) {
            return '-- Data for ' . $tableName . ": table is empty.\n\n";
        }

        return '-- Data for ' . $tableName . "\n"
            . implode("\n\n", $rowsSql)
            . "\n\n";
    }

    private function formatValue($value, ColumnSchema $column): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if ($column->phpType === 'boolean') {
            return $value ? '1' : '0';
        }

        if (in_array($column->phpType, ['integer', 'double'], true) && is_numeric($value)) {
            return (string) $value;
        }

        return Yii::$app->db->quoteValue((string) $value);
    }

    /**
     * @return string[]
     */
    private function getDatabaseObjects(string $type): array
    {
        if (!in_array($type, ['BASE TABLE', 'VIEW'], true)) {
            throw new RuntimeException('Неизвестный тип объекта базы данных: ' . $type . '.');
        }

        $rows = Yii::$app->db
            ->createCommand("SHOW FULL TABLES WHERE Table_type = '{$type}'")
            ->queryAll();

        $items = [];
        foreach ($rows as $row) {
            $items[] = (string) reset($row);
        }

        sort($items);

        return $items;
    }

    /**
     * @return string[]
     */
    private function findBackupFiles(): array
    {
        $backupDirectory = $this->getBackupDirectory();
        if (!is_dir($backupDirectory)) {
            return [];
        }

        $files = FileHelper::findFiles($backupDirectory, [
            'only' => ['*.sql', '*.sql.gz'],
        ]);

        usort($files, static function (string $left, string $right): int {
            return (int) filemtime($right) <=> (int) filemtime($left);
        });

        return $files;
    }

    private function getDatabaseName(): string
    {
        $dsn = Yii::$app->db->dsn;
        if (preg_match('/dbname=([^;]+)/i', $dsn, $matches) === 1) {
            return $matches[1];
        }

        return 'database';
    }

    private function compressFile(string $sourcePath, string $targetPath): void
    {
        $input = fopen($sourcePath, 'rb');
        $output = gzopen($targetPath, 'wb9');

        if ($input === false || $output === false) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                gzclose($output);
            }
            throw new RuntimeException('Не удалось сжать резервную копию базы данных.');
        }

        while (!feof($input)) {
            $chunk = fread($input, 8192);
            if ($chunk === false) {
                fclose($input);
                gzclose($output);
                @unlink($targetPath);
                throw new RuntimeException('Ошибка чтения SQL-дампа при сжатии.');
            }
            gzwrite($output, $chunk);
        }

        fclose($input);
        gzclose($output);
    }

    /**
     * @param resource $handle
     */
    private function writeText($handle, string $text): void
    {
        if (fwrite($handle, $text) === false) {
            throw new RuntimeException('Ошибка записи резервной копии в файл.');
        }
    }
}
