<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 *
 * @property ResourceRow[] $rows
 * @property ResourceColumn[] $columns
 */
class ResourceMatrix extends ActiveRecord
{
    public static function tableName()
    {
        return 'resource_matrices';
    }

    public function rules()
    {
        return [
            [['name', 'code'], 'required'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 100],
            [['code'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'code' => 'Код',
            'description' => 'Описание',
        ];
    }

    public function getRows()
    {
        return $this->hasMany(ResourceRow::class, ['matrix_id' => 'id'])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function getColumns()
    {
        return $this->hasMany(ResourceColumn::class, ['matrix_id' => 'id'])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
    }
}



