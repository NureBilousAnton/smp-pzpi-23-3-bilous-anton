#!/usr/bin/env php
<?php

echo <<< EOF
    ################################
    # ПРОДОВОЛЬЧИЙ МАГАЗИН "ВЕСНА" #
    ################################

    EOF;

$main_menu = <<< EOF

    1 Вибрати товари
    2 Отримати підсумковий рахунок
    3 Налаштувати свій профіль
    0 Вийти з програми
    Введіть команду: 
    EOF;

$products = [ // [name, price]
    ['Молоко пастеризоване',    12],
    ['Хліб чорний',             9],
    ['Сир білий',               21],
    ['Сметана 20%',             25],
    ['Кефір 1%',                19],
    ['Вода газована',           18],
    ['Печиво "Весна"',          14],
];

$cart = [ /* product id => amount */ ];

// pad string with spaces at the end to the length
function pad($str, $len) {
    return $str . str_repeat(' ', max(0, $len - mb_strlen($str)));
}

$products_menu = PHP_EOL;

// find longest product name
$pad_name = 0;
$products_number = 0;
foreach ($products as $product) {
    $pad_name = max($pad_name, mb_strlen($product[0]));
    ++$products_number;
}

$pad_name += 2;
$pad_number = strlen($products_number) + 2;

$products_menu .= pad('№', $pad_number) . pad('НАЗВА', $pad_name) . 'ЦІНА' . PHP_EOL;
for ($i = 0; $i < $products_number; ++$i) {
    $products_menu .= pad($i + 1, $pad_number) . pad($products[$i][0],
        $pad_name) . $products[$i][1] . PHP_EOL;
}
$products_menu .= pad('', $pad_number) . '-----------' . PHP_EOL;
$products_menu .= pad('0', $pad_number) . 'ПОВЕРНУТИСЯ' . PHP_EOL;


while (true) {
    switch (trim(readline($main_menu))) {
        case '0': exit(0);
        case '1': shop();
            break;
        case '2': receipt();
            break;
        case '3': profile();
            break;
        default: echo 'ПОМИЛКА! Введіть правильну команду', PHP_EOL;
    }
}

function shop() {
    global $products_menu, $products_number, $products, $cart;

    while (true) {
        echo $products_menu;

        $choice = trim(readline('Виберіть товар: '));
        if ($choice == 0) { break; }

        if ($choice < 1 || $choice > $products_number) {
            echo 'ПОМИЛКА! ВКАЗАНО НЕПРАВИЛЬНИЙ НОМЕР ТОВАРУ', PHP_EOL;
            continue;
        }
        --$choice;

        while(true) {
            echo PHP_EOL, 'Вибрано: ', $products[$choice][0], PHP_EOL;

            $amount = trim(readline('Введіть кількість, штук: '));
            if ($amount < 0 || $amount >= 100) {
                echo 'ПОМИЛКА! Кількість товару має бути від 1 до 100', PHP_EOL;
                continue;
            }

            if ($amount == 0) {
                echo 'ВИДАЛЯЮ З КОШИКА', PHP_EOL;
                unset($cart[$choice]);
            } else if (array_key_exists($choice, $cart)) {
                $cart[$choice] += $amount;
            } else {
                $cart[$choice] = $amount;
            }
            break;
        }

        if (empty($cart)) {
            echo 'КОШИК ПОРОЖНІЙ', PHP_EOL;
            continue;
        }
        $pad_name = 0;
        foreach ($cart as $item => $_) {
            $pad_name = max($pad_name, mb_strlen($products[$item][0]));
        }
        $pad_name += 2;
        echo PHP_EOL, pad('НАЗВА', $pad_name), 'КІЛЬКІСТЬ', PHP_EOL;
        foreach ($cart as $item => $amount) {
            echo pad($products[$item][0], $pad_name), $amount, PHP_EOL;
        }
    }
}

function receipt() {
    global $products, $cart;

    if (empty($cart)) {
        echo 'КОШИК ПОРОЖНІЙ', PHP_EOL;
        return;
    }

    $pad_name = 0;
    $products_number = 0;
    foreach ($cart as $item => $_) {
        $pad_name = max($pad_name, mb_strlen($products[$item][0]));
        ++$products_number;
    }

    $pad_name += 2;
    $pad_number = strlen($products_number) + 2;


    echo PHP_EOL, pad('№', $pad_number), pad('НАЗВА', $pad_name), 'ЦІНА  КІЛЬКІСТЬ  ВАРТІСТЬ', PHP_EOL;

    $i = 0;
    $total = 0;
    foreach ($cart as $item => $amount) {
        $price = $products[$item][1];
        $total += $price * $amount;

        echo pad(++$i, $pad_number), pad($products[$item][0], $pad_name),
            pad($price, 6), pad($amount, 11), $price * $amount, PHP_EOL;
    }
    echo 'РАЗОМ ДО CПЛАТИ: ', $total, PHP_EOL;
}

function profile() {
    while (!preg_match('/[a-zа-яґїіє]/iu', trim(readline("\nВаше імʼя: ")))) { // u for utf8
        echo 'ПОМИЛКА! Імʼя користувача не може бути порожнім і повинно містити хоча б одну літеру', PHP_EOL;
    }

    $age = trim(readline('Ваш вік: '));
    while ($age < 7 || $age > 150) {
        echo 'ПОМИЛКА! Користувач не може бути молодшим 7-ми або старшим 150-ти років', PHP_EOL, PHP_EOL;
        $age = trim(readline('Ваш вік: '));
    }
}
