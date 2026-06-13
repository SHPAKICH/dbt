<?php

use yii\db\Migration;

/**
 * Импорт товаров из Excel-бланка заказа франчайзи (обновлённый на 09.10.2025).
 */
class m260325_000002_seed_products_from_excel extends Migration
{
    public function safeUp()
    {
        $this->delete('{{%products}}');

        $products = $this->getData();
        foreach ($products as $p) {
            $this->insert('{{%products}}', [
                'name' => $p['name'],
                'sku' => null,
                'unit' => $p['unit'],
                'category' => $p['category'],
                'price_per_unit' => $p['price'],
                'package_quantity' => $p['pkg_qty'],
                'package_description' => $p['pkg_desc'],
                'sort_order' => $p['sort'],
                'is_active' => 1,
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%products}}');
    }

    private function getData(): array
    {
        $cat1 = 'Ингредиенты';
        $cat2 = 'Витрина';
        $cat3 = 'Оборудование и расходные материалы';
        $cat4 = 'Можно приобрести со склада ДБТ';

        return [
            // === Ингредиенты ===
            ['name' => 'Тапиока', 'unit' => 'уп', 'category' => $cat1, 'price' => 649, 'pkg_qty' => '1', 'pkg_desc' => '20 кг (20 пачек)', 'sort' => 1],
            ['name' => 'Джус-боллы манго', 'unit' => 'уп', 'category' => $cat1, 'price' => 899, 'pkg_qty' => '1.3', 'pkg_desc' => '15,6 кг (12 банок)', 'sort' => 2],
            ['name' => 'Джус-боллы апельсин', 'unit' => 'уп', 'category' => $cat1, 'price' => 899, 'pkg_qty' => '1.3', 'pkg_desc' => '15,6 кг (12 банок)', 'sort' => 3],
            ['name' => 'Джус-боллы виноград', 'unit' => 'уп', 'category' => $cat1, 'price' => 899, 'pkg_qty' => '1.3', 'pkg_desc' => '15,6 кг (12 банок)', 'sort' => 4],
            ['name' => 'Джус-боллы клубника', 'unit' => 'уп', 'category' => $cat1, 'price' => 899, 'pkg_qty' => '1.3', 'pkg_desc' => '15,6 кг (12 банок)', 'sort' => 5],
            ['name' => 'Джус-боллы маракуйя', 'unit' => 'уп', 'category' => $cat1, 'price' => 899, 'pkg_qty' => '1.3', 'pkg_desc' => '15,6 кг (12 банок)', 'sort' => 6],
            ['name' => 'Джус-боллы йогурт', 'unit' => 'уп', 'category' => $cat1, 'price' => 899, 'pkg_qty' => '1.3', 'pkg_desc' => '15,6 кг (12 банок)', 'sort' => 7],
            ['name' => 'Джус-боллы персик', 'unit' => 'уп', 'category' => $cat1, 'price' => 899, 'pkg_qty' => '1.3', 'pkg_desc' => '15,6 кг (12 банок)', 'sort' => 8],
            ['name' => 'Джус-боллы зеленый виноград', 'unit' => 'уп', 'category' => $cat1, 'price' => 899, 'pkg_qty' => '1.3', 'pkg_desc' => '15,6 кг (12 банок)', 'sort' => 9],
            ['name' => 'Таро порошок', 'unit' => 'уп', 'category' => $cat1, 'price' => 1049, 'pkg_qty' => '1', 'pkg_desc' => '25 кг (25 пачек)', 'sort' => 10],
            ['name' => 'Кокосовое желе', 'unit' => 'уп', 'category' => $cat1, 'price' => 549, 'pkg_qty' => '1', 'pkg_desc' => '20 кг (20 пачек)', 'sort' => 11],
            ['name' => 'Агар агар сакура', 'unit' => 'уп', 'category' => $cat1, 'price' => 549, 'pkg_qty' => '1', 'pkg_desc' => '12 кг (12 пачек)', 'sort' => 12],
            ['name' => 'Клубничное пюре', 'unit' => 'уп', 'category' => $cat1, 'price' => 1129, 'pkg_qty' => '1.2', 'pkg_desc' => '14,4 кг (12 банок)', 'sort' => 13],
            ['name' => 'Апельсиновое пюре', 'unit' => 'уп', 'category' => $cat1, 'price' => 1129, 'pkg_qty' => '1.2', 'pkg_desc' => '14,4 кг (12 банок)', 'sort' => 14],
            ['name' => 'Грейпфрутовое пюре', 'unit' => 'уп', 'category' => $cat1, 'price' => 1129, 'pkg_qty' => '1.2', 'pkg_desc' => '14,4 кг (12 банок)', 'sort' => 15],
            ['name' => 'Гуава пюре', 'unit' => 'уп', 'category' => $cat1, 'price' => 1129, 'pkg_qty' => '1.2', 'pkg_desc' => '14,4 кг (12 банок)', 'sort' => 16],
            ['name' => 'Сироп маракуйя', 'unit' => 'уп', 'category' => $cat1, 'price' => 1129, 'pkg_qty' => '1.2', 'pkg_desc' => '14,4 кг (12 банок)', 'sort' => 17],
            ['name' => 'Пюре гранат', 'unit' => 'уп', 'category' => $cat1, 'price' => 1129, 'pkg_qty' => '1.2', 'pkg_desc' => '14,4 кг (12 банок)', 'sort' => 18],
            ['name' => 'Пюре манго', 'unit' => 'уп', 'category' => $cat1, 'price' => 1129, 'pkg_qty' => '1.2', 'pkg_desc' => '14,4 кг (12 банок)', 'sort' => 19],
            ['name' => 'Пюре алое и ромашка', 'unit' => 'уп', 'category' => $cat1, 'price' => 795, 'pkg_qty' => '1', 'pkg_desc' => '6 кг', 'sort' => 20],
            ['name' => 'Сироп клубника', 'unit' => 'уп', 'category' => $cat1, 'price' => 440, 'pkg_qty' => '1', 'pkg_desc' => '6 кг', 'sort' => 21],
            ['name' => 'Сироп блю кюрасао', 'unit' => 'уп', 'category' => $cat1, 'price' => 440, 'pkg_qty' => '1', 'pkg_desc' => '6 кг', 'sort' => 22],
            ['name' => 'Сироп карамель', 'unit' => 'уп', 'category' => $cat1, 'price' => 440, 'pkg_qty' => '1', 'pkg_desc' => '6 кг', 'sort' => 23],
            ['name' => 'Сироп гренадин', 'unit' => 'уп', 'category' => $cat1, 'price' => 440, 'pkg_qty' => '1', 'pkg_desc' => '6 кг', 'sort' => 24],
            ['name' => 'Фисташковый сироп', 'unit' => 'уп', 'category' => $cat1, 'price' => 440, 'pkg_qty' => '1', 'pkg_desc' => '6 кг', 'sort' => 25],
            ['name' => 'Сироп лесной орех', 'unit' => 'уп', 'category' => $cat1, 'price' => 440, 'pkg_qty' => '1', 'pkg_desc' => '6 кг', 'sort' => 26],
            ['name' => 'Шоколадный соус', 'unit' => 'уп', 'category' => $cat1, 'price' => 440, 'pkg_qty' => '1', 'pkg_desc' => '6 кг', 'sort' => 27],
            ['name' => 'Сироп сладкая мята', 'unit' => 'уп', 'category' => $cat1, 'price' => 440, 'pkg_qty' => '1', 'pkg_desc' => '6 кг', 'sort' => 28],
            ['name' => 'Соус карамельный', 'unit' => 'уп', 'category' => $cat1, 'price' => 1371, 'pkg_qty' => '1.2', 'pkg_desc' => '14,4 кг (12 банок)', 'sort' => 29],
            ['name' => 'Фисташковая паста', 'unit' => 'уп', 'category' => $cat1, 'price' => 1699, 'pkg_qty' => '0.5', 'pkg_desc' => '6 кг (12 банок)', 'sort' => 30],
            ['name' => 'Сухое молоко', 'unit' => 'уп', 'category' => $cat1, 'price' => 999, 'pkg_qty' => '1', 'pkg_desc' => '20 кг', 'sort' => 31],
            ['name' => 'Сахар тросниковый для тапиоки', 'unit' => 'уп', 'category' => $cat1, 'price' => 799, 'pkg_qty' => '0.8', 'pkg_desc' => '20 кг (25 пачек)', 'sort' => 32],
            ['name' => 'Сырный порошок', 'unit' => 'уп', 'category' => $cat1, 'price' => 899, 'pkg_qty' => '0.5', 'pkg_desc' => '20 кг (40 пачек)', 'sort' => 33],
            ['name' => 'Косточки маракуйи', 'unit' => 'уп', 'category' => $cat1, 'price' => 1258.80, 'pkg_qty' => '1.2', 'pkg_desc' => '14,4 кг (12 банок)', 'sort' => 34],
            ['name' => 'Чай Улун Камелия', 'unit' => 'уп', 'category' => $cat1, 'price' => 1049, 'pkg_qty' => '0.5', 'pkg_desc' => '10 кг (20 пачек)', 'sort' => 35],
            ['name' => 'Зелёный чай', 'unit' => 'уп', 'category' => $cat1, 'price' => 1049, 'pkg_qty' => '0.5', 'pkg_desc' => '10 кг (20 пачек)', 'sort' => 36],
            ['name' => 'Чай Улун Лапсанг Сушонг', 'unit' => 'уп', 'category' => $cat1, 'price' => 999, 'pkg_qty' => '0.5', 'pkg_desc' => '10 кг (20 пачек)', 'sort' => 37],
            ['name' => 'Фруктоза жидкая', 'unit' => 'уп', 'category' => $cat1, 'price' => 1845, 'pkg_qty' => '5', 'pkg_desc' => '20 кг (4 пачки)', 'sort' => 38],
            ['name' => 'Матча Premium', 'unit' => 'уп', 'category' => $cat1, 'price' => 3700, 'pkg_qty' => '0.5', 'pkg_desc' => '10 кг (20 пачек)', 'sort' => 39],
            ['name' => 'Черная карамель', 'unit' => 'уп', 'category' => $cat1, 'price' => 1699, 'pkg_qty' => '2.5', 'pkg_desc' => '20 кг (8 банок)', 'sort' => 40],
            ['name' => 'Лимонный концентрат', 'unit' => 'уп', 'category' => $cat1, 'price' => 1349, 'pkg_qty' => '2.5', 'pkg_desc' => '8', 'sort' => 41],
            ['name' => 'Смесь сытная для гонконгских вафель', 'unit' => 'уп', 'category' => $cat1, 'price' => 360, 'pkg_qty' => '1', 'pkg_desc' => '10 кг', 'sort' => 42],
            ['name' => 'Смесь сладкая для гонконгских вафель', 'unit' => 'уп', 'category' => $cat1, 'price' => 360, 'pkg_qty' => '1', 'pkg_desc' => '10 кг', 'sort' => 43],
            ['name' => 'Коллаген', 'unit' => 'шт', 'category' => $cat1, 'price' => 1550, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 44],
            ['name' => 'Б-комплекс', 'unit' => 'шт', 'category' => $cat1, 'price' => 1550, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 45],
            ['name' => 'Кофе зерновой', 'unit' => 'уп', 'category' => $cat1, 'price' => 2020, 'pkg_qty' => '1', 'pkg_desc' => '8 кг', 'sort' => 46],
            ['name' => 'Горячий молочный шоколад', 'unit' => 'уп', 'category' => $cat1, 'price' => 700, 'pkg_qty' => '1', 'pkg_desc' => '10', 'sort' => 47],
            ['name' => 'Крошка Орео', 'unit' => 'уп', 'category' => $cat1, 'price' => 325, 'pkg_qty' => '0.4', 'pkg_desc' => '9,6 кг (24 пачки)', 'sort' => 48],
            ['name' => 'Крошка печенья карамельная', 'unit' => 'уп', 'category' => $cat1, 'price' => 400, 'pkg_qty' => '0.4', 'pkg_desc' => '9,6 кг (24 пачки)', 'sort' => 49],
            ['name' => 'Кокосовая вода', 'unit' => 'уп', 'category' => $cat1, 'price' => 380, 'pkg_qty' => '1', 'pkg_desc' => '12', 'sort' => 50],

            // === Витрина ===
            ['name' => 'Маршмеллоу', 'unit' => 'уп', 'category' => $cat2, 'price' => 150, 'pkg_qty' => '1', 'pkg_desc' => 'кратно упаковке', 'sort' => 1],
            ['name' => 'Брелок ДБТ', 'unit' => 'шт', 'category' => $cat2, 'price' => 151, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 2],
            ['name' => 'Бусы с лентой', 'unit' => 'шт', 'category' => $cat2, 'price' => 95, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 3],
            ['name' => 'Авоська', 'unit' => 'шт', 'category' => $cat2, 'price' => 99, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 4],
            ['name' => 'Сумка DBT', 'unit' => 'шт', 'category' => $cat2, 'price' => 426, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 5],
            ['name' => 'Тонкие печенья DBT вкус Сакура', 'unit' => 'уп', 'category' => $cat2, 'price' => 280, 'pkg_qty' => '1', 'pkg_desc' => 'кратно упак (в коробке 24 шт)', 'sort' => 6],
            ['name' => 'Тонкие печенья DBT вкус Соль и Сыр', 'unit' => 'уп', 'category' => $cat2, 'price' => 280, 'pkg_qty' => '1', 'pkg_desc' => 'кратно упак (в коробке 24 шт)', 'sort' => 7],
            ['name' => 'Тонкие печенья DBT вкус Матча', 'unit' => 'уп', 'category' => $cat2, 'price' => 280, 'pkg_qty' => '1', 'pkg_desc' => 'кратно упак (в коробке 24 шт)', 'sort' => 8],
            ['name' => 'Лапша 140 гр в ассортименте', 'unit' => 'шт', 'category' => $cat2, 'price' => 130, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 9],
            ['name' => 'Шашлычки 68 гр', 'unit' => 'шт', 'category' => $cat2, 'price' => 60, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 10],
            ['name' => 'Шашлычки 85 гр', 'unit' => 'шт', 'category' => $cat2, 'price' => 120, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 11],
            ['name' => 'Чай с жасмином, 50 гр', 'unit' => 'шт', 'category' => $cat2, 'price' => 239, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 12],
            ['name' => 'Чай персиковый улун, 50 гр', 'unit' => 'шт', 'category' => $cat2, 'price' => 239, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 13],
            ['name' => 'Чай Тегуаньинь, 50 гр', 'unit' => 'шт', 'category' => $cat2, 'price' => 239, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 14],
            ['name' => 'Подарочный чайный набор', 'unit' => 'шт', 'category' => $cat2, 'price' => 399, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 15],
            ['name' => 'Лимонад DBT 0,330 мл клубника-юдзу', 'unit' => 'уп', 'category' => $cat2, 'price' => 85, 'pkg_qty' => '0.33', 'pkg_desc' => 'кратно упак (в коробке 12 шт)', 'sort' => 16],
            ['name' => 'Вода "Айленд О2" 0,5 л (газ.) (DBT)', 'unit' => 'уп', 'category' => $cat2, 'price' => 42, 'pkg_qty' => '0.33', 'pkg_desc' => 'кратно упак (в коробке 12 шт)', 'sort' => 17],
            ['name' => 'Вода "Айленд О2" 0,5 л (негаз.) (DBT)', 'unit' => 'уп', 'category' => $cat2, 'price' => 42, 'pkg_qty' => '0.5', 'pkg_desc' => 'кратно упак (в коробке 12 шт)', 'sort' => 18],
            ['name' => 'Хлебный снэк со вкусом морской соли и карамели', 'unit' => 'шт', 'category' => $cat2, 'price' => 70, 'pkg_qty' => '0.33', 'pkg_desc' => 'поштучно', 'sort' => 19],
            ['name' => 'Кукурузные шарики с сыром', 'unit' => 'шт', 'category' => $cat2, 'price' => 78, 'pkg_qty' => '0.35', 'pkg_desc' => 'поштучно', 'sort' => 20],
            ['name' => 'Сырные шарики со вкусом огурца', 'unit' => 'шт', 'category' => $cat2, 'price' => 78, 'pkg_qty' => '0.35', 'pkg_desc' => 'поштучно', 'sort' => 21],
            ['name' => 'Снэки со вкусом сыра', 'unit' => 'шт', 'category' => $cat2, 'price' => 80, 'pkg_qty' => '0.35', 'pkg_desc' => 'поштучно', 'sort' => 22],
            ['name' => 'Сырные снэки со вкусом васаби', 'unit' => 'шт', 'category' => $cat2, 'price' => 90, 'pkg_qty' => '0.35', 'pkg_desc' => 'поштучно', 'sort' => 23],
            ['name' => 'Термос с соломинкой', 'unit' => 'шт', 'category' => $cat2, 'price' => 750, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 24],
            ['name' => 'Термос-бутылка', 'unit' => 'шт', 'category' => $cat2, 'price' => 475, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 25],
            ['name' => 'Термокружка', 'unit' => 'шт', 'category' => $cat2, 'price' => 350, 'pkg_qty' => '1', 'pkg_desc' => 'поштучно', 'sort' => 26],
            ['name' => 'Брелок Металлический', 'unit' => 'шт', 'category' => $cat2, 'price' => 66, 'pkg_qty' => null, 'pkg_desc' => 'кратно 5 шт', 'sort' => 27],
            ['name' => 'Брелок Яркий', 'unit' => 'шт', 'category' => $cat2, 'price' => 68, 'pkg_qty' => null, 'pkg_desc' => 'кратно 5 шт', 'sort' => 28],
            ['name' => 'Брелок Светлый', 'unit' => 'шт', 'category' => $cat2, 'price' => 67, 'pkg_qty' => null, 'pkg_desc' => 'кратно 5 шт', 'sort' => 29],
            ['name' => 'Брелок-держатель', 'unit' => 'шт', 'category' => $cat2, 'price' => 122, 'pkg_qty' => null, 'pkg_desc' => 'кратно 5 шт', 'sort' => 30],

            // === Оборудование и расходные материалы ===
            ['name' => 'Стакан 0,5', 'unit' => 'шт', 'category' => $cat3, 'price' => 15.75, 'pkg_qty' => null, 'pkg_desc' => '500 шт/кор', 'sort' => 1],
            ['name' => 'Стакан 0,7', 'unit' => 'шт', 'category' => $cat3, 'price' => 15.75, 'pkg_qty' => null, 'pkg_desc' => '500 шт/кор', 'sort' => 2],
            ['name' => 'Стакан для мороженого 0,36', 'unit' => 'шт', 'category' => $cat3, 'price' => 12.50, 'pkg_qty' => null, 'pkg_desc' => '50 шт/уп', 'sort' => 3],
            ['name' => 'Стакан кофейный 0,4', 'unit' => 'шт', 'category' => $cat3, 'price' => 13.20, 'pkg_qty' => null, 'pkg_desc' => '500 шт/уп', 'sort' => 4],
            ['name' => 'Конверт для таяки', 'unit' => 'шт', 'category' => $cat3, 'price' => 1.63, 'pkg_qty' => null, 'pkg_desc' => 'поштучно', 'sort' => 5],
            ['name' => 'Пергамент ДБТ 28*31', 'unit' => 'уп', 'category' => $cat3, 'price' => 3.33, 'pkg_qty' => '1', 'pkg_desc' => 'кратно 50 шт', 'sort' => 6],
            ['name' => 'Коробка для кукиса', 'unit' => 'уп', 'category' => $cat3, 'price' => 43.75, 'pkg_qty' => '1', 'pkg_desc' => 'кратно 50 шт', 'sort' => 7],
            ['name' => 'Упаковка для вафель', 'unit' => 'уп', 'category' => $cat3, 'price' => 8, 'pkg_qty' => null, 'pkg_desc' => 'кратно 50 шт', 'sort' => 8],
            ['name' => 'Крышки', 'unit' => 'шт', 'category' => $cat3, 'price' => 3.15, 'pkg_qty' => null, 'pkg_desc' => '1000 шт', 'sort' => 9],
            ['name' => 'Крышки для бумажного стакана', 'unit' => 'шт', 'category' => $cat3, 'price' => 6.75, 'pkg_qty' => null, 'pkg_desc' => '1000 шт', 'sort' => 10],
            ['name' => 'Заглушки-сердечки на стаканы', 'unit' => 'шт', 'category' => $cat3, 'price' => 1, 'pkg_qty' => null, 'pkg_desc' => '500 шт', 'sort' => 11],
            ['name' => 'Трубочка DBT', 'unit' => 'шт', 'category' => $cat3, 'price' => 3.60, 'pkg_qty' => null, 'pkg_desc' => '100/2000 шт', 'sort' => 12],
            ['name' => 'Трубочка для миников', 'unit' => 'шт', 'category' => $cat3, 'price' => 2.40, 'pkg_qty' => null, 'pkg_desc' => '100/2000 шт', 'sort' => 13],
            ['name' => 'Пакеты одинарные для доставки', 'unit' => 'шт', 'category' => $cat3, 'price' => 6, 'pkg_qty' => null, 'pkg_desc' => '100 шт', 'sort' => 14],
            ['name' => 'Пакеты двойные для доставки', 'unit' => 'шт', 'category' => $cat3, 'price' => 7, 'pkg_qty' => null, 'pkg_desc' => '100 шт', 'sort' => 15],
            ['name' => 'Футболка DBT', 'unit' => 'шт', 'category' => $cat3, 'price' => 1000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 16],
            ['name' => 'Фартук', 'unit' => 'шт', 'category' => $cat3, 'price' => 1000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 17],
            ['name' => 'Рубашка DBT', 'unit' => 'шт', 'category' => $cat3, 'price' => 1300, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 18],
            ['name' => 'Кепка DBT', 'unit' => 'шт', 'category' => $cat3, 'price' => 700, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 19],
            ['name' => 'Бейдж для сотрудника', 'unit' => 'шт', 'category' => $cat3, 'price' => 210, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 20],
            ['name' => 'Китайский барный набор', 'unit' => 'шт', 'category' => $cat3, 'price' => 12285, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 21],
            ['name' => 'Слайсер', 'unit' => 'шт', 'category' => $cat3, 'price' => 16250, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 22],
            ['name' => 'Плитка для шоколада', 'unit' => 'шт', 'category' => $cat3, 'price' => 2800, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 23],
            ['name' => 'Вафельница для таяки со сменными панелями', 'unit' => 'шт', 'category' => $cat3, 'price' => 26000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 24],
            ['name' => 'Сменные панели для вафельницы таяки', 'unit' => 'шт', 'category' => $cat3, 'price' => 8750, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 25],
            ['name' => 'Диспенсер для Фруктозы', 'unit' => 'шт', 'category' => $cat3, 'price' => 24000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 26],
            ['name' => 'Машинка для запечатывания', 'unit' => 'шт', 'category' => $cat3, 'price' => 46095, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 27],
            ['name' => 'Лента для запаечной машины', 'unit' => 'шт', 'category' => $cat3, 'price' => 2500, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 28],
            ['name' => 'Мультиварка для тапиоки 5 л', 'unit' => 'шт', 'category' => $cat3, 'price' => 14175, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 29],
            ['name' => 'Мультиварка для тапиоки 12 л', 'unit' => 'шт', 'category' => $cat3, 'price' => 22000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 30],
            ['name' => 'Мультиварка для тапиоки 16 л', 'unit' => 'шт', 'category' => $cat3, 'price' => 23100, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 31],
            ['name' => 'Подогрев для тапиоки', 'unit' => 'шт', 'category' => $cat3, 'price' => 12600, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 32],
            ['name' => 'Кипятильник 35 л', 'unit' => 'шт', 'category' => $cat3, 'price' => 35000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 33],
            ['name' => 'Термос 10 л', 'unit' => 'шт', 'category' => $cat3, 'price' => 6195, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 34],
            ['name' => 'Блендер с крышкой', 'unit' => 'шт', 'category' => $cat3, 'price' => 48750, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 35],
            ['name' => 'Пейджеры для кафе и ресторанов', 'unit' => 'шт', 'category' => $cat3, 'price' => 14700, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 36],
            ['name' => 'Шейкер 1 литр', 'unit' => 'шт', 'category' => $cat3, 'price' => 735, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 37],
            ['name' => 'Милкшейкер электрический', 'unit' => 'шт', 'category' => $cat3, 'price' => 8295, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 38],
            ['name' => 'Шкаф холодильный Polair СМ 105-S', 'unit' => 'шт', 'category' => $cat3, 'price' => 60000, 'pkg_qty' => '1', 'pkg_desc' => '1', 'sort' => 39],
            ['name' => 'Ледогенератор средний', 'unit' => 'шт', 'category' => $cat3, 'price' => 157500, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 40],
            ['name' => 'Кофемолка', 'unit' => 'шт', 'category' => $cat3, 'price' => 65000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 41],
            ['name' => 'Кофемашина', 'unit' => 'шт', 'category' => $cat3, 'price' => 200000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 42],
            ['name' => 'Холодильный стол', 'unit' => 'шт', 'category' => $cat3, 'price' => 165000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 43],
            ['name' => 'Морозильный стол', 'unit' => 'шт', 'category' => $cat3, 'price' => 165000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 44],
            ['name' => 'Монитор диаг. 32', 'unit' => 'шт', 'category' => $cat3, 'price' => 36000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 45],
            ['name' => 'Монитор диаг. 43', 'unit' => 'шт', 'category' => $cat3, 'price' => 41000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 46],
            ['name' => 'Кронштейн', 'unit' => 'шт', 'category' => $cat3, 'price' => 3000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 47],
            ['name' => 'Экран для меню', 'unit' => 'шт', 'category' => $cat3, 'price' => 27000, 'pkg_qty' => null, 'pkg_desc' => '1', 'sort' => 48],

            // === Можно приобрести со склада ДБТ ===
            ['name' => 'Крем на раст.сливках Шантипак', 'unit' => 'уп', 'category' => $cat4, 'price' => 336, 'pkg_qty' => '1', 'pkg_desc' => '12 шт в коробке', 'sort' => 1],
            ['name' => 'Сироп соленая карамель RC 1 л', 'unit' => 'уп', 'category' => $cat4, 'price' => 440, 'pkg_qty' => '1', 'pkg_desc' => '6 шт в коробке', 'sort' => 2],
            ['name' => 'Сироп банан RC 1 л', 'unit' => 'уп', 'category' => $cat4, 'price' => 440, 'pkg_qty' => '1', 'pkg_desc' => '6 шт в коробке', 'sort' => 3],
        ];
    }
}
