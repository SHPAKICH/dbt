<?php

use yii\db\Migration;

/**
 * Импорт из sql/import_staff_list_20260320.sql: недостающие локации, пользователи, manager_locations.
 *
 * Выполняется одним exec() с MYSQL_ATTR_MULTI_STATEMENTS (только MySQL).
 */
class m260320_120000_import_staff_list_20260320 extends Migration
{
    public function safeUp()
    {
        $path = \Yii::getAlias('@app/sql/import_staff_list_20260320.sql');
        if (!is_readable($path)) {
            throw new \RuntimeException('Не найден или не читается файл: ' . $path);
        }
        $sql = file_get_contents($path);
        if ($sql === false || $sql === '') {
            throw new \RuntimeException('Пустой или недоступный SQL: ' . $path);
        }

        $pdo = $this->db->pdo;
        if ($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            echo "    > Пропуск: миграция рассчитана на MySQL (мультизапрос из файла).\n";

            return true;
        }

        $prevMulti = false;
        if (\defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
            try {
                $prevMulti = (bool) $pdo->getAttribute(\PDO::MYSQL_ATTR_MULTI_STATEMENTS);
            } catch (\Throwable $e) {
                $prevMulti = false;
            }
            $pdo->setAttribute(\PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);
        }

        try {
            $pdo->exec($sql);
        } finally {
            if (\defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
                $pdo->setAttribute(\PDO::MYSQL_ATTR_MULTI_STATEMENTS, $prevMulti);
            }
        }

        return true;
    }

    public function safeDown()
    {
        echo "    > Откат не предусмотрен: удаляйте записи вручную или восстанавливайте БД из бэкапа.\n";

        return false;
    }
}
