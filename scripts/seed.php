<?php
$pdo = require __DIR__ . '/../lib/db.php';

$pdo->exec("INSERT INTO companies (name) VALUES ('Sample Industrial Co.')");
$companyId = $pdo->lastInsertId();

$users = [
    [
        'name' => 'Admin User',
        'email' => 'admin@hicm.local',
        'password' => 'admin1234',
        'role' => 'admin',
        'company_id' => null,
    ],
    [
        'name' => 'Auditor User',
        'email' => 'auditor@hicm.local',
        'password' => 'auditor1234',
        'role' => 'auditor',
        'company_id' => null,
    ],
    [
        'name' => 'Company User',
        'email' => 'company@hicm.local',
        'password' => 'company1234',
        'role' => 'company',
        'company_id' => $companyId,
    ],
];

$stmt = $pdo->prepare('INSERT INTO users (company_id, name, email, password_hash, role) VALUES (:company_id, :name, :email, :password_hash, :role)');

foreach ($users as $user) {
    $stmt->execute([
        ':company_id' => $user['company_id'],
        ':name' => $user['name'],
        ':email' => $user['email'],
        ':password_hash' => password_hash($user['password'], PASSWORD_DEFAULT),
        ':role' => $user['role'],
    ]);
}

echo "Seeded sample users.\n";
