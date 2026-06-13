<?php

namespace app\modules\guru\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use app\models\TechCard;
use app\models\TechCardIngredient;
use app\modules\guru\components\GuruImageHelper;

/**
 * Технологические карты: каталог, просмотр, CRUD (для админов).
 */
class CardController extends Controller
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
     * Каталог карточек: поиск, фильтр по категории.
     */
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();

        $query = TechCard::find()->with('ingredients')->orderBy(['name' => SORT_ASC]);
        if (!$isAdmin) {
            $query->andWhere(['is_active' => 1]);
        }

        $search = Yii::$app->request->get('search', '');
        $category = Yii::$app->request->get('category', '');
        if ($search !== '') {
            $query->andWhere(['or',
                ['like', 'name', $search],
                ['like', 'description', $search],
                ['like', 'category', $search],
            ]);
        }
        if ($category !== '') {
            $query->andWhere(['category' => $category]);
        }

        $cards = $query->all();
        $categories = TechCard::find()
            ->select('category')
            ->distinct()
            ->where(['not', ['category' => null]])
            ->andWhere(['<>', 'category', ''])
            ->orderBy('category')
            ->column();

        return $this->render('index', [
            'cards' => $cards,
            'categories' => $categories,
            'search' => $search,
            'category' => $category,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Просмотр одной карточки.
     */
    public function actionView($id)
    {
        $card = $this->findCard($id);
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();
        if (!$card->is_active && !$isAdmin) {
            throw new NotFoundHttpException('Карточка недоступна.');
        }
        $card->getIngredients()->all(); // ensure loaded
        return $this->render('view', ['card' => $card, 'isAdmin' => $isAdmin]);
    }

    public function actionCreate()
    {
        $model = new TechCard();
        $model->created_by = Yii::$app->user->id;
        $ingredients = [];

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->imageFile) {
                $path = GuruImageHelper::save($model->imageFile, GuruImageHelper::TYPE_CARD);
                if ($path) {
                    $model->image = $path;
                }
            }
            $ingredientsData = Yii::$app->request->post('ingredients', []);
            if ($model->save(false)) {
                $this->saveIngredients($model->id, $ingredientsData);
                Yii::$app->session->setFlash('success', 'Технологическая карта создана.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('form', ['model' => $model, 'ingredients' => $ingredients]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findCard($id);
        $ingredients = $model->getIngredients()->all();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->imageFile) {
                $path = GuruImageHelper::save($model->imageFile, GuruImageHelper::TYPE_CARD);
                if ($path) {
                    $model->image = $path;
                }
            }
            $ingredientsData = Yii::$app->request->post('ingredients', []);
            if ($model->save(false)) {
                $this->saveIngredients($model->id, $ingredientsData);
                Yii::$app->session->setFlash('success', 'Карточка сохранена.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('form', ['model' => $model, 'ingredients' => $ingredients]);
    }

    public function actionDelete($id)
    {
        $model = $this->findCard($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Карточка удалена.');
        return $this->redirect(['index']);
    }

    private function findCard($id): TechCard
    {
        $model = TechCard::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Карточка не найдена.');
        }
        return $model;
    }

    /**
     * @param int $cardId
     * @param array $rows [['ingredient_name','size_code','quantity','unit','price_per_unit'], ...]
     */
    private function saveIngredients(int $cardId, array $rows): void
    {
        TechCardIngredient::deleteAll(['card_id' => $cardId]);
        $sortOrder = 0;
        foreach ($rows as $row) {
            if (empty($row['ingredient_name'])) {
                continue;
            }
            $ing = new TechCardIngredient();
            $ing->card_id = $cardId;
            $ing->ingredient_name = $row['ingredient_name'];
            $ing->size_code = $row['size_code'] !== '' ? ($row['size_code'] ?? null) : null;
            $ing->quantity = isset($row['quantity']) ? (float) str_replace(',', '.', $row['quantity']) : 0;
            $ing->unit = $row['unit'] ?? 'г';
            $ing->price_per_unit = isset($row['price_per_unit']) && $row['price_per_unit'] !== ''
                ? (float) str_replace(',', '.', $row['price_per_unit']) : null;
            $ing->sort_order = $sortOrder++;
            $ing->save(false);
        }
    }
}
