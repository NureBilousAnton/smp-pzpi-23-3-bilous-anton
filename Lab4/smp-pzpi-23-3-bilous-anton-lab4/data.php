<?php
define('DB_FILE', __DIR__ . '/storage.db');
define('UPLOADS_DIR', __DIR__ . '/uploads/');

if (!is_dir(UPLOADS_DIR) && !mkdir(UPLOADS_DIR, 0775, true)) {
    exit('Failed to create uploads/ directory!');
}

function get_pdo() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // throw exeptions
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // parse into dicts
        } catch (PDOException $e) {
            exit("DB connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}


function products_get_all() {
    static $query = null;

    if ($query === null) {
        $query = get_pdo()->prepare("SELECT * FROM Products ORDER BY price ASC");
    }

    $query->execute();
    return $query->fetchAll(); // [] if none
}

function products_get_by_id($id) {
    static $query = null;

    if ($query === null) {
        $query = get_pdo()->prepare("SELECT * FROM Products WHERE id = ?");
    }

    $query->execute([$id]);
    return $query->fetch(); // false if not found
}


function users_insert($username, $password) {
    static $query = null;

    if ($query === null) {
        $query = get_pdo()->prepare("INSERT INTO Users (username, password_hash) VALUES (?, ?)");
    }

    $query->execute([$username,  $password]);
    return $query->rowCount() > 0; // true if inserted
}

function users_get_by_id($id) {
    static $query = null;

    if ($query === null) {
        $query = get_pdo()->prepare("SELECT * FROM Users WHERE id = ?");
    }

    $query->execute([$id]);
    return $query->fetch(); // false if not found
}

function users_get_by_username($username) {
    static $query = null;

    if ($query === null) {
        $query = get_pdo()->prepare("SELECT * FROM Users WHERE username = ?");
    }

    $query->execute([$username]);
    return $query->fetch(); // false if not found
}

function users_update($data) {
    static $query = null;

    if ($query === null) {
        $query = get_pdo()->prepare("
            UPDATE Users SET
                username = ?,
                password_hash = ?,
                name = ?,
                surname = ?,
                birth_date = ?,
                description = ?,
                pfp_name = ?
            WHERE id = ?
        ");
    }

    $query->execute([
        $data['username'],
        $data['password_hash'],
        $data['name'],
        $data['surname'],
        $data['birth_date'],
        $data['description'],
        $data['pfp_name'],
        $data['id']
    ]);

    return $query->rowCount() > 0; // true if updated
}


function cart_insert_or_update($user_id, $product_id, $quantity) {
    static $query = null;

    if ($query === null) {
        $query = get_pdo()->prepare("
            INSERT INTO Cart (user_id, product_id, quantity) VALUES (?, ?, ?)
            ON CONFLICT(user_id, product_id)
            DO UPDATE SET
                quantity = quantity + excluded.quantity,
                added_at = CURRENT_TIMESTAMP
        ");
    }

    $query->execute([$user_id, $product_id, $quantity]);
    return $query->rowCount() > 0; // true if insert/update was successful
}

function cart_fetch($user_id) {
    static $query = null;

    if ($query === null) {
        $query = get_pdo()->prepare("
            SELECT p.id, p.name, p.price, p.image_name, c.quantity
            FROM Cart c
            JOIN Products p ON c.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.added_at DESC
        ");
    }

    $query->execute([$user_id]);
    return $query->fetchAll();
}

function cart_delete($user_id, $product_id) {
    static $query = null;

    if ($query === null) {
        $query = get_pdo()->prepare("DELETE FROM Cart WHERE user_id = ? AND product_id = ?");
    }

    $query->execute([$user_id, $product_id]);
    return $query->rowCount() > 0; // true if a row was deleted
}

function cart_delete_all($user_id) {
    static $query = null;

    if ($query === null) {
        $query = get_pdo()->prepare("DELETE FROM Cart WHERE user_id = ?");
    }

    $query->execute([$user_id]);
    return $query->rowCount(); // number of items removed
}


function init_db() {
    if (file_exists(DB_FILE)) {
        return;
    }
    error_log("Initializing DB at: " . DB_FILE);

    $pdo = get_pdo();

    $pdo->exec("CREATE TABLE Products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        price REAL NOT NULL,
        description TEXT,
        image_name TEXT
    )");
    $products_query = $pdo->prepare("INSERT INTO Products (name, price, description, image_name) VALUES (?, ?, ?, ?)");

    $pdo->exec("CREATE TABLE Users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        name TEXT,
        surname TEXT,
        birth_date DATE,
        description TEXT,
        pfp_name TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE Cart (
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL DEFAULT 1,
        added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES Products(id) ON DELETE CASCADE
    )");
    $cart_query = $pdo->prepare("INSERT INTO Cart (user_id, product_id, quantity) VALUES (?, 5, 1)");


    $products = [
        ['Кола', 45, 'Пляшка газованого солодкого напою', 'cola'],
        ['Вода', 20, 'Пляшка питної мінеральної води', 'water'],
        ['Хліб', 20.50, 'Свіжий хліб', 'bread'],
        ['Кавун', 50, 'Смачний кавун з теплиці', 'watermelon'],
        ['Слоненя', 500, 'М\'яке PHP слоненя', 'elephant'],
        ['Тенші', 2081.97, 'Тенші фумо', 'tenshi'],
        ['Windows', 7999, 'Ліцензія Windows 11 Pro', 'windows'],
        ['Toyota Corolla', 916500, 'Нова Toyota Corolla 2025', 'corolla'],
    ];
    foreach ($products as $product) {
        $products_query->execute($product);
    }

    $users = [
        ['test', 'test'],
        ['john', 'купуй слона'],
    ];
    foreach ($users as [$username, $password]) {
        users_insert($username, password_hash($password, PASSWORD_ARGON2ID));
        $cart_query->execute([$pdo->lastInsertId()]);
    }
}
