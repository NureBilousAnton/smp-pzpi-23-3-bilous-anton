<?php
if (isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'logout':
            logout();
            header('Location: index.php?page=products');
            exit;

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

        case 'update_profile':
            update_profile($_POST);
            header('Location: index.php?page=profile');
            exit;
    }
}


$cart_items = get_cart(); // needed for cart.phtml
$cart_item_count = count($cart_items); // and header.phtml also
include 'tpl/header.phtml';

switch ($_GET['page'] ?? 'products') {
    case 'products':
        $products = get_products();
        include 'tpl/products.phtml';
        break;

    case 'cart':
        $cart_total = calculate_cart_total($cart_items);
        include 'tpl/cart.phtml';
        break;

    case 'profile':
        include 'tpl/profile.phtml';
        break;

    default:
        include 'tpl/404.phtml';
        break;
}
