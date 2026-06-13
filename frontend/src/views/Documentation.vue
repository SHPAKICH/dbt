<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Документация</h1>
    <p class="text-gray-600 mb-6">
      Здесь ответственные лица заполняют документы и отчёты по окончании смены: выручка, отчёты по точке и прочее.
    </p>

    <div class="bg-white rounded-xl border-2 border-primary/30 overflow-hidden shadow-sm">
      <div class="bg-primary/10 px-6 py-4 flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-gray-800">📊 Категория документов — Дейли</h2>
        <router-link
          to="/daily"
          class="inline-flex items-center px-4 py-2 rounded-lg bg-primary text-white font-medium hover:opacity-90"
        >
          Открыть Дейли →
        </router-link>
      </div>
      <div class="p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-3">Ежедневный отчёт по точке (заполняется в конце смены, экспорт в конце месяца)</h3>
        <ul class="space-y-1 text-gray-700 text-sm mb-4">
          <li><strong>Автогенерация:</strong> документ Дейли автоматически создаётся на каждый следующий месяц.</li>
          <li><strong>Когда заполняется:</strong> в конце смены сотрудником, закрывающим смену.</li>
          <li><strong>Где ведётся:</strong> отдельный Дейли заводится на каждой точке.</li>
        </ul>
        <p class="text-sm text-gray-600 mb-2">В Дейли указываются основные показатели дня:</p>
        <ul class="text-sm text-gray-600 space-y-0.5">
          <li>Дата, день недели</li>
          <li>ТО (выручка), план на день, DELTA к дневному плану</li>
          <li>Разбивка продаж: БАР, ДОСТАВКА, САМОВЫВОЗ, БОНУСЫ, заказы</li>
          <li>Средний чек по каналам, часы, производительность</li>
          <li>Менеджер смены</li>
        </ul>
      </div>
    </div>

    <div class="mt-6 bg-white rounded-xl border-2 border-amber-200 overflow-hidden shadow-sm">
      <div class="bg-amber-50 px-6 py-4 flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-gray-800">📋 Списание</h2>
        <router-link
          to="/write-off"
          class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-600 text-white font-medium hover:bg-amber-700"
        >
          Открыть списание →
        </router-link>
      </div>
      <div class="p-6">
        <p class="text-sm text-gray-600 mb-3">
          Учёт списанных продуктов по категориям за смену: порча, сервис зала, маркетинг, проработка, бракераж.
        </p>
        <ul class="text-sm text-gray-600 space-y-1">
          <li>Дата и точка — в шапке формы</li>
          <li>Пять таблиц по категориям списания</li>
          <li>Автоподстановка продукта из iiko (например, «чай зелёный п/ф»)</li>
          <li>Вес и цена за единицу → сумма списания автоматически</li>
        </ul>
      </div>
    </div>

    <div class="mt-6 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-800 mb-2">Прочие документы</h2>
      <p class="text-gray-500 text-sm">Дополнительные категории документов будут добавлены в следующих обновлениях.</p>
    </div>
  </div>
</template>

<script setup>
</script>
