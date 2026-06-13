# Telegram-бот обратной связи для DBT Hub

Простой Telegram-бот, который принимает сообщения от пользователей (ошибки, идеи, предложения) и пересылает их в указанный админский чат или канал.

## Возможности

- Команда `/start` — краткая инструкция.
- Команда `/bug` — режим отправки сообщения как *ошибки*.
- Команда `/idea` — режим отправки *предложений/улучшений*.
- Команда `/cancel` — отмена текущей заявки.
- Команда `/link КОД` — привязка Telegram к аккаунту DBT Hub (код из настроек приложения; нужна для восстановления пароля).
- Любое сообщение пользователя (текст, скриншоты, файлы) пересылается в админский чат с шапкой, где указаны:
  - тип (`ошибка` / `идея` / просто сообщение),
  - имя и username отправителя,
  - его Telegram ID.

## Установка

1. Перейди в директорию бота:

   ```bash
   cd telegram-bot
   ```

2. Создай виртуальное окружение (рекомендуется):

   ```bash
   python3 -m venv venv
   source venv/bin/activate  # Linux/macOS
   # или
   venv\Scripts\activate     # Windows
   ```

3. Установи зависимости:

   ```bash
   pip install -r requirements.txt
   ```

4. Создай файл `.env` на основе `.env.example`:

   ```bash
   cp .env.example .env
   ```

   Заполни значения:

   - `TELEGRAM_BOT_TOKEN` — токен бота от `@BotFather`.
   - `TELEGRAM_ADMIN_CHAT_ID` — ID чата/канала, куда бот будет слать заявки.
     - Можно узнать, добавив бота в нужный чат и воспользовавшись сторонними инструментами/ботами, либо временно залогировав `update.effective_chat.id`.
   - `TELEGRAM_PROXY` (опционально) — если с сервера не открывается `https://api.telegram.org` (типично для части датацентров в России): URL HTTP- или SOCKS5-прокси **вне РФ**. См. раздел ниже.
   - `API_BASE_URL` — URL бэкенда DBT Hub (например `https://hub.example.com`), для команды `/link`.
   - `TELEGRAM_WEBHOOK_SECRET` — общий секрет с PHP (`TELEGRAM_WEBHOOK_SECRET` в окружении бэкенда).

### Сервер в России: доступ к Telegram через прокси

С ботом всё в порядке, проблема в маршруте: **запросы к API Telegram** (`api.telegram.org`) с некоторых сетей из РФ не проходят или обрываются. Нужен **исходящий** прокси, через который Python будет ходить к Telegram.

**Что сделать**

1. Достать рабочий **HTTP(S)** или **SOCKS5**-прокси с endpoint **за пределами РФ** (любой удобный тебе способ):
   - платный «datacenter»-прокси с логином/паролем;
   - или **маленький VPS в ЕС/США**, на нём поднять `3proxy` / `dante` / `squid` и в файрволе разрешить только IP твоего российского сервера.
2. На российском сервере в `.env` (не коммить в git):

   ```env
   TELEGRAM_PROXY=http://user:password@host:port
   ```

   или для SOCKS5:

   ```env
   TELEGRAM_PROXY=socks5://user:password@host:port
   ```

   (если авторизации нет — без `user:password@`).

3. Убедиться, что зависимости с SOCKS стоят: `pip install -r requirements.txt` (в проекте уже указано `python-telegram-bot[socks]`).

4. Проверка с **того же** сервера до запуска бота:

   ```bash
   curl -v --max-time 15 -x "$TELEGRAM_PROXY" https://api.telegram.org/
   ```

   Должен прийти ответ HTTP (хотя бы страница API), а не таймаут.

Альтернатива без переменной в боте: **VPN/WireGuard на весь сервер**, чтобы весь исходящий трафик шёл через зарубежный узел — тогда `TELEGRAM_PROXY` можно не задавать, но это уже настройка ОС, не приложения.

## Запуск локально

```bash
cd telegram-bot
source venv/bin/activate      # или venv\Scripts\activate
python bot.py
```

Бот работает в режиме long polling и не требует отдельной настройки вебхуков.

## Запуск на сервере (systemd пример)

Предполагаем, что код бота лежит, например, в `/var/www/dbt-feedback-bot`.

1. Создай unit-файл, например `/etc/systemd/system/dbt-feedback-bot.service`:

   ```ini
   [Unit]
   Description=DBT Hub Telegram feedback bot
   After=network.target

   [Service]
   Type=simple
   WorkingDirectory=/var/www/dbt-feedback-bot
   ExecStart=/var/www/dbt-feedback-bot/venv/bin/python /var/www/dbt-feedback-bot/bot.py
   Restart=always
   RestartSec=3
   Environment=PYTHONUNBUFFERED=1

   [Install]
   WantedBy=multi-user.target
   ```

2. Перезагрузи конфигурацию systemd и запусти сервис:

   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable --now dbt-feedback-bot.service
   sudo systemctl status dbt-feedback-bot.service
   ```

После этого бот будет автоматически стартовать вместе с сервером и работать в фоне.

