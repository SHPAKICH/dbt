<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $row_id
 * @property int $column_id
 * @property string|null $value
 * @property int $version
 * @property int|null $updated_by
 * @property string $updated_at
 *
 * @property ResourceRow $row
 * @property ResourceColumn $column
 * @property User $updatedBy
 * @property ResourceCellHistory[] $history
 */
class ResourceCell extends ActiveRecord
{
    public static function tableName()
    {
        return 'resource_cells';
    }

    public function rules()
    {
        return [
            [['row_id', 'column_id'], 'required'],
            [['row_id', 'column_id', 'version', 'updated_by'], 'integer'],
            [['value'], 'string'],
            [['row_id'], 'exist', 'skipOnError' => true, 'targetClass' => ResourceRow::class, 'targetAttribute' => ['row_id' => 'id']],
            [['column_id'], 'exist', 'skipOnError' => true, 'targetClass' => ResourceColumn::class, 'targetAttribute' => ['column_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'row_id' => 'Строка',
            'column_id' => 'Колонка',
            'value' => 'Значение',
            'version' => 'Версия',
            'updated_by' => 'Кем изменено',
            'updated_at' => 'Когда изменено',
        ];
    }

    public function getRow()
    {
        return $this->hasOne(ResourceRow::class, ['id' => 'row_id']);
    }

    public function getColumn()
    {
        return $this->hasOne(ResourceColumn::class, ['id' => 'column_id']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }

    public function getHistory()
    {
        return $this->hasMany(ResourceCellHistory::class, ['cell_id' => 'id'])->orderBy(['version' => SORT_DESC]);
    }
}



