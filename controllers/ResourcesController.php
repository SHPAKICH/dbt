<?php

namespace app\controllers;

use app\models\ResourceCell;
use app\models\ResourceCellHistory;
use app\models\ResourceMatrix;
use app\models\ResourceRow;
use app\models\ResourceColumn;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ResourcesController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            $user = Yii::$app->user->identity;
                            if (!$user) {
                                return false;
                            }
                            // Администратор — всегда можно
                            if ($user->isAdmin()) {
                                return true;
                            }
                            // Менеджер точки не имеет доступа к таблице ресурсов
                            return $user->position !== 'location_manager';
                        },
                    ],
                ],
            ],
        ];
    }

    public function actionIndex($id = null)
    {
        $matrices = ResourceMatrix::find()->orderBy(['name' => SORT_ASC])->all();
        if (!$matrices) {
            return $this->render('index-empty');
        }

        if ($id === null) {
            $matrix = $matrices[0];
        } else {
            $matrix = ResourceMatrix::findOne((int)$id);
            if (!$matrix) {
                throw new NotFoundHttpException('Матрица не найдена.');
            }
        }

        $rows = $matrix->rows;
        $columns = $matrix->columns;

        // Предзагрузка ячеек
        $cells = ResourceCell::find()
            ->where(['row_id' => array_column($rows, 'id') ?: [0]])
            ->andWhere(['column_id' => array_column($columns, 'id') ?: [0]])
            ->all();

        $cellMap = [];
        foreach ($cells as $cell) {
            $cellMap[$cell->row_id][$cell->column_id] = $cell;
        }

        return $this->render('index', [
            'matrices' => $matrices,
            'matrix' => $matrix,
            'rows' => $rows,
            'columns' => $columns,
            'cellMap' => $cellMap,
        ]);
    }

    /**
     * AJAX-обновление ячейки с optimistic locking.
     */
    public function actionUpdateCell()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rowId = (int)Yii::$app->request->post('row_id');
        $columnId = (int)Yii::$app->request->post('column_id');
        $value = (string)Yii::$app->request->post('value', '');
        $version = (int)Yii::$app->request->post('version', 0);

        $row = ResourceRow::findOne($rowId);
        $column = ResourceColumn::findOne($columnId);

        if (!$row || !$column || $row->matrix_id !== $column->matrix_id) {
            return [
                'success' => false,
                'error' => 'Некорректные данные строки или колонки.',
            ];
        }

        $cell = ResourceCell::findOne(['row_id' => $rowId, 'column_id' => $columnId]);
        $currentUserId = Yii::$app->user->id;

        if ($cell) {
            if ($version !== $cell->version) {
                return [
                    'success' => false,
                    'conflict' => true,
                    'currentValue' => $cell->value,
                    'currentVersion' => $cell->version,
                    'message' => 'Ячейка была изменена другим пользователем.',
                ];
            }
            $oldValue = $cell->value;
            $cell->value = $value !== '' ? $value : null;
            $cell->version++;
            $cell->updated_by = $currentUserId;
        } else {
            $cell = new ResourceCell();
            $cell->row_id = $rowId;
            $cell->column_id = $columnId;
            $cell->value = $value !== '' ? $value : null;
            $cell->version = 1;
            $cell->updated_by = $currentUserId;
            $oldValue = null;
        }

        if (!$cell->save()) {
            return [
                'success' => false,
                'error' => 'Не удалось сохранить ячейку.',
            ];
        }

        $history = new ResourceCellHistory();
        $history->cell_id = $cell->id;
        $history->old_value = $oldValue;
        $history->new_value = $cell->value;
        $history->version = $cell->version;
        $history->changed_by = $currentUserId;
        $history->save(false);

        return [
            'success' => true,
            'value' => $cell->value,
            'version' => $cell->version,
        ];
    }
}



