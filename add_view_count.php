<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN view_count INT NOT NULL DEFAULT 0");
    echo "Successfully added view_count column to posts table.\n";
} catch (PDOException $e) {
    echo "Error adding view_count column: " . $e->getMessage() . "\n";
}
?>
