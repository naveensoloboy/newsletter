<?php
// ─── seed.php — Run once to create default admin ─────────────
// Usage: php seed.php
require_once __DIR__ . '/bootstrap.php';

$users = new UserModel();

// Create default admin
$existing = $users->findByEmail('admin@college.edu');
if ($existing) {
    echo "✅ Admin already exists: admin@college.edu\n";
} else {
    $id = $users->create('System Administrator', 'admin@college.edu', 'Admin@123', 'admin', 'Management');
    echo "✅ Admin created!\n";
    echo "   Email   : admin@college.edu\n";
    echo "   Password: Admin@123\n";
    echo "   ID      : $id\n";
}

echo "\n✅ Seed complete!\n";
