<?php

namespace app\modules\guru\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\TrainingMaterial;
use app\models\TrainingTest;

/**
 * Теоретические материалы: список, просмотр, CRUD (для админов).
 */
class MaterialController extends Controller
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
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            $adminOnly = ['create', 'update', 'delete'];
                            if (in_array($action->id, $adminOnly, true)) {
                                return $user && $user->isAdmin();
                            }
                            return true;
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Список материалов (с фильтром по категории и тесту).
     */
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();

        $query = TrainingMaterial::find()->orderBy(['sort_order' => SORT_ASC, 'title' => SORT_ASC]);
        if (!$isAdmin) {
            $query->andWhere(['is_active' => 1]);
        }
        $category = Yii::$app->request->get('category', '');
        $testId = (int) Yii::$app->request->get('test_id', 0);
        if ($category !== '') {
            $query->andWhere(['category' => $category]);
        }
        if ($testId > 0) {
            $query->andWhere(['test_id' => $testId]);
        }
        $materials = $query->with('test')->all();

        $categories = TrainingMaterial::find()
            ->select('category')
            ->distinct()
            ->where(['not', ['category' => null]])
            ->andWhere(['<>', 'category', ''])
            ->orderBy('category')
            ->column();
        $tests = TrainingTest::find()->select(['id', 'title'])->orderBy('title')->asArray()->all();

        return $this->render('index', [
            'materials' => $materials,
            'categories' => $categories,
            'tests' => $tests,
            'category' => $category,
            'testId' => $testId,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Просмотр материала.
     */
    public function actionView($id)
    {
        $model = $this->findMaterial($id);
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();
        if (!$model->is_active && !$isAdmin) {
            throw new NotFoundHttpException('Материал недоступен.');
        }
        return $this->render('view', ['model' => $model, 'isAdmin' => $isAdmin]);
    }

    public function actionCreate()
    {
        $model = new TrainingMaterial();
        $model->created_by = Yii::$app->user->id;
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Материал создан.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('form', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findMaterial($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Материал сохранён.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('form', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = $this->findMaterial($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Материал удалён.');
        return $this->redirect(['index']);
    }

    private function findMaterial($id): TrainingMaterial
    {
        $model = TrainingMaterial::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Материал не найден.');
        }
        return $model;
    }
}
