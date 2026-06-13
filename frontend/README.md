# Фронтенд — Vue 3 + Vite + Tailwind

SPA для личного кабинета dbt hub. Работает с бэкендом Yii2 REST API.

## Установка

```bash
cd frontend
npm install
```

## Разработка

1. Запустите бэкенд (Yii2) на порту 8080 (или укажите в `.env`):

   ```bash
   # из корня проекта
   php -S localhost:8080 -t web
   ```

2. Скопируйте `.env.example` в `.env` и при необходимости задайте `VITE_API_URL` (если бэкенд не на 8080).

3. Запустите фронтенд (прокси на бэкенд уже настроен в `vite.config.js` на 8080):

   ```bash
   npm run dev
   ```

   Откройте http://localhost:5173

## Сборка

```bash
npm run build
```

Статика будет в `dist/`. Разместите её на веб-сервере и настройте прокси запросов `/api` на PHP-бэкенд.

## Деплой на dbthub.ru

1. **Соберите фронт на сервере:**
   ```bash
   cd /var/www/dbt/frontend
   npm install
   npm run build
   ```

2. **Настройте Nginx** по примеру из `nginx-dbthub.conf.example`:
   - Скопируйте конфиг: `cp nginx-dbthub.conf.example /etc/nginx/sites-available/dbthub.ru`
   - Укажите свой сокет PHP-FPM (например `php8.1-fpm.sock` или `php8.2-fpm.sock`) в блоке `location /api`
   - Включите сайт: `ln -s /etc/nginx/sites-available/dbthub.ru /etc/nginx/sites-enabled/`
   - Проверьте и перезагрузите: `nginx -t && systemctl reload nginx`

3. **Проверьте пути** в конфиге: `root` должен указывать на `/var/www/dbt/frontend/dist`, а в `location /api` — путь к `web/index.php` вашего Yii2 (например `/var/www/dbt/web/index.php`).

4. Для **HTTPS** раскомментируйте блок с `listen 443` и настройте сертификат (например Let's Encrypt: `certbot --nginx -d dbthub.ru`).

После этого сайт будет доступен по адресу **https://dbthub.ru** (или http до настройки SSL).

## Переменные окружения

- `VITE_API_URL` — базовый URL API (по умолчанию пусто; при dev с proxy запросы идут на тот же хост).
