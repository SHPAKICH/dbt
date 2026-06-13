<?php

/** @var yii\web\View $this */
/** @var app\models\TrainingTest $model */
/** @var app\models\TrainingQuestion[] $questions */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use app\modules\guru\components\GuruImageHelper;

$this->title = $model->isNewRecord ? 'Новый тест' : 'Редактировать тест';
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Тесты', 'url' => ['/guru/test/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="guru-test-form">
    <?php $form = ActiveForm::begin(['id' => 'test-form', 'options' => ['enctype' => 'multipart/form-data']]); ?>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Основные данные</h5></div>
        <div class="card-body">
            <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'category')->textInput(['maxlength' => true]) ?>
            <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>
            <div class="row">
                <div class="col-md-4"><?= $form->field($model, 'time_limit')->textInput(['type' => 'number', 'min' => 0])->hint('Секунд на весь тест, пусто — без лимита') ?></div>
                <div class="col-md-4"><?= $form->field($model, 'question_time_limit')->textInput(['type' => 'number', 'min' => 0])->hint('Секунд на вопрос') ?></div>
                <div class="col-md-4"><?= $form->field($model, 'pass_score')->textInput(['type' => 'number', 'min' => 0, 'max' => 100, 'step' => 0.01]) ?></div>
            </div>
            <?= $form->field($model, 'imageFile')->fileInput(['accept' => 'image/*'])->hint('JPG, PNG, GIF, WebP до 3 МБ') ?>
            <?php if ($model->image): ?>
                <p class="text-muted small">Текущее: <?= Html::img(GuruImageHelper::getUrl($model->image), ['style' => 'max-height:60px', 'alt' => '']) ?></p>
            <?php endif; ?>
            <?= $form->field($model, 'is_active')->checkbox() ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Вопросы</h5>
            <button type="button" class="btn btn-sm btn-success" id="add-question">+ Добавить вопрос</button>
        </div>
        <div class="card-body" id="questions-list">
            <?php
            $questions = $questions ?? [];
            if (empty($questions)) {
                $questions = [new \app\models\TrainingQuestion()];
            }
            foreach ($questions as $qIndex => $q):
                $opts = $q->getOptionsArray();
                while (count($opts) < 4) {
                    $opts[] = ['id' => (string)(count($opts) + 1), 'text' => '', 'is_correct' => false];
                }
                $correctId = $q->correct_answer;
            ?>
            <div class="question-item card mb-3" data-index="<?= $qIndex ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <strong>Вопрос <?= $qIndex + 1 ?></strong>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-question">Удалить</button>
                    </div>
                    <input type="hidden" name="questions[<?= $qIndex ?>][id]" value="<?= $q->id ?? '' ?>">
                    <div class="mb-2 mt-2">
                        <label class="form-label">Текст вопроса</label>
                        <textarea name="questions[<?= $qIndex ?>][question_text]" class="form-control" rows="2"><?= Html::encode($q->question_text) ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Варианты ответов (отметьте правильный)</label>
                        <?php for ($o = 0; $o < 6; $o++): ?>
                            <?php
                            $opt = $opts[$o] ?? ['id' => (string)($o + 1), 'text' => '', 'is_correct' => false];
                            $oid = $opt['id'] ?? ($o + 1);
                            $checked = ((string)$oid === (string)$correctId) ? ' checked' : '';
                            ?>
                            <div class="input-group mb-1 option-row">
                                <div class="input-group-text">
                                    <input type="radio" name="questions[<?= $qIndex ?>][correct_answer]" value="<?= Html::encode($oid) ?>"<?= $checked ?> class="form-check-input">
                                </div>
                                <input type="hidden" name="questions[<?= $qIndex ?>][options][<?= $o ?>][id]" value="<?= Html::encode($oid) ?>">
                                <input type="text" name="questions[<?= $qIndex ?>][options][<?= $o ?>][text]" class="form-control" placeholder="Вариант <?= $o + 1 ?>" value="<?= Html::encode($opt['text'] ?? '') ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Баллы</label>
                            <input type="number" name="questions[<?= $qIndex ?>][points]" class="form-control" value="<?= (int)($q->points ?? 1) ?>" min="1">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Изображение к вопросу</label>
                            <input type="file" name="questions[<?= $qIndex ?>][image_file]" class="form-control form-control-sm" accept="image/*">
                            <?php if ($q->image): ?>
                                <p class="small text-muted mt-1">Текущее: <?= Html::img(GuruImageHelper::getUrl($q->image), ['style' => 'max-height:50px', 'alt' => '']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', $model->isNewRecord ? ['/guru/test/index'] : ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>

<?php
// Шаблон для нового вопроса (для JS)
$templateQuestion = new \app\models\TrainingQuestion();
$templateQuestion->setOptionsArray([['id' => '1', 'text' => '', 'is_correct' => false], ['id' => '2', 'text' => '', 'is_correct' => false], ['id' => '3', 'text' => '', 'is_correct' => false], ['id' => '4', 'text' => '', 'is_correct' => false]]);
$nextIndex = count($questions ?? []);
$this->registerJs(<<<JS
(function() {
    var nextIndex = {$nextIndex};
    var optionCount = 6;

    function getQuestionHtml(index) {
        var html = '<div class="question-item card mb-3" data-index="' + index + '">';
        html += '<div class="card-body">';
        html += '<div class="d-flex justify-content-between align-items-start"><strong>Вопрос ' + (index + 1) + '</strong>';
        html += '<button type="button" class="btn btn-sm btn-outline-danger remove-question">Удалить</button></div>';
        html += '<input type="hidden" name="questions[' + index + '][id]" value="">';
        html += '<div class="mb-2 mt-2"><label class="form-label">Текст вопроса</label>';
        html += '<textarea name="questions[' + index + '][question_text]" class="form-control" rows="2"></textarea></div>';
        html += '<div class="mb-2"><label class="form-label">Варианты ответов (отметьте правильный)</label>';
        for (var o = 0; o < optionCount; o++) {
            var oid = o + 1;
            html += '<div class="input-group mb-1 option-row">';
            html += '<div class="input-group-text"><input type="radio" name="questions[' + index + '][correct_answer]" value="' + oid + '" class="form-check-input"></div>';
            html += '<input type="hidden" name="questions[' + index + '][options][' + o + '][id]" value="' + oid + '">';
            html += '<input type="text" name="questions[' + index + '][options][' + o + '][text]" class="form-control" placeholder="Вариант ' + (o + 1) + '"></div>';
        }
        html += '</div><div class="row"><div class="col-md-4"><label class="form-label">Баллы</label>';
        html += '<input type="number" name="questions[' + index + '][points]" class="form-control" value="1" min="1"></div>';
        html += '<div class="col-md-8"><label class="form-label">Изображение к вопросу</label>';
        html += '<input type="file" name="questions[' + index + '][image_file]" class="form-control form-control-sm" accept="image/*"></div></div></div></div>';
        return html;
    }

    function reindexQuestions() {
        document.querySelectorAll('#questions-list .question-item').forEach(function(el, i) {
            el.setAttribute('data-index', i);
            el.querySelector('strong').textContent = 'Вопрос ' + (i + 1);
            ['id','question_text','correct_answer','points','image_file'].forEach(function(field) {
                var inp = el.querySelector('[name*="[' + field + ']"]');
                if (inp) inp.name = 'questions[' + i + '][' + field + ']';
            });
            el.querySelectorAll('.option-row').forEach(function(row, o) {
                row.querySelectorAll('input').forEach(function(inp) {
                    if (inp.name.indexOf('options') !== -1) inp.name = 'questions[' + i + '][options][' + o + '][' + (inp.name.indexOf('id') !== -1 ? 'id' : 'text') + ']';
                    if (inp.type === 'radio') inp.name = 'questions[' + i + '][correct_answer]';
                });
            });
        });
        nextIndex = document.querySelectorAll('#questions-list .question-item').length;
    }

    document.getElementById('add-question').onclick = function() {
        var div = document.createElement('div');
        div.innerHTML = getQuestionHtml(nextIndex);
        document.getElementById('questions-list').appendChild(div.firstElementChild);
        nextIndex++;
        div.firstElementChild.querySelector('.remove-question').onclick = function() {
            this.closest('.question-item').remove();
            reindexQuestions();
        };
    };

    document.querySelectorAll('.remove-question').forEach(function(btn) {
        btn.onclick = function() {
            this.closest('.question-item').remove();
            reindexQuestions();
        };
    });
})();
JS
);
?>
