<?php
session_start();

require_once 'data.php';
try {
    init_db();
} catch (PDOException $e) {
    exit("DB initialization failed: " . $e->getMessage());
}
require_once 'functions.php';

if (isset($_SESSION['user'])) {
    require_once 'private.php';
} else {
    require_once 'public.php';
}

include 'tpl/footer.phtml';
