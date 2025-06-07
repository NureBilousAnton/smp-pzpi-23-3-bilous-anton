<?php
if (isset($_GET['action'])) {
    if ($_GET['action'] !== 'login') {
        $_SESSION['message404'] = 'Доступ до цієї дії обмежений.';
        header('Location: index.php?page=404');
        exit;
    }

    if (isset($_POST['username']) && isset($_POST['password'])
        && login($_POST['username'], $_POST['password'])) {
        header('Location: index.php?page=products');
        exit;
    }

    header('Location: index.php?page=login');
    exit;
}

include 'tpl/header.phtml';

switch ($_GET['page'] ?? 'products') {
    case 'login':
        include 'tpl/login.phtml';
        break;

    case 'products':
        $products = get_products();
        include 'tpl/products.phtml';
        break;

    case 'cart':
    case 'profile':
        $_SESSION['message404'] = 'Доступ до цієї сторінки обмежений.';
        // FALLTHROUGH
    case '404':
    default:
        include 'tpl/404.phtml';
        break;
}
