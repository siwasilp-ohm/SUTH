<?php
require __DIR__ . '/../../lib/auth.php';

require_login();
require_role('admin');

$pdo = require __DIR__ . '/../../lib/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'company';
    $companyId = $_POST['company_id'] ?: null;

    $stmt = $pdo->prepare('INSERT INTO users (company_id, name, email, password_hash, role) VALUES (:company_id, :name, :email, :password_hash, :role)');
    $stmt->execute([
        ':company_id' => $companyId ?: null,
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => $role,
    ]);

    $message = 'สร้างบัญชีผู้ใช้เรียบร้อยแล้ว';
}

$users = $pdo->query('SELECT users.*, companies.name AS company_name FROM users LEFT JOIN companies ON users.company_id = companies.id ORDER BY users.created_at DESC')->fetchAll();
$companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();

include __DIR__ . '/../partials/header.php';
?>
<section class="admin">
    <h2>จัดการบัญชีผู้ใช้</h2>
    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <div class="admin-grid">
        <div class="card">
            <h3>เพิ่มผู้ใช้</h3>
            <form method="post">
                <label>
                    ชื่อ
                    <input type="text" name="name" required>
                </label>
                <label>
                    อีเมล
                    <input type="email" name="email" required>
                </label>
                <label>
                    รหัสผ่าน
                    <input type="password" name="password" required>
                </label>
                <label>
                    บทบาท
                    <select name="role">
                        <option value="company">Company</option>
                        <option value="auditor">Auditor</option>
                        <option value="admin">Admin</option>
                    </select>
                </label>
                <label>
                    บริษัท (เฉพาะ Company)
                    <select name="company_id">
                        <option value="">ไม่ระบุ</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="btn primary" type="submit">สร้างบัญชี</button>
            </form>
        </div>
        <div class="card">
            <h3>ผู้ใช้ทั้งหมด</h3>
            <div class="table">
                <div class="table-row table-header">
                    <span>ชื่อ</span>
                    <span>อีเมล</span>
                    <span>บทบาท</span>
                    <span>บริษัท</span>
                </div>
                <?php foreach ($users as $user): ?>
                    <div class="table-row">
                        <span><?= htmlspecialchars($user['name']) ?></span>
                        <span><?= htmlspecialchars($user['email']) ?></span>
                        <span><?= htmlspecialchars($user['role']) ?></span>
                        <span><?= htmlspecialchars($user['company_name'] ?? '-') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
