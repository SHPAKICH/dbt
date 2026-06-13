<?php

use yii\db\Migration;

/**
 * Оставляет только точки сети Double Bubble Tea (16 локаций).
 * Связанные записи (смены, дейли, заказы и т.д.) удаляются каскадом.
 * У сотрудников location_id обнуляется (ON DELETE SET NULL).
 */
class m260605_140000_keep_network_locations_only extends Migration
{
    /** @var string[] */
    private array $keepNames = [
        'Жемчужная Плаза',
        'Стачек',
        'ТРК Лето',
        'ТЦ Радуга',
        'Бухарестская',
        'Лондон Молл',
        'Марата',
        'Ломоносова 12/66',
        'Ломоносова 1',
        'Гороховая',
        'Чкаловская',
        'Атлантик Сити',
        'Меркурий',
        'Леомолл',
        'Легенда',
        'Прометей',
    ];

    public function safeUp()
    {
        $idsToKeep = (new \yii\db\Query())
            ->from('{{%locations}}')
            ->where(['name' => $this->keepNames])
            ->select('id')
            ->column($this->db);

        if (count($idsToKeep) < count($this->keepNames)) {
            $existing = (new \yii\db\Query())
                ->from('{{%locations}}')
                ->where(['name' => $this->keepNames])
                ->select('name')
                ->column($this->db);
            $missing = array_values(array_diff($this->keepNames, $existing));
            echo 'Внимание: в БД не найдены точки: ' . implode(', ', $missing) . "\n";
        }

        if ($idsToKeep === []) {
            echo "Нет точек для сохранения — удаление пропущено.\n";
            return;
        }

        $this->delete('{{%locations}}', ['not in', 'id', $idsToKeep]);
    }

    public function safeDown()
    {
        echo "Откат невозможен: удалённые точки не восстанавливаются автоматически.\n";
        return false;
    }
}
