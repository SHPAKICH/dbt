import csv
from pathlib import Path

csv_path = Path(r"c:\Users\Ира\Downloads\546390_waiters_export - employees.csv")
out_path = Path(r"c:\Users\Ира\Desktop\diplom\sql\seed_from_waiters_insert_only_20260320.sql")

position_map = {
    "Менеджер": "location_manager",
    "Партнер": "teamaker",
    "Старший тимейкер": "senior_teamaker",
    "Тестер": "teamaker",
    "Тимейкер": "teamaker",
    "Управляющий": "manager",
}

location_map = {
    "DBT 5 Столиц": "5 Столиц",
    "DBT Атлантик Сити": "Атлантик Сити",
    "DBT Василеостровский": "Василеостровский",
    "DBT Галерея Чижова": "Галерея Чижова",
    "DBT Гончарная": "Гончарная",
    "DBT Гороховая": "Гороховая",
    "DBT Горьковская": "Горьковская",
    "DBT Гулливер": "Гулливер",
    "DBT Европолиc": "Европолис",
    "DBT Жемчужная Плаза": "Жемчужная Плаза",
    "DBT Июнь": "Июнь",
    "DBT Калининград": "Калининград",
    "DBT Каменоостровская 39": "Каменоостровская 39",
    "DBT Кирочная 27": "Кирочная 27",
    "DBT Колпино": "Колпино",
    "DBT Краснознаменск": "Краснознаменск",
    "DBT Легенда": "Легенда",
    "DBT Леомолл": "Леомолл",
    "DBT Лондон Молл": "Лондон Молл",
    "DBT Марата": "Марата",
    "DBT Мега Парнас": "Мега Парнас",
    "DBT Меркурий": "Меркурий",
    "DBT Московская 191": "Московская 191",
    "DBT Новослободская Москва": "Новослободская Москва",
    "DBT Омск": "Омск",
    "DBT Охта Молл": "Охта Молл",
    "DBT ПИК": "ПИК",
    "DBT Парк Победы": "Парк Победы",
    "DBT Петергоф": "Петергоф",
    "DBT Петрозаводск": "Петрозаводск",
    "DBT ПитерЛэнд": "ПитерЛэнд",
    "DBT Радуга": "ТЦ Радуга",
    "DBT Родео Драйв": "Родео Драйв",
    "DBT Южный полюс": "Южный полюс",
    "DBt Ломоносова 1": "Ломоносова 1",
    "DBt Пр-кт Стачек": "Стачек",
    "DBt Сенная": "Сенная",
    "DBt Сити молл": "Сити молл",
    "DBt ТРК Лето": "ТРК Лето",
    "DBt Ул. Бухарестская": "Бухарестская",
    "DBt Чкаловская": "Чкаловская",
    "DBt ломо": "Ломоносова 12/66",
    "Dbt 5-ая Авеню": "5е Авеню",
    "Dbt АкадемПарк": "АкадемПарк",
    "Dbt Мозайка": "Мозайка",
    "Dbt прометей": "Прометей",
    "Офис": "Офис",
    "ТК Парнас": "ТК Парнас",
    "ТЦ Небо": "ТЦ Небо",
}

DEFAULT_HASH = "$2y$12$/mI.tbsHNtyrxxdM2FhUSOUA.w.fligqMQpVC6ilcpiPWDyem.X2S"


def sq(value: str) -> str:
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def norm_space(value: str) -> str:
    return " ".join((value or "").replace("\r", " ").replace("\n", " ").split())


def normalize_phone(raw: str, idx: int) -> str:
    digits = "".join(ch for ch in (raw or "") if ch.isdigit())
    if not digits:
        return f"+7999{idx:07d}"
    if len(digits) == 10:
        return "+7" + digits
    if len(digits) == 11 and digits[0] in ("7", "8"):
        return "+7" + digits[-10:]
    return "+" + digits


with csv_path.open("r", encoding="utf-8-sig", newline="") as f:
    rows = list(csv.reader(f))

data_rows = rows[3:]
prepared = []
location_names = set()

