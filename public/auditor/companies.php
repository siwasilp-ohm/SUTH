<?php
require __DIR__ . '/../../lib/auth.php';
require __DIR__ . '/../../lib/assessment.php';

require_login();
require_role('auditor');

$pdo = require __DIR__ . '/../../lib/db.php';
$companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();

include __DIR__ . '/../partials/header.php';
?>
<section class="list-page">
    <div class="section-header">
        <h2>รายการบริษัทที่ส่งประเมิน</h2>
        <a class="btn ghost" href="/auditor/export.php">Export คะแนนรวม (CSV)</a>
    </div>
    <div class="list-grid">
        <?php foreach ($companies as $company): ?>
            <div class="card">
                <h3><?= htmlspecialchars($company['name']) ?></h3>
                <a class="btn primary" href="/auditor/company.php?id=<?= $company['id'] ?>">ตรวจสอบและให้คะแนน</a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
