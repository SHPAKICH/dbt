<?php

use yii\db\Migration;

/**
 * Главный админ всегда имеет is_admin=1; супер-доступ ему не нужен отдельно.
 */
class m260605_180000_fix_main_admin_rights extends Migration
{
    public function safeUp()
    {
        if (!$this->db->schema->getTableSchema('{{%users}}')->getColumn('is_main_admin')) {
            return;
        }

        $this->update('{{%users}}', [
            'is_admin' => 1,
            'has_super_access' => 0,
        ], ['is_main_admin' => 1]);

        // Если главный админ не назначен — первый is_admin становится главным
        $hasMain = (new \yii\db\Query())
            ->from('{{%users}}')
            ->where(['is_main_admin' => 1])
            ->exists();
        if (!$hasMain) {
            $mainId = (new \yii\db\Query())
                ->from('{{%users}}')
                ->where(['is_admin' => 1])
                ->min('id');
            if ($mainId) {
                $this->update('{{%users}}', [
                    'is_main_admin' => 1,
                    'is_admin' => 1,
                    'has_super_access' => 0,
                ], ['id' => $mainId]);
            }
        }
    }

    public function safeDown()
    {
        // без отката
    }
}
