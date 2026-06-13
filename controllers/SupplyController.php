<?php

namespace app\controllers;

use app\models\Location;
use app\models\Product;
use app\models\SupplyOrder;
use app\models\SupplyOrderItem;
use app\services\SupplyExportService;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SupplyController extends Controller
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
                            if ($user->isAdmin()) {
                                return true;
                            }
                            // Доступ к заказам поставки: старший тимейкер и выше
                            return in_array($user->position, ['senior_teamaker', 'location_manager', 'manager'], true);
                        },
                    ],
                ],
            ],
        ];
    }

    public function actionCreate()
    {
        $currentUser = Yii::$app->user->identity;

        $locationsQuery = Location::find()->where(['is_active' => 1]);
        if ($currentUser && !$currentUser->isAdmin()) {
            if ($currentUser->position === 'manager') {
                // Управляющий — только свои точки
                $managerLocationIds = (new \yii\db\Query())
                    ->from('manager_locations')
                    ->where(['manager_id' => $currentUser->id])
                    ->select('location_id')
                    ->column();
                if ($managerLocationIds) {
                    $locationsQuery->andWhere(['id' => $managerLocationIds]);
                } else {
                    $locationsQuery->andWhere('0=1');
                }
            } elseif ($currentUser->position === 'location_manager' && $currentUser->location_id) {
                // Менеджер точки — только своя точка
                $locationsQuery->andWhere(['id' => $currentUser->location_id]);
            }
        }
        $locations = $locationsQuery->orderBy(['name' => SORT_ASC])->all();

        $products = Product::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();

        if (Yii::$app->request->isPost) {
            $locationId = (int)Yii::$app->request->post('location_id');
            $comment = (string)Yii::$app->request->post('comment', '');
            $itemsData = Yii::$app->request->post('items', []);

            if (!$locationId) {
                Yii::$app->session->setFlash('error', 'Не выбрана точка.');
                return $this->redirect(['create']);
            }

            $order = new SupplyOrder();
            $order->location_id = $locationId;
            $order->created_by = $currentUser->id;
            $order->status = 'new';
            $order->comment = $comment ?: null;

            if (!$order->save()) {
                Yii::$app->session->setFlash('error', 'Не удалось создать заказ.');
                return $this->redirect(['create']);
            }

            $hasItems = false;
            foreach ($itemsData as $productId => $quantity) {
                $quantity = (float)$quantity;
                if ($quantity <= 0) {
                    continue;
                }
                $item = new SupplyOrderItem();
                $item->order_id = $order->id;
                $item->product_id = (int)$productId;
                $item->quantity = $quantity;
                if ($item->save()) {
                    $hasItems = true;
                }
            }

            if (!$hasItems) {
                $order->delete();
                Yii::$app->session->setFlash('error', 'Вы не указали количество ни по одному товару.');
                return $this->redirect(['create']);
            }

            /** @var SupplyExportService $exporter */
            $exporter = Yii::$container->get(SupplyExportService::class);
            $tmpFile = $exporter->exportToFile($order);
            return Yii::$app->response->sendFile($tmpFile, 'supply_order_' . $order->id . '.xlsx', [
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'inline' => false,
            ])->on(Response::EVENT_AFTER_SEND, function () use ($tmpFile) {
                @unlink($tmpFile);
            });
        }

        return $this->render('create', [
            'locations' => $locations,
            'products' => $products,
        ]);
    }

    /**
     * Просмотр существующего заказа (минимальный режим, без списка).
     */
    public function actionView($id)
    {
        $order = SupplyOrder::find()->with(['location', 'creator', 'items.product'])->where(['id' => (int)$id])->one();
        if (!$order) {
            throw new NotFoundHttpException('Заказ не найден.');
        }

        return $this->render('view', [
            'order' => $order,
        ]);
    }
}



