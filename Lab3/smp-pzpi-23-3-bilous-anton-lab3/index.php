<?php
session_start();

require_once 'db.php';
require_once 'functions.php';

init_db();


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
