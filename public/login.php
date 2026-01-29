<?php
require __DIR__ . '/../lib/auth.php';
$pdo = require __DIR__ . '/../lib/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT users.*, companies.name AS company_name FROM users LEFT JOIN companies ON users.company_id = companies.id WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        login_user([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'company_id' => $user['company_id'],
            'company_name' => $user['company_name'],
        ]);
        header('Location: /dashboard.php');
        exit;
    }

    $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
}

include __DIR__ . '/partials/header.php';
?>
<section class="auth">
    <div class="auth-card">
        <h2>เข้าสู่ระบบ</h2>
        <p>เข้าสู่ระบบเพื่อเริ่มต้นการประเมิน HICM V2025</p>
        <?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <label>
                อีเมล
                <input type="email" name="email" required>
            </label>
            <label>
                รหัสผ่าน
                <input type="password" name="password" required>
            </label>
            <button class="btn primary" type="submit">เข้าสู่ระบบ</button>
        </form>
        <div class="auth-hint">
            ตัวอย่างบัญชี: company@hicm.local / company1234
        </div>
        <div class="auth-hint">
            ยังไม่มีบัญชี? <a href="/register.php">ลงทะเบียน</a>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
