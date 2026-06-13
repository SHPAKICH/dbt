<?php

/** @var yii\web\View $this */
/** @var app\models\TrainingTest $test */
/** @var app\models\TrainingQuestion[] $questions */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use app\modules\guru\components\GuruImageHelper;

$this->title = 'Прохождение: ' . $test->title;
$this->params['breadcrumbs'][] = ['label' => 'Меню Гуру', 'url' => ['/guru/default/index']];
$this->params['breadcrumbs'][] = ['label' => 'Тесты', 'url' => ['/guru/test/index']];
$this->params['breadcrumbs'][] = $this->title;

$totalQuestions = count($questions);
$timeLimit = $test->time_limit ? (int) $test->time_limit : 0;
$questionTimeLimit = $test->question_time_limit ? (int) $test->question_time_limit : 0;
$startedAt = date('Y-m-d H:i:s');
?>
<div class="guru-test-take">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0"><?= Html::encode($test->title) ?></h5>
            <div class="d-flex align-items-center gap-3">
                <span id="progress-text">Вопрос 1 из <?= $totalQuestions ?></span>
                <div class="progress flex-grow-1" style="width: 120px;">
                    <div class="progress-bar" id="progress-bar" role="progressbar" style="width: <?= $totalQuestions ? (100 / $totalQuestions) : 0 ?>%"></div>
                </div>
                <?php if ($timeLimit > 0): ?>
                    <span id="timer" class="badge bg-warning text-dark"><?= gmdate('i:s', $timeLimit) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'id' => 'test-form',
                'action' => ['/guru/test/submit'],
                'options' => ['class' => 'guru-take-form'],
            ]); ?>
            <?= Html::hiddenInput('test_id', $test->id) ?>
            <?= Html::hiddenInput('started_at', $startedAt) ?>
            <?= Html::hiddenInput('time_spent', 0, ['id' => 'time-spent']) ?>

            <div id="questions-container">
                <?php foreach ($questions as $index => $q): ?>
                    <div class="question-block <?= $index === 0 ? '' : 'd-none' ?>" data-index="<?= $index ?>" data-question-id="<?= $q->id ?>">
                        <p class="fw-bold mb-3"><?= Html::encode($q->question_text) ?></p>
                        <?php if ($q->image): ?>
                            <p><?= Html::img(GuruImageHelper::getUrl($q->image), ['class' => 'img-fluid rounded', 'alt' => '']) ?></p>
                        <?php endif; ?>
                        <div class="options-list">
                            <?php
                            $opts = $q->getOptionsArray();
                            foreach ($opts as $opt):
                                $optId = $opt['id'] ?? $opt['id'] ?? '';
                                $optText = $opt['text'] ?? '';
                            ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="answers[<?= $q->id ?>]" value="<?= Html::encode($optId) ?>" id="q<?= $q->id ?>_<?= Html::encode($optId) ?>">
                                    <label class="form-check-label" for="q<?= $q->id ?>_<?= Html::encode($optId) ?>"><?= Html::encode($optText) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($questionTimeLimit > 0): ?>
                            <p class="small text-muted mt-2">Осталось времени на вопрос: <span class="question-timer" data-limit="<?= $questionTimeLimit ?>"><?= $questionTimeLimit ?></span> сек</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary" id="btn-prev" style="display: none;">← Назад</button>
                <button type="button" class="btn btn-primary ms-auto" id="btn-next">Далее →</button>
                <button type="submit" class="btn btn-success ms-auto" id="btn-submit" style="display: none;">Завершить тест</button>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
$js = <<<JS
(function() {
    var total = {$totalQuestions};
    var current = 0;
    var timeLimit = {$timeLimit};
    var questionTimeLimit = {$questionTimeLimit};
    var startTime = Math.floor(Date.now() / 1000);
    var timerInterval = null;
    var questionTimerInterval = null;

    function showQuestion(index) {
        document.querySelectorAll('.question-block').forEach(function(el, i) {
            el.classList.toggle('d-none', i !== index);
        });
        current = index;
        document.getElementById('progress-text').textContent = 'Вопрос ' + (index + 1) + ' из ' + total;
        document.getElementById('progress-bar').style.width = ((index + 1) / total * 100) + '%';
        document.getElementById('btn-prev').style.display = index > 0 ? 'inline-block' : 'none';
        document.getElementById('btn-next').style.display = index < total - 1 ? 'inline-block' : 'none';
        document.getElementById('btn-submit').style.display = index === total - 1 ? 'inline-block' : 'none';

        if (questionTimeLimit > 0) {
            var qBlock = document.querySelector('.question-block[data-index="' + index + '"]');
            var qTimerEl = qBlock ? qBlock.querySelector('.question-timer') : null;
            if (questionTimerInterval) clearInterval(questionTimerInterval);
            if (qTimerEl) {
                var qLeft = questionTimeLimit;
                qTimerEl.textContent = qLeft;
                questionTimerInterval = setInterval(function() {
                    qLeft--;
                    qTimerEl.textContent = qLeft;
                    if (qLeft <= 0) {
                        clearInterval(questionTimerInterval);
                        if (current < total - 1) showQuestion(current + 1);
                        else document.getElementById('test-form').submit();
                    }
                }, 1000);
            }
        }
    }

    if (timeLimit > 0) {
        var timerEl = document.getElementById('timer');
        if (timerEl) {
            var left = timeLimit;
            timerInterval = setInterval(function() {
                left--;
                var m = Math.floor(left / 60), s = left % 60;
                timerEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                if (left <= 0) {
                    clearInterval(timerInterval);
                    document.getElementById('time-spent').value = timeLimit;
                    document.getElementById('test-form').submit();
                }
            }, 1000);
        }
    }

    document.getElementById('btn-next').onclick = function() {
        if (questionTimerInterval) clearInterval(questionTimerInterval);
        if (current < total - 1) showQuestion(current + 1);
    };
    document.getElementById('btn-prev').onclick = function() {
        if (current > 0) showQuestion(current - 1);
    };

    document.getElementById('test-form').onsubmit = function() {
        document.getElementById('time-spent').value = Math.floor(Date.now() / 1000) - startTime;
        if (timerInterval) clearInterval(timerInterval);
        if (questionTimerInterval) clearInterval(questionTimerInterval);
    };

    showQuestion(0);
})();
JS;
$this->registerJs($js);
?>
