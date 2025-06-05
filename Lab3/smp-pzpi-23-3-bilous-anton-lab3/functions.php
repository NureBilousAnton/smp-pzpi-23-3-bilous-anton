<?php
require_once 'db.php';

function add_to_cart($product_id, $quantity) {
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;

    if ($quantity < 1) {
        $_SESSION['error_message'] = "Не можна купити менше одного товару!";
        return;
    }
    if ($quantity > 99) {
        $_SESSION['error_message'] = "Не можна купити більше 99 товарів!";
        return;
    }

    $_SESSION['cart'] ??= [];

    $product = get_product_by_id($product_id);
    if (!$product) {
        $_SESSION['error_message'] = "Продукт не зайдено!";
        return;
    }

    $_SESSION['cart'][$product_id] ??= 0;
    $_SESSION['cart'][$product_id] += $quantity;
    $_SESSION['success_message'] = $product['name'] . " додано до кошику.";
}

function get_cart_items() {
    if (empty($_SESSION['cart'])) {
        return [];
    }

    $cart_items = [];
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        if ($quantity == 0) {
            unset($_SESSION['cart'][$product_id]);
            continue;
        }

        $product = get_product_by_id($product_id);
        if (!$product) {
            continue;
        }

        $cart_items[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'image_name' => $product['image_name'],
            'quantity' => $quantity,
            'subtotal' => $product['price'] * $quantity,
        ];
    }

    return $cart_items;
}

function remove_from_cart($product_id) {
    if (isset($_SESSION['cart'][(int)$product_id])) {
        unset($_SESSION['cart'][(int)$product_id]);
        $_SESSION['success_message'] = "Товар видалено з кошика.";
    }
}

function clear_cart() {
    $_SESSION['cart'] = [];
    $_SESSION['success_message'] = "Кошик очищено.";
}

function get_cart_total() {
    $total = 0;
    $cart_items = get_cart_items();
    foreach ($cart_items as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}
?>
