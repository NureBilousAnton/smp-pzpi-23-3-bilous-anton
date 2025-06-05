<?php
session_start();

require_once 'db.php';
require_once 'functions.php';

init_db();

function e($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'add_to_cart':
            if (isset($_POST['product_id'])) {
                add_to_cart($_POST['product_id'], $_POST['quantity'] ?? 1);
            }
            header('Location: index.php?page=products');
            exit;

        case 'remove_from_cart':
            if (isset($_POST['product_id'])) {
                remove_from_cart($_POST['product_id']);
            }
            header('Location: index.php?page=cart');
            exit;

        case 'clear_cart':
            clear_cart();
            header('Location: index.php?page=cart');
            exit;
    }
}


$cart_item_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
foreach (['message', 'error_message', 'success_message'] as $msg) {
    $$msg = $_SESSION[$msg];
    unset($_SESSION[$msg]);
}

include 'tpl/header.phtml';

switch ($_GET['page'] ?? 'products') {
    case 'cart':
        $cart_items = get_cart_items();
        $cart_total = get_cart_total();
        include 'tpl/cart.phtml';
        break;
    case 'products':
    default:
        $products = get_all_products();
        include 'tpl/products.phtml';
        break;
}

include 'tpl/footer.phtml';
?>