for i, row in enumerate(data_rows, start=1):
    row = (row + ["", "", "", "", ""])[:5]
    fio_raw, pos_raw, rest_raw, phone_raw, email_raw = row

    fio = norm_space(fio_raw)
    if not fio:
        continue

    parts = fio.split(" ")
    first_name = parts[0]
    last_name = " ".join(parts[1:]) if len(parts) > 1 else ""

    pos = position_map.get(norm_space(pos_raw), "teamaker")
    rest_alias = norm_space(rest_raw)
    location_name = location_map.get(rest_alias, rest_alias)
    if location_name:
        location_names.add(location_name)

    email = norm_space(email_raw).lower()
    if not email:
        email = f"import+{i:04d}@placeholder.local"

    phone = normalize_phone(phone_raw, i)

    prepared.append(
        {
            "idx": i,
            "first_name": first_name,
            "last_name": last_name,
            "position": pos,
            "location_name": location_name,
            "email": email,
            "phone": phone,
        }
    )

# Убираем дубли внутри файла по email/phone (оставляем первую запись)
seen_email = set()
seen_phone = set()
users = []
for item in prepared:
    if item["email"] in seen_email or item["phone"] in seen_phone:
        continue
    seen_email.add(item["email"])
    seen_phone.add(item["phone"])
    users.append(item)

manager_links = [
    (u["email"], u["phone"], u["location_name"])
    for u in users
    if u["position"] == "manager" and u["location_name"]
]

loc_lines = []
for name in sorted(location_names):
    loc_lines.append(
        f"SELECT {sq(name)} AS `name`, NULL AS `address`, NULL AS `phone`, 1 AS `is_active`, '#2b2b2b' AS `color`, NOW() AS `created_at`, NOW() AS `updated_at`"
    )

user_values = []
for u in users:
    if u["position"] == "manager":
        loc_expr = "NULL"
    else:
        loc_expr = f"(SELECT id FROM locations WHERE name={sq(u['location_name'])} LIMIT 1)"
    user_values.append(
        "("
        + ", ".join(
            [
                sq(u["phone"]),
                sq(u["email"]),
                sq(DEFAULT_HASH),
                sq(u["position"]),
                loc_expr,
                sq(u["first_name"]),
                sq(u["last_name"]),
                "1",
                "0",
                "LEFT(REPLACE(UUID(), '-', ''), 32)",
            ]
        )
        + ")"
    )

manager_values = []
for email, phone, loc in manager_links:
    manager_values.append(
        f"((SELECT id FROM users WHERE email={sq(email)} AND phone={sq(phone)} LIMIT 1), (SELECT id FROM locations WHERE name={sq(loc)} LIMIT 1), NOW())"
    )

sql = []
sql.append("-- Автосгенерировано из 546390_waiters_export - employees.csv")
sql.append("-- Только INSERT INTO: locations, users, manager_locations")
sql.append("SET NAMES utf8mb4;")
sql.append("")
sql.append("-- 1) Локации")
sql.append("INSERT INTO `locations` (`name`, `address`, `phone`, `is_active`, `color`, `created_at`, `updated_at`)")
sql.append("SELECT src.`name`, src.`address`, src.`phone`, src.`is_active`, src.`color`, src.`created_at`, src.`updated_at")
sql.append("FROM (")
sql.append("    " + "\n    UNION ALL\n    ".join(loc_lines))
sql.append(") src")
sql.append("LEFT JOIN `locations` l ON l.`name` = src.`name`")
sql.append("WHERE l.`id` IS NULL;")
sql.append("")
sql.append("-- 2) Юзеры")
sql.append(
    "INSERT INTO `users` (`phone`, `email`, `password_hash`, `position`, `location_id`, `first_name`, `last_name`, `is_active`, `is_admin`, `auth_key`) VALUES"
)
sql.append("    " + ",\n    ".join(user_values) + ";")
sql.append("")
sql.append("-- 3) Привязки управляющих")
if manager_values:
    sql.append("INSERT IGNORE INTO `manager_locations` (`manager_id`, `location_id`, `created_at`) VALUES")
    sql.append("    " + ",\n    ".join(manager_values) + ";")
else:
    sql.append("-- Нет управляющих для вставки в manager_locations")
sql.append("")
sql.append(
    "SELECT COUNT(*) AS imported_placeholder_emails FROM `users` WHERE `email` LIKE 'import+%@placeholder.local';"
)

out_path.write_text("\n".join(sql), encoding="utf-8")
print(f"written={out_path}")
print(f"users={len(users)} locations={len(location_names)} manager_links={len(manager_links)}")
