<?php

use yii\db\Migration;

/**
 * Супер-доступ: главный админ может делегировать права админ-панели.
 */
class m260605_170000_add_user_super_access extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%users}}',
            'has_super_access',
            $this->boolean()->notNull()->defaultValue(0)->comment('Супер-доступ (админ-панель)')
        );
        $this->addColumn(
            '{{%users}}',
            'is_main_admin',
            $this->boolean()->notNull()->defaultValue(0)->comment('Главный администратор')
        );

        // Первый существующий is_admin = главный администратор
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

    public function safeDown()
    {
        $this->dropColumn('{{%users}}', 'is_main_admin');
        $this->dropColumn('{{%users}}', 'has_super_access');
    }
}
