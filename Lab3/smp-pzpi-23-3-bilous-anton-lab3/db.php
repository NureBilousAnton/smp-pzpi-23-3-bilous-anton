<?php
define('DB_FILE', __DIR__ . '/store.db');

function get_pdo() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // throw exeptions
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // parse into dicts
    } catch (PDOException $e) {
        exit("DB connection failed: " . $e->getMessage());
    }

    return $pdo;
}

function get_all_products() {
    return get_pdo()->query("SELECT * FROM products")->fetchAll();
}

function get_product_by_id($id) {
    $stmt = get_pdo()->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}


function init_db() {
    $pdo = get_pdo();

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL,
            image_name TEXT
        )");

        $query = $pdo->query("SELECT COUNT(*) as count FROM products");
        if ($query->fetchColumn() != 0) {
            return;
        }

        $products_to_seed = [
            ['Кола', 'Пляшка газованого солодкого напою', 45, 'cola'],
            ['Вода', 'Пляшка питної мінеральної води', 20, 'water'],
            ['Хліб', 'Свіжий хліб', 20.50, 'bread'],
            ['Кавун', 'Смачний кавун з теплиці', 50, 'watermelon'],
            ['Слоненя', 'М\'яке PHP слоненя', 500, 'elephant'],
            ['Тенші', 'Тенші фумо', 2081.97, 'tenshi'],
            ['Windows', 'Ліцензія Windows 11 Pro', 7999, 'windows'],
            ['Toyota Corolla', 'Нова Toyota Corolla 2025', 916500, 'corolla'],
        ];

        $query = $pdo->prepare("INSERT INTO products (name, description, price, image_name) VALUES (?, ?, ?, ?)");
        foreach ($products_to_seed as $product) {
            $query->execute($product);
        }
    } catch (PDOException $e) {
        exit("DB initialization failed: " . $e->getMessage());
    }
}
?>
