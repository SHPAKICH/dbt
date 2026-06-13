# База данных системы контроля сотрудников сети кафе

## Описание

База данных MySQL для корпоративной системы контроля сотрудников сети кафе. Система поддерживает иерархию должностей и управление точками (кафе).

## Структура базы данных

### Таблица `locations` (Точки)

Хранит информацию о точках (кафе) сети.

**Поля:**
- `id` - Уникальный идентификатор точки
- `name` - Название точки
- `address` - Адрес точки
- `phone` - Телефон точки
- `is_active` - Флаг активности точки
- `created_at` - Дата создания записи
- `updated_at` - Дата последнего обновления

### Таблица `users` (Пользователи/Сотрудники)

Хранит информацию о сотрудниках системы.

**Поля:**
- `id` - Уникальный идентификатор пользователя
- `phone` - Номер телефона (уникальный)
- `email` - Электронная почта (уникальная)
- `password_hash` - Хеш пароля (используйте password_hash() или bcrypt)
- `position` - Должность (ENUM):
  - `manager` - Управляющий
  - `location_manager` - Менеджер точки
  - `senior_teamaker` - Старший тимейкер
  - `teamaker` - Тимейкер
  - `trainee` - Стажер
- `location_id` - ID точки закрепления (может быть NULL)
- `avatar` - Путь к файлу аватарки (может быть NULL)
- `first_name` - Имя сотрудника
- `last_name` - Фамилия сотрудника
- `is_active` - Флаг активности пользователя
- `created_at` - Дата создания записи
- `updated_at` - Дата последнего обновления

### Таблица `manager_locations` (Связь управляющих с точками)

Связывает управляющих с точками, которые они контролируют (many-to-many).

**Поля:**
- `id` - Уникальный идентификатор связи
- `manager_id` - ID управляющего (FK на users)
- `location_id` - ID точки (FK на locations)
- `created_at` - Дата создания записи

## Иерархия должностей и права доступа

### Управляющий (`manager`)
- Может контролировать **несколько точек** через таблицу `manager_locations`
- Может создавать учетные записи сотрудников **не выше должности "Менеджер точки"**
- Может закреплять сотрудников за точками
- В таблице `users` поле `location_id` для управляющего обычно **NULL**

### Менеджер точки (`location_manager`)
- Закреплен за **одной точкой** через поле `location_id` в таблице `users`
- Может изменять данные **только своей точки**
- Не может создавать учетные записи других менеджеров или управляющих

### Старший тимейкер (`senior_teamaker`)
- Может быть закреплен за точкой через `location_id`
- Обычно имеет расширенные права по сравнению с тимейкером

### Тимейкер (`teamaker`)
- Может быть закреплен за точкой через `location_id`
- Базовые права сотрудника

### Стажер (`trainee`)
- Может быть закреплен за точкой через `location_id`
- Ограниченные права

## Установка

1. Создайте базу данных (если еще не создана):
```sql
CREATE DATABASE IF NOT EXISTS cafe_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Выполните скрипт создания структуры:
```bash
mysql -u root -p cafe_management < database/schema.sql
```

3. (Опционально) Загрузите тестовые данные:
```bash
mysql -u root -p cafe_management < database/seed_data.sql
```

## Примеры запросов

### Получить всех управляющих и их точки
```sql
SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.email,
    GROUP_CONCAT(l.name SEPARATOR ', ') as locations
FROM users u
LEFT JOIN manager_locations ml ON u.id = ml.manager_id
LEFT JOIN locations l ON ml.location_id = l.id
WHERE u.position = 'manager'
GROUP BY u.id;
```

### Получить всех сотрудников точки с их должностями
```sql
SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.position,
    u.email,
    u.phone,
    l.name as location_name
FROM users u
LEFT JOIN locations l ON u.location_id = l.id
WHERE u.location_id = 1
ORDER BY 
    FIELD(u.position, 'location_manager', 'senior_teamaker', 'teamaker', 'trainee');
```

### Получить менеджера точки
```sql
SELECT 
    u.*,
    l.name as location_name,
    l.address
FROM users u
JOIN locations l ON u.location_id = l.id
WHERE u.position = 'location_manager' 
  AND u.location_id = 1;
```

## Примечания

- Все пароли должны храниться в виде хешей (используйте `password_hash()` в PHP или bcrypt)
- Телефон и email должны быть уникальными
- Управляющий не должен иметь `location_id` (используйте таблицу `manager_locations` для связи с точками)
- Менеджер точки должен иметь `location_id` и не должен быть в таблице `manager_locations`



