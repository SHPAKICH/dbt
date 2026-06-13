<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">О проекте</h1>
    <p class="text-gray-600 mb-6">
      DBT Hub (dbthub.ru) — корпоративный портал и единый личный кабинет сотрудников сети точек. Ниже — назначение системы, роли, разделы интерфейса и ключевые возможности в актуальном виде.
    </p>

    <!-- Назначение -->
    <section class="bg-white rounded-xl border-2 border-primary/30 overflow-hidden shadow-sm mb-6">
      <div class="bg-primary/10 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Назначение системы</h2>
      </div>
      <div class="p-6">
        <p class="text-gray-700 mb-4">
          Внутренняя платформа для персонала и операций сети локаций (точек). В одном SPA объединены:
        </p>
        <ul class="space-y-2 text-gray-700 text-sm list-disc list-inside">
          <li>профиль, график смен и карта возможностей (доступность по датам)</li>
          <li>зарплата и КРО (ключевые результаты / проход)</li>
          <li>заказы поставок, ежедневная отчётность Дейли, хаб «Документация»</li>
          <li>обучение DBT.INFO: тесты, карточки, уроки</li>
          <li>уведомления (лента новостей), Web Push, чат с бухгалтерией по точке</li>
          <li>техническое обслуживание: фотоотчёты по сменам (заявки — в планах)</li>
          <li>админка и аналитика (по ролям), настройки интерфейса и обратная связь</li>
        </ul>
        <p class="text-gray-600 text-sm mt-4">
          Один вход для сотрудника: операционные задачи, обучение и коммуникации в едином интерфейсе с разграничением по ролям и привязке к точкам / территории управляющего.
        </p>
      </div>
    </section>

    <!-- Роли -->
    <section class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm mb-6">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Роли и доступ</h2>
      </div>
      <div class="p-6">
        <div class="space-y-4">
          <div class="flex flex-wrap gap-2 items-start">
            <span class="font-medium text-gray-800 shrink-0 w-32">admin</span>
            <span class="text-gray-600 text-sm">Полный доступ: пользователи, локации, новости, push, конструктор карточек, аналитика, все модули без ограничения по точкам.</span>
          </div>
          <div class="flex flex-wrap gap-2 items-start">
            <span class="font-medium text-gray-800 shrink-0 w-32">manager</span>
            <span class="text-gray-600 text-sm">Управляющий территории: пользователи в рамках привязанных точек, поставки, Дейли, зарплата и график по своим локациям; карта возможностей — просмотр по всем точкам территории или по одной; раздел «Аналитика» и упрощённый вход в админку (пользователи).</span>
          </div>
          <div class="flex flex-wrap gap-2 items-start">
            <span class="font-medium text-gray-800 shrink-0 w-32">location_manager</span>
            <span class="text-gray-600 text-sm">Менеджер точки: поставки, Дейли, зарплата, документация, график и карта возможностей своей точки.</span>
          </div>
          <div class="flex flex-wrap gap-2 items-start">
            <span class="font-medium text-gray-800 shrink-0 w-32">senior_teamaker</span>
            <span class="text-gray-600 text-sm">Поставки, Дейли и документация по доступным локациям.</span>
          </div>
          <div class="flex flex-wrap gap-2 items-start">
            <span class="font-medium text-gray-800 shrink-0 w-32">teamaker</span>
            <span class="text-gray-600 text-sm">График, карта возможностей (личная), профиль, обучение, новости, зарплата, настройки, бухгалтерский чат и техобслуживание — в рамках прав на точку.</span>
          </div>
          <div class="flex flex-wrap gap-2 items-start">
            <span class="font-medium text-gray-800 shrink-0 w-32">trainee</span>
            <span class="text-gray-600 text-sm">Профиль, график, карта возможностей, обучение, новости.</span>
          </div>
        </div>
        <p class="text-gray-500 text-sm mt-4">
          Управляющие привязываются к набору точек; списки в API и пункты меню ограничиваются этой привязкой.
        </p>
      </div>
    </section>

    <!-- Модули -->
    <section class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm mb-6">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Разделы приложения</h2>
      </div>
      <div class="p-6 space-y-5">
        <div>
          <h3 class="font-medium text-gray-800 mb-1">Уведомления и личный кабинет</h3>
          <p class="text-gray-600 text-sm">Лента новостей; профиль с аватаром, выбором шаблона карточки (визитки) и настройкой видимости. Вход по email или телефону и паролю, сессия по Bearer-токену.</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">График смен</h3>
          <p class="text-gray-600 text-sm">Таблица смен по выбранной точке (неделя / месяц), строка «усиление», подсветка праздников РФ и желаемых смен из карты возможностей. Приглашения на смены, принятие / отклонение, список кандидатов на усиление.</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">Карта возможностей</h3>
          <p class="text-gray-600 text-sm">Тот же табличный формат, что у графика: дни месяца в столбцах. <strong>Моя карта</strong> — редактирование желаемого времени по датам (например, 9–21). Для управляющего, менеджера точки и администратора — вкладка <strong>По точкам</strong>: отдельный блок на каждую точку территории (Точка 1, 2, …) с картами сотрудников; фильтр «все точки» или одна точка.</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">Зарплата</h3>
          <p class="text-gray-600 text-sm">Данные по локациям, настройка КРО по точке и периоду, личные расчёты по дням для сотрудника.</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">Бухгалтерия</h3>
          <p class="text-gray-600 text-sm">Чат по выбранной точке: переписка с бухгалтерией, вложения, привязка к локации.</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">Тех. обслуживание</h3>
          <p class="text-gray-600 text-sm">Фотоотчёты по смене и точке. Раздел заявок на обслуживание зарезервирован под будущую разработку.</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">DBT.INFO (обучение)</h3>
          <p class="text-gray-600 text-sm">Раздел обучения в меню (DBT.INFO): тесты (прохождение, лидерборд, результаты), карточки, уроки с форматированным текстом, дашборд прогресса. Создание и редактирование контента — по правам в интерфейсе.</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">Заказ поставки</h3>
          <p class="text-gray-600 text-sm">Выбор точки, каталог, заказ, экспорт (в т.ч. Excel). Доступ: admin, manager, location_manager, senior_teamaker.</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">Документация и Дейли</h3>
          <p class="text-gray-600 text-sm">Страница-навигатор к ежедневным отчётам: автогенерация документов на месяц, заполнение по точке, показатели смены, экспорт. Сам ввод — в разделе «Дейли».</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">Аналитика</h3>
          <p class="text-gray-600 text-sm">Доступно администратору и управляющему территории.</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">Админка</h3>
          <p class="text-gray-600 text-sm">Дашборд, пользователи и локации, привязка управляющих к точкам, новости, Web Push, конструктор шаблонов карточек. У manager — упор на пользователей своей территории.</p>
        </div>
        <div>
          <h3 class="font-medium text-gray-800 mb-1">Настройки</h3>
          <p class="text-gray-600 text-sm">Тёмная тема, компактные таблицы, формат даты, напоминания о сменах через Web Push, ссылка и подсказки по Telegram-боту обратной связи.</p>
        </div>
      </div>
    </section>

    <!-- Интеграции -->
    <section class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm mb-6">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Интеграции</h2>
      </div>
      <div class="p-6">
        <h3 class="font-medium text-gray-800 mb-2">Telegram-бот обратной связи</h3>
        <p class="text-gray-600 text-sm mb-2">
          Команды: /start, /bug, /idea, /cancel. Текст, скриншоты и файлы пересылаются в админский чат с метаданными отправителя.
        </p>
        <p class="text-gray-500 text-sm">Подключение и уведомления настраиваются в разделе «Настройки».</p>
      </div>
    </section>

    <!-- Ключевые особенности -->
    <section class="bg-white rounded-xl border-2 border-primary/30 overflow-hidden shadow-sm mb-6">
      <div class="bg-primary/10 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-800">Ключевые особенности</h2>
      </div>
      <div class="p-6">
        <ol class="space-y-3 text-gray-700 text-sm list-decimal list-inside">
          <li><strong>Единый кабинет</strong> — график, карта возможностей, зарплата, обучение, новости, поставки, Дейли, бухгалтерия и техобслуживание в одном интерфейсе.</li>
          <li><strong>Карта возможностей как график</strong> — единый табличный вид; для управляющего — все точки территории отдельными блоками.</li>
          <li><strong>DBT.INFO</strong> — тесты с лидербордом, карточки, уроки, дашборд прогресса.</li>
          <li><strong>Роли и точки</strong> — данные на API отфильтрованы по привязке к локациям и должности.</li>
          <li><strong>Смены и приглашения</strong> — планирование, приглашения, принятие / отклонение.</li>
          <li><strong>Web Push</strong> — рассылки из админки и напоминания о сменах из настроек.</li>
          <li><strong>Экспорт</strong> — поставки и Дейли (в т.ч. Excel).</li>
          <li><strong>КРО</strong> — настройка процента прохода по локации и периоду.</li>
          <li><strong>Карточки профиля</strong> — шаблоны и конструктор в админке.</li>
          <li><strong>Адаптив</strong> — сайдбар с мобильным меню.</li>
        </ol>
      </div>
    </section>

    <!-- Безопасность -->
    <section class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm mb-6">
      <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Безопасность и надёжность</h2>
      </div>
      <div class="p-6">
        <ul class="space-y-1 text-gray-600 text-sm list-disc list-inside">
          <li>API под Bearer-токеном, проверка прав на каждом эндпоинте.</li>
          <li>Разделение доступа по ролям и локациям в бэкенде (Yii2) и в меню фронтенда.</li>
          <li>CORS и конфигурация окружения для продакшена; раздача по HTTPS через Nginx.</li>
        </ul>
      </div>
    </section>

    <!-- Резюме -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-800 mb-3">Резюме</h2>
      <p class="text-gray-600 text-sm mb-2">
        DBT Hub — точка входа для сотрудников сети: операционные модули (график, карта возможностей, поставки, Дейли, зарплата, аналитика), обучение DBT.INFO, коммуникации (новости, push, бухгалтерия, Telegram-бот), техобслуживание и администрирование с привязкой к точкам.
      </p>
      <p class="text-gray-500 text-sm">Рабочая среда: dbthub.ru.</p>
    </section>
  </div>
</template>

<script setup>
</script>
