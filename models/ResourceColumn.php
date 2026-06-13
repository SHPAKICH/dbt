<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $matrix_id
 * @property string $label
 * @property int $sort_order
 *
 * @property ResourceMatrix $matrix
 * @property ResourceCell[] $cells
 */
class ResourceColumn extends ActiveRecord
{
    public static function tableName()
    {
        return 'resource_columns';
    }

    public function rules()
    {
        return [
            [['matrix_id', 'label'], 'required'],
            [['matrix_id', 'sort_order'], 'integer'],
            [['label'], 'string', 'max' => 255],
            [['matrix_id'], 'exist', 'skipOnError' => true, 'targetClass' => ResourceMatrix::class, 'targetAttribute' => ['matrix_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'matrix_id' => 'Матрица',
            'label' => 'Колонка',
            'sort_order' => 'Порядок',
        ];
    }

    public function getMatrix()
    {
        return $this->hasOne(ResourceMatrix::class, ['id' => 'matrix_id']);
    }

    public function getCells()
    {
        return $this->hasMany(ResourceCell::class, ['column_id' => 'id']);
    }
}



