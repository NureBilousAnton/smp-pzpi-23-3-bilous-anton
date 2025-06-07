<?php
function e($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}


function get_products() {
    try {
        return products_get_all();
    } catch (PDOException $e) {
        error_log("DB error occured: " . $e);
    }

    $_SESSION['error_message'] = 'Не вийшло отримати товари магазину.';
    return [];
}


function login($username, $password) {
    try {
        $user = users_get_by_username($username);
    } catch (PDOException $e) {
        error_log("DB error occured: " . $e);
        return false;
    }

    if (!$user) {
        $_SESSION['error_message'] = 'Користувача не існує в системі!';
        return false;
    } else if(!password_verify($password, $user['password_hash'])) {
        $_SESSION['error_message'] = 'Невірний пароль!';
        return false;
    }

    $_SESSION['user'] = $user;
    $_SESSION['message'] = 'Ласкаво просимо, ' . ($user['name'] ?: 'Гість') . '!';
    return true;
}

function logout() {
    // NOTE: don't session_destroy(), kills the message
    if (isset($_SESSION['user'])) {
        unset($_SESSION['user']);
        $_SESSION['message'] = "Ви вийшли з системи.";
        return true;
    } else {
        error_log("logout() got called when user is not authenticated!");
    }
    return false;
}

function update_profile($data) {
    // update local values
    foreach (['username', 'name', 'surname', 'birth_date', 'description'] as $field) {
        if (isset($data[$field])) {
            $_SESSION['user'][$field] = $data[$field];
        }
    }

    $errors = [];

    // upload pfp to server
    $pfp_file = $_FILES['pfp'];
    $_SESSION['user']['pfp_name'] ??= uniqid('pfp_');
    if (isset($_FILES['pfp']) && $pfp_file['error'] != UPLOAD_ERR_NO_FILE) {
        if (!in_array($pfp_file['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
            $errors[] = "Некоректний тип файлу аватара! Дозволені: JPG, PNG, GIF.";
        } else if ($pfp_file['size'] > 4 * 1024 * 1024) {
            $errors[] = "Файл занадто великий! Максимальний розмір: 4 MB.";
        } else if ($pfp_file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Помилка завантаження файлу! Код помилки: " . $pfp_file['error'];
        } else if (!move_uploaded_file($pfp_file['tmp_name'], UPLOADS_DIR . $_SESSION['user']['pfp_name'])) {
            $errors[] = "Не вдалося завантажити картинку профілю!";
        }
    }

    // local checks
    if (empty($_SESSION['user']['username'])) $errors[] = "Логін не може бути порожнім!";
    if (empty($_SESSION['user']['name'])) $errors[] = "Ім'я не може бути порожнє!";
    if (empty($_SESSION['user']['surname'])) $errors[] = "Прізвище не може бути порожнє!";
    if (!empty($_SESSION['user']['birth_date'])
        && strtotime($_SESSION['user']['birth_date']) > strtotime('-16 years')) {
        $errors[] = "Користувачеві має бути не менше 16 років!";
    }
    if (mb_strlen($_SESSION['user']['description']) > 200) {
        $errors[] = "Опис має містити не більше 200 символів!";
    }

    if (!empty($errors)) {
        $_SESSION['error_list'] = $errors;
        return false;
    }

    // remote checks
    try {
        $user = users_get_by_username($_SESSION['user']['username']);
        if ($user && $user['id'] != $_SESSION['user']['id']) {
            $_SESSION['error_message'] = 'Логін вже зайнятий!';
            return false;
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Не вийшло перевірити логін на доступність.';
        error_log("DB error occured: " . $e);
        return false;
    }

    // update remote data
    try {
        users_update($_SESSION['user']);
    } catch (PDOException $e) {
        error_log("DB error occured: " . $e);
        $_SESSION['error_message'] = 'Не вийшло зберегти дані.';
        return false;
    }

    $_SESSION['message'] = "Профіль успішно оновлено.";
    return true;
}


function add_to_cart($product_id, $quantity) {
    if ($quantity < 1) {
        $_SESSION['error_message'] = 'Не можна купити менше одного товару!';
        return false;
    }
    if ($quantity > 99) {
        $_SESSION['error_message'] = 'Не можна купити більше 99 товарів!';
        return false;
    }

    try {
        $product = products_get_by_id($product_id);
        if (!$product) {
            $_SESSION['error_message'] = 'Товар не зайдено!';
            return false;
        }

        if (cart_insert_or_update($_SESSION['user']['id'], $product_id, $quantity)) {
            $_SESSION['success_message'] = 'Товар "' . $product['name'] . '" додано до кошику.';
            return true;
        }
    } catch (PDOException $e) {
        error_log("DB error occured: " . $e);
    }

    $_SESSION['error_message'] = 'Не вийшло додати товар до кошику.';
    return false;
}

function get_cart() {
    try {
        return cart_fetch($_SESSION['user']['id']);
    } catch (PDOException $e) {
        error_log("DB error occured: " . $e);
    }

    $_SESSION['error_message'] = 'Не вийшло отримати товари в кошику.';
    return [];
}

function calculate_cart_total($cart_items) {
    $cart_total = 0;
    foreach ($cart_items as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }
    return $cart_total;
}

function remove_from_cart($product_id) {
    try {
        if (cart_delete($_SESSION['user']['id'], $product_id)) {
            $_SESSION['success_message'] = 'Товар видалено з кошика.';
            return true;
        }
    } catch (PDOException $e) {
        error_log("DB error occured: " . $e);
    }

    $_SESSION['error_message'] = 'Не вийшло видалити товар з кошику.';
    return false;
}

function clear_cart() {
    try {
        if (cart_delete_all($_SESSION['user']['id'])) {
            $_SESSION['success_message'] = 'Кошик очищено.';
            return true;
        }
    } catch (PDOException $e) {
        error_log("DB error occured: " . $e);
    }

    $_SESSION['error_message'] = 'Не вийшло очистити кошик.';
    return false;
}
