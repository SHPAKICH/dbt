<?php

use app\models\SupplyOrder;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var SupplyOrder $order */

$this->title = 'Заказ #' . $order->id;
$this->params['breadcrumbs'][] = ['label' => 'Заказ поставки', 'url' => ['create']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-3"><?= Html::encode($this->title) ?></h5>

        <p><strong>Точка:</strong> <?= $order->location ? Html::encode($order->location->name) : '' ?></p>
        <p><strong>Дата:</strong> <?= Html::encode($order->created_at) ?></p>
        <p><strong>Ответственный:</strong> <?= $order->creator ? Html::encode($order->creator->getFullName()) : '' ?></p>
        <?php if ($order->comment): ?>
            <p><strong>Комментарий:</strong> <?= nl2br(Html::encode($order->comment)) ?></p>
        <?php endif; ?>

        <h6 class="mt-3">Позиции</h6>
        <div class="table-responsive">
            <table class="table table-dark table-hover table-striped align-middle">
                <thead>
                <tr>
                    <th>Товар</th>
                    <th>Артикул</th>
                    <th>Ед. изм.</th>
                    <th class="text-end">Количество</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($order->items as $item): ?>
                    <?php $product = $item->product; ?>
                    <?php if (!$product) continue; ?>
                    <tr>
                        <td><?= Html::encode($product->name) ?></td>
                        <td><?= Html::encode($product->sku) ?></td>
                        <td><?= Html::encode($product->unit) ?></td>
                        <td class="text-end"><?= Html::encode($item->quantity) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



