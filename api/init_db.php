<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = "localhost";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->exec("CREATE DATABASE IF NOT EXISTS ambr DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    
    $conn->exec("USE ambr");


    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $conn->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255),
        category VARCHAR(100) NOT NULL,
        stock INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

$colCheck = $conn->query("\n    SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS \n    WHERE TABLE_SCHEMA = 'ambr' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'image'\n")->fetch(PDO::FETCH_ASSOC);
if ($colCheck && $colCheck['DATA_TYPE'] !== 'longtext') {
    $conn->exec("ALTER TABLE products MODIFY image LONGTEXT");
}
$colCheckVar = $conn->query("\n    SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS \n    WHERE TABLE_SCHEMA = 'ambr' AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'image'\n")->fetch(PDO::FETCH_ASSOC);
if ($colCheckVar && $colCheckVar['DATA_TYPE'] !== 'longtext') {
    $conn->exec("ALTER TABLE product_variants MODIFY image LONGTEXT");
}
// Product Variants
    $conn->exec("CREATE TABLE IF NOT EXISTS product_variants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        color_name VARCHAR(100) NOT NULL,
        color_code VARCHAR(50) NOT NULL,
        image VARCHAR(255),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Cart
    $conn->exec("CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        variant_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Orders
    $conn->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        shipping_address TEXT NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'cash',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Order Items
    $conn->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        variant_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Admin Adjustments
    $conn->exec("CREATE TABLE IF NOT EXISTS admin_adjustments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Coupons
    $conn->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        type VARCHAR(50) NOT NULL,
        value DECIMAL(10,2) NOT NULL,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // 5. Seed default admin and user if not exists
    $adminCheck = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $adminCheck->execute(['admin@ambr.com']);
    if (!$adminCheck->fetch()) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Admin System', 'admin@ambr.com', $adminPassword, 'admin']);
    }

    $userCheck = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $userCheck->execute(['john@example.com']);
    if (!$userCheck->fetch()) {
        $userPassword = password_hash('user123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['John Doe', 'john@example.com', $userPassword, 'user']);
    }

    // 6. Seed dummy products if table is empty
    $prodCheck = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($prodCheck == 0) {
        $productsSeed = [
            [
                'name' => 'Classic Crewneck Sweatshirt',
                'description' => 'A super comfortable, soft cotton blend crewneck sweatshirt designed for everyday premium comfort.',
                'price' => 49.99,
                'category' => 'Hoodies',
                'stock' => 50,
                'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=500&auto=format&fit=crop&q=60',
                'colors' => [
                    ['name' => 'Sunset Red', 'code' => '#ff3b30', 'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=500&auto=format&fit=crop&q=60'],
                    ['name' => 'Royal Blue', 'code' => '#007aff', 'image' => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=500&auto=format&fit=crop&q=60'],
                    ['name' => 'Carbon Black', 'code' => '#1c1c1e', 'image' => 'https://images.unsplash.com/photo-1578932750294-f5075e85f44a?w=500&auto=format&fit=crop&q=60']
                ]
            ],
            [
                'name' => 'Essential Signature Tee',
                'description' => '100% organic cotton luxury tee. Minimalist design with a heavy-weight fabric feel.',
                'price' => 24.99,
                'category' => 'Shirts',
                'stock' => 100,
                'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=60',
                'colors' => [
                    ['name' => 'Pure White', 'code' => '#ffffff', 'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=60'],
                    ['name' => 'Heather Gray', 'code' => '#8e8e93', 'image' => 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=500&auto=format&fit=crop&q=60']
                ]
            ],
            [
                'name' => 'Premium Knitted Beanie',
                'description' => 'A warm, ribbed wool knit beanie that keeps you stylish and protected in colder weather.',
                'price' => 19.99,
                'category' => 'Accessories',
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?w=500&auto=format&fit=crop&q=60',
                'colors' => [
                    ['name' => 'Matte Black', 'code' => '#1c1c1e', 'image' => 'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?w=500&auto=format&fit=crop&q=60'],
                    ['name' => 'Mustard Gold', 'code' => '#ffcc00', 'image' => 'https://images.unsplash.com/photo-1608228088998-57828365d486?w=500&auto=format&fit=crop&q=60']
                ]
            ],
            [
                'name' => 'Vintage Denim Trucker Jacket',
                'description' => 'Classic denim jacket with distressed details, metal buttons, and a perfect relaxed fit.',
                'price' => 79.99,
                'category' => 'Outerwear',
                'stock' => 25,
                'image' => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=500&auto=format&fit=crop&q=60',
                'colors' => [
                    ['name' => 'Classic Blue', 'code' => '#5ac8fa', 'image' => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=500&auto=format&fit=crop&q=60']
                ]
            ],
            [
                'name' => 'Urban Athletic Joggers',
                'description' => 'Tailored slim-fit sweatpants with zipper pockets and drawstring waist. Ideal for workouts or lounging.',
                'price' => 39.99,
                'category' => 'Pants',
                'stock' => 40,
                'image' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?w=500&auto=format&fit=crop&q=60',
                'colors' => [
                    ['name' => 'Obsidian Black', 'code' => '#1c1c1e', 'image' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?w=500&auto=format&fit=crop&q=60'],
                    ['name' => 'Forest Green', 'code' => '#2e7d32', 'image' => 'https://images.unsplash.com/photo-1517462964-21fdcec3f25b?w=500&auto=format&fit=crop&q=60']
                ]
            ]
        ];

        foreach ($productsSeed as $p) {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$p['name'], $p['description'], $p['price'], $p['category'], $p['stock'], $p['image']]);
            $productId = $conn->lastInsertId();

            foreach ($p['colors'] as $c) {
                $vStmt = $conn->prepare("INSERT INTO product_variants (product_id, color_name, color_code, image) VALUES (?, ?, ?, ?)");
                $vStmt->execute([$productId, $c['name'], $c['code'], $c['image']]);
            }
        }
    }

    // 7. Seed dummy coupons if table is empty
    $couponCheck = $conn->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
    if ($couponCheck == 0) {
        $couponsSeed = [
            ['code' => 'WELCOME10', 'type' => 'percentage', 'value' => 10],
            ['code' => 'SUPERFIXED', 'type' => 'fixed', 'value' => 15],
            ['code' => 'FREESHIP', 'type' => 'free_shipping', 'value' => 0]
        ];
        foreach ($couponsSeed as $c) {
            $stmt = $conn->prepare("INSERT INTO coupons (code, type, value, active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$c['code'], $c['type'], $c['value']]);
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Database initialized, tables created, and seeded successfully.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database setup failed: ' . $e->getMessage()
    ]);
}
?>
