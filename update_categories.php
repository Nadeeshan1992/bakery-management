<?php
$pdo = new PDO('mysql:host=localhost;dbname=bakery_db;charset=utf8mb4', 'root', '');

// Disable foreign key checks to update categories cleanly
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
$pdo->exec("TRUNCATE TABLE categories;");

$newCategories = [
    'Raw Material',
    'Catering Range',
    'Biscuits/Cookies',
    'Rusks',
    'Cakes',
    'Breads',
    'Buns',
    'None'
];

$stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");

foreach ($newCategories as $catName) {
    $stmt->execute([$catName, $catName . ' category']);
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

// Update existing products category_id to 1 (or default)
$pdo->exec("UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'Cakes' LIMIT 1) WHERE category_id NOT IN (SELECT id FROM categories)");
$pdo->exec("UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'Biscuits/Cookies' LIMIT 1) WHERE name LIKE '%Cookie%' OR name LIKE '%Biscuit%'");
$pdo->exec("UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'Breads' LIMIT 1) WHERE name LIKE '%Bread%' OR name LIKE '%Baguette%' OR name LIKE '%Loaf%' OR name LIKE '%Croissant%'");
$pdo->exec("UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'Buns' LIMIT 1) WHERE name LIKE '%Bun%'");

echo "Categories table successfully updated with attached list.\n";
