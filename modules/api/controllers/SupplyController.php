<?php

namespace app\modules\api\controllers;

use app\models\Location;
use app\models\Product;
use app\models\SupplyOrder;
use app\models\SupplyOrderItem;
use app\services\SupplyExportService;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SupplyController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];
        // XLSX-экспорт отдаёт бинарный поток, а не JSON
        if (isset($behaviors['contentNegotiator'])) {
            $behaviors['contentNegotiator']['except'] = ['export'];
        }
        return $behaviors;
    }

    protected function verbs(): array
    {
        return [
            'locations' => ['GET'],
            'products' => ['GET'],
            'create-order' => ['POST'],
            'order' => ['GET'],
            'export' => ['GET'],
        ];
    }

    private function requireSupplyAccess(): void
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            Yii::$app->response->statusCode = 401;
            throw new \yii\web\UnauthorizedHttpException('Не авторизован.');
        }
        if (!$user->isAdmin() && !in_array($user->position, ['senior_teamaker', 'location_manager', 'manager'], true)) {
            Yii::$app->response->statusCode = 403;
            throw new \yii\web\ForbiddenHttpException('Нет доступа к заказу поставки.');
        }
    }

    private function getLocationsQuery(): \yii\db\ActiveQuery
    {
        $user = Yii::$app->user->identity;
        $query = Location::find()->where(['is_active' => 1]);
        if (!$user->isAdmin()) {
            if ($user->position === 'manager') {
                $ids = (new \yii\db\Query())->from('manager_locations')->where(['manager_id' => $user->id])->select('location_id')->column();
                if ($ids) {
                    $query->andWhere(['id' => $ids]);
                } else {
                    $query->andWhere('0=1');
                }
            } elseif ($user->position === 'location_manager' && $user->location_id) {
                $query->andWhere(['id' => $user->location_id]);
            }
        }
        return $query->orderBy(['name' => SORT_ASC]);
    }

    /**
     * GET /api/v1/supply/locations
     */
    public function actionLocations(): array
    {
        $this->requireSupplyAccess();
        $locations = $this->getLocationsQuery()->all();
        $list = [];
        foreach ($locations as $loc) {
            $list[] = ['id' => (int) $loc->id, 'name' => $loc->name];
        }
        return $this->success(['locations' => $list]);
    }

    /**
     * GET /api/v1/supply/products
     */
    public function actionProducts(): array
    {
        $this->requireSupplyAccess();
        $products = Product::find()
            ->where(['is_active' => 1])
            ->orderBy(['category' => SORT_ASC, 'sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        $categoryOrder = [
            'Ингредиенты' => 1,
            'Витрина' => 2,
            'Оборудование и расходные материалы' => 3,
            'Можно приобрести со склада ДБТ' => 4,
        ];

        $grouped = [];
        foreach ($products as $p) {
            $cat = $p->category ?: 'Без категории';
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = [
                'id' => (int) $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'unit' => $p->unit,
                'pricePerUnit' => $p->price_per_unit !== null ? (float) $p->price_per_unit : null,
                'packageQuantity' => $p->package_quantity,
                'packageDescription' => $p->package_description,
                'sortOrder' => (int) $p->sort_order,
            ];
        }

        uksort($grouped, function ($a, $b) use ($categoryOrder) {
            $oa = $categoryOrder[$a] ?? 999;
            $ob = $categoryOrder[$b] ?? 999;
            return $oa <=> $ob;
        });

        $categories = [];
        foreach ($grouped as $name => $items) {
            $categories[] = [
                'name' => $name,
                'products' => $items,
            ];
        }

        return $this->success(['categories' => $categories]);
    }

    /**
     * POST /api/v1/supply/orders — создать заказ, вернуть id и exportUrl.
     */
    public function actionCreateOrder(): array
    {
        $this->requireSupplyAccess();
        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->getBodyParams();
        $locationId = (int) ($body['locationId'] ?? $body['location_id'] ?? 0);
        $comment = trim((string) ($body['comment'] ?? ''));
        $items = $body['items'] ?? [];

        $locations = $this->getLocationsQuery()->all();
        $locationIds = array_column($locations, 'id');
        if (!$locationId || !in_array($locationId, $locationIds, true)) {
            return $this->error('Выберите точку.', [], 422);
        }

        $order = new SupplyOrder();
        $order->location_id = $locationId;
        $order->created_by = $user->id;
        $order->status = 'new';
        $order->comment = $comment ?: null;
        if (!$order->save()) {
            return $this->error('Не удалось создать заказ.', $order->errors, 422);
        }

        $hasItems = false;
        foreach ($items as $productId => $quantity) {
            $quantity = (float) $quantity;
            if ($quantity <= 0) {
                continue;
            }
            $item = new SupplyOrderItem();
            $item->order_id = $order->id;
            $item->product_id = (int) $productId;
            $item->quantity = $quantity;
            if ($item->save()) {
                $hasItems = true;
            }
        }

        if (!$hasItems) {
            $order->delete();
            return $this->error('Укажите количество хотя бы для одного товара.', [], 422);
        }

        $baseUrl = rtrim(Yii::$app->request->hostInfo, '/');
        $exportPath = '/api/v1/supply/orders/' . $order->id . '/export';
        return $this->success([
            'orderId' => (int) $order->id,
            'exportUrl' => $baseUrl . $exportPath,
        ], 'Заказ создан.');
    }

    /**
     * GET /api/v1/supply/orders/:id
     */
    public function actionOrder($id): array
    {
        $this->requireSupplyAccess();
        $order = SupplyOrder::find()->with(['location', 'creator', 'items.product'])->where(['id' => (int) $id])->one();
        if (!$order) {
            throw new NotFoundHttpException('Заказ не найден.');
        }
        $locationIds = array_column($this->getLocationsQuery()->all(), 'id');
        if (!in_array($order->location_id, $locationIds, true)) {
            Yii::$app->response->statusCode = 403;
            return $this->error('Нет доступа к этому заказу.', [], 403);
        }
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'productId' => (int) $item->product_id,
                'productName' => $item->product ? $item->product->name : '',
                'quantity' => (float) $item->quantity,
            ];
        }
        return $this->success([
            'id' => (int) $order->id,
            'locationName' => $order->location ? $order->location->name : '',
            'createdAt' => $order->created_at,
            'creatorName' => $order->creator ? $order->creator->getFullName() : '',
            'comment' => $order->comment,
            'items' => $items,
        ]);
    }

    /**
     * GET /api/v1/supply/orders/:id/export — скачать XLSX.
     */
    public function actionExport($id)
    {
        $this->requireSupplyAccess();
        $order = SupplyOrder::find()->with(['location', 'creator', 'items.product'])->where(['id' => (int) $id])->one();
        if (!$order) {
            throw new NotFoundHttpException('Заказ не найден.');
        }
        $locationIds = array_column($this->getLocationsQuery()->all(), 'id');
        if (!in_array($order->location_id, $locationIds, true)) {
            throw new \yii\web\ForbiddenHttpException('Нет доступа.');
        }
        try {
            /** @var SupplyExportService $exporter */
            $exporter = Yii::$container->get(SupplyExportService::class);
            $tmpFile = $exporter->exportToFile($order);
            $filename = 'supply_order_' . $order->id . '.xlsx';

            return Yii::$app->response->sendFile($tmpFile, $filename, [
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'inline' => false,
            ])->on(Response::EVENT_AFTER_SEND, function () use ($tmpFile) {
                @unlink($tmpFile);
            });
        } catch (\Throwable $e) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // Возвращаем реальную причину: на PHP 8.1 часто падает не сам пакет, а отдельный класс/зависимость.
            $detail = $e->getMessage();
            $class = $e::class;
            $msg = "Ошибка генерации XLSX ({$class}): {$detail}";
            return $this->error($msg, [], 503);
        }
    }
}
