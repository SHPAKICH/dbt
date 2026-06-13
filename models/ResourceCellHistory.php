<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $cell_id
 * @property string|null $old_value
 * @property string|null $new_value
 * @property int $version
 * @property int|null $changed_by
 * @property string $changed_at
 *
 * @property ResourceCell $cell
 * @property User $changedBy
 */
class ResourceCellHistory extends ActiveRecord
{
    public static function tableName()
    {
        return 'resource_cell_history';
    }

    public function rules()
    {
        return [
            [['cell_id', 'version'], 'required'],
            [['cell_id', 'version', 'changed_by'], 'integer'],
            [['old_value', 'new_value'], 'string'],
            [['cell_id'], 'exist', 'skipOnError' => true, 'targetClass' => ResourceCell::class, 'targetAttribute' => ['cell_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cell_id' => 'Ячейка',
            'old_value' => 'Старое значение',
            'new_value' => 'Новое значение',
            'version' => 'Версия',
            'changed_by' => 'Кто изменил',
            'changed_at' => 'Когда изменил',
        ];
    }

    public function getCell()
    {
        return $this->hasOne(ResourceCell::class, ['id' => 'cell_id']);
    }

    public function getChangedBy()
    {
        return $this->hasOne(User::class, ['id' => 'changed_by']);
    }
}



