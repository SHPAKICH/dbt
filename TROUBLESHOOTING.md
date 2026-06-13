# Решение проблем со входом

## Быстрое решение

### 1. Создайте тестового пользователя через SQL

Выполните SQL скрипт:
```bash
mysql -u ваш_пользователь -p ваша_база < database/create_test_user.sql
```

Или откройте файл `database/create_test_user.sql` и выполните его вручную в MySQL.

### 2. Создайте пользователя через веб-интерфейс

Откройте в браузере:
```
http://ваш-сайт/create-test-user.php
```

### 3. Проверьте данные для входа

**Email:** `manager@cafe.ru`  
**Телефон:** `+7 (999) 111-11-11`  
**Пароль:** `password123`

---

## Частые проблемы

### Проблема 1: "Неверный email/телефон или пароль"

**Причины:**
- Пользователь не существует в БД
- Пароль неправильно захеширован
- Поле `auth_key` отсутствует в таблице

**Решение:**
1. Проверьте, существует ли пользователь:
```sql
SELECT * FROM users WHERE email = 'manager@cafe.ru';
```

2. Если пользователь есть, но пароль не работает, пересоздайте его через SQL скрипт выше

3. Проверьте наличие поля `auth_key`:
```sql
DESCRIBE users;
```
Если поля нет, выполните:
```sql
ALTER TABLE users ADD COLUMN auth_key VARCHAR(32) DEFAULT NULL AFTER last_name;
```

### Проблема 2: Пользователь не активен

Проверьте поле `is_active`:
```sql
SELECT id, email, is_active FROM users WHERE email = 'manager@cafe.ru';
```

Если `is_active = 0`, активируйте:
```sql
UPDATE users SET is_active = 1 WHERE email = 'manager@cafe.ru';
```

### Проблема 3: Ошибка подключения к БД

Проверьте файл `config/db.php` - правильные ли данные подключения.

### Проблема 4: Таблицы не созданы

Выполните основной скрипт создания БД:
```bash
mysql -u ваш_пользователь -p ваша_база < database/schema.sql
```

---

## Отладка

Включите режим отладки в `config/web.php` (если еще не включен):
```php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
```

Проверьте логи в `runtime/logs/app.log` - там будут детали ошибок.

---

## Проверка через консоль (если доступна)

```bash
php yii test-login
```

Или создайте пользователя:
```bash
php yii create-test-user
```

