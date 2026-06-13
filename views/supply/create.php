<?php

use app\models\Location;
use app\models\Product;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/** @var yii\web\View $this */
/** @var Location[] $locations */
/** @var Product[] $products */

$this->title = 'Заказ поставки';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss(<<<CSS
.table-dark th,
.table-dark td {
    vertical-align: middle;
}
CSS);
?>

<div class="card mb-3">
    <div class="card-body">
        <?php $form = ActiveForm::begin([
            'method' => 'post',
            'action' => ['create'],
        ]); ?>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Точка</label>
                <select name="location_id" class="form-select" required>
                    <option value="">Выберите точку...</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc->id ?>"><?= Html::encode($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Комментарий (опционально)</label>
                <textarea name="comment" class="form-control" rows="1" placeholder="Дополнительная информация"></textarea>
            </div>
        </div>

        <h5 class="mb-2">Товары</h5>
        <div class="table-responsive">
            <table class="table table-dark table-hover table-striped align-middle">
                <thead>
                <tr>
                    <th>Товар</th>
                    <th>Артикул</th>
                    <th>Ед. изм.</th>
                    <th style="width: 160px;">Количество</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= Html::encode($product->name) ?></td>
                        <td><?= Html::encode($product->sku) ?></td>
                        <td><?= Html::encode($product->unit) ?></td>
                        <td>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="items[<?= $product->id ?>]"
                                   class="form-control form-control-sm text-end"
                                   placeholder="0">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted small">
                Укажите количество хотя бы для одного товара. После отправки будет сформирован XLSX-файл.
            </div>
            <button type="submit" class="btn btn-success">
                📦 Сформировать заказ (XLSX)
            </button>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
    </div>



