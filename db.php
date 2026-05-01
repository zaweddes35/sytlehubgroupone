
<?php
$host = "localhost";
$dbname = "stylehub";
$username = "root";
$password = "";
$databaseCreatedNow = false;
$databaseRepairedNow = false;

function createCoreSchema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            category_id INT NULL,
            stock INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_products_category
                FOREIGN KEY (category_id) REFERENCES categories(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(60) NOT NULL,
            address TEXT NOT NULL,
            total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('pending','processing','shipped','delivered') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_orders_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_order_items_order
                FOREIGN KEY (order_id) REFERENCES orders(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_order_items_product
                FOREIGN KEY (product_id) REFERENCES products(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function seedDefaultData(PDO $pdo): void
{
    $hasCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($hasCategories === 0) {
        $pdo->exec("INSERT INTO categories (name) VALUES ('Men'), ('Women'), ('Kids'), ('Accessories')");
    }

    $hasAdmin = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($hasAdmin === 0) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([
            'Administrator',
            'admin@stylehub.local',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
    }
}

try {
    $bootstrap = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $bootstrap->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbExistsStmt = $bootstrap->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $dbExistsStmt->execute([$dbname]);
    $databaseAlreadyExists = (bool)$dbExistsStmt->fetchColumn();

    $bootstrap->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $databaseCreatedNow = !$databaseAlreadyExists;
    $bootstrap = null;

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    createCoreSchema($pdo);
    seedDefaultData($pdo);
} catch (PDOException $e) {
    $isTablespaceConflict = strpos($e->getMessage(), '1813') !== false
        || stripos($e->getMessage(), 'tablespace') !== false;

    if (!$isTablespaceConflict) {
        die("<h2 style='color:red;font-family:sans-serif;padding:2rem'>Database Error: " . $e->getMessage() . "</h2>");
    }

    try {
        // Repair orphaned tablespace state by rebuilding the schema database.
        $repair = new PDO("mysql:host=$host;charset=utf8", $username, $password);
        $repair->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $repair->exec("DROP DATABASE IF EXISTS `$dbname`");
        $repair->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $repair = null;

        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        createCoreSchema($pdo);
        seedDefaultData($pdo);
        $databaseRepairedNow = true;
    } catch (PDOException $repairError) {
        die("<h2 style='color:red;font-family:sans-serif;padding:2rem'>Database Error: " . $repairError->getMessage() . "</h2>");
    }
}

if (
    isset($_SERVER['SCRIPT_FILENAME']) &&
    realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__
) {
    $isSuccess = true;
    $title = $databaseRepairedNow
        ? "Database repaired successfully"
        : ($databaseCreatedNow ? "Database created successfully" : "Database already exists");
    $message = $databaseRepairedNow
        ? "A tablespace conflict was fixed by recreating stylehub and rebuilding all required tables."
        : ($databaseCreatedNow
        ? "The stylehub database has been created and initialized."
        : "The stylehub database is already available and ready to use.");

    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>StyleHub Database Setup</title>
    <style>
        body{margin:0;font-family:Arial,sans-serif;background:#f4f6fb;color:#222;display:flex;align-items:center;justify-content:center;min-height:100vh}
        .card{background:#fff;padding:2rem 2.2rem;border-radius:14px;box-shadow:0 8px 28px rgba(0,0,0,.1);max-width:620px;width:92%}
        .badge{display:inline-block;padding:.35rem .7rem;border-radius:999px;font-size:.78rem;font-weight:700;background:#d4edda;color:#155724;margin-bottom:1rem}
        h1{margin:0 0 .7rem;font-size:1.45rem}
        p{margin:0 0 1.2rem;color:#555;line-height:1.55}
        a{display:inline-block;text-decoration:none;background:#e94560;color:#fff;padding:.65rem 1rem;border-radius:8px;font-size:.92rem}
    </style>
</head>
<body>
    <div class='card'>
        <span class='badge'>" . ($isSuccess ? "SUCCESS" : "INFO") . "</span>
        <h1>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h1>
        <p>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>
        <a href='setup.php'>Run Full Setup Check</a>
    </div>
</body>
</html>";
}
?>

