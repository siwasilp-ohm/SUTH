<?php
require __DIR__ . '/../lib/auth.php';
$pdo = require __DIR__ . '/../lib/db.php';

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = trim($_POST['company_name'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$companyName || !$name || !$email || !$password) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'อีเมลนี้ถูกใช้งานแล้ว';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO companies (name) VALUES (:name)');
                $stmt->execute([':name' => $companyName]);
                $companyId = $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO users (company_id, name, email, password_hash, role) VALUES (:company_id, :name, :email, :password_hash, :role)');
                $stmt->execute([
                    ':company_id' => $companyId,
                    ':name' => $name,
                    ':email' => $email,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':role' => 'company',
                ]);

                $pdo->commit();
                $success = 'ลงทะเบียนสำเร็จ กรุณาเข้าสู่ระบบ';
            } catch (Exception $exception) {
                $pdo->rollBack();
                $error = 'ไม่สามารถลงทะเบียนได้ กรุณาลองใหม่อีกครั้ง';
            }
        }
    }
}

include __DIR__ . '/partials/header.php';
?>
<section class="auth">
    <div class="auth-card">
        <h2>ลงทะเบียนบริษัท</h2>
        <p>สร้างบัญชีเพื่อเริ่มทำแบบประเมิน HICM V2025</p>
        <?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post">
            <label>
                ชื่อบริษัท
                <input type="text" name="company_name" required>
            </label>
            <label>
                ชื่อผู้ติดต่อ
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
            <button class="btn primary" type="submit">สร้างบัญชี</button>
        </form>
        <div class="auth-hint">
            มีบัญชีอยู่แล้ว? <a href="/login.php">เข้าสู่ระบบ</a>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
