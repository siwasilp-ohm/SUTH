<?php
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/assessment.php';
require __DIR__ . '/../lib/data.php';

require_login();
$user = current_user();
$pdo = require __DIR__ . '/../lib/db.php';

ensure_indicators_seeded($pdo);
$indicators = get_indicators($pdo);

$companyAssessment = null;
$totals = null;
$stats = [];
if ($user['role'] === 'company' && $user['company_id']) {
    $companyAssessment = get_or_create_assessment($pdo, (int) $user['company_id']);
    $scores = get_scores($pdo, (int) $companyAssessment['id']);
    $totals = calculate_totals($indicators, $scores, 'company');
}

if ($user['role'] === 'auditor') {
    $totals = ['overall' => 0, 'pillars' => []];
    $stats['companies'] = (int) $pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn();
    $stats['submitted'] = (int) $pdo->query('SELECT COUNT(*) FROM assessments WHERE status = \"submitted\"')->fetchColumn();
    $stats['reviewed'] = (int) $pdo->query('SELECT COUNT(*) FROM assessments WHERE status = \"reviewed\"')->fetchColumn();
}

if ($user['role'] === 'admin') {
    $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['companies'] = (int) $pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn();
}

$data = load_hicm_data();
$pillarMeta = [];
foreach ($data['pillars'] as $pillar) {
    if (preg_match('/\(([^)]+)\)/', $pillar['code'], $matches)) {
        $pillarMeta[$matches[1]] = $pillar;
    }
}

include __DIR__ . '/partials/header.php';
?>
<section class="dashboard">
    <h2>Dashboard</h2>
    <p class="subtitle">สรุปผลการประเมินและภาพรวมคะแนนแบบเรียลไทม์</p>

    <div class="dashboard-grid">
        <div class="card">
            <h3>สถานะการประเมิน</h3>
            <?php if ($user['role'] === 'company' && $companyAssessment): ?>
                <p><strong>บริษัท:</strong> <?= htmlspecialchars($user['company_name'] ?? '-') ?></p>
                <p><strong>สถานะ:</strong> <?= htmlspecialchars($companyAssessment['status']) ?></p>
                <p><strong>คะแนนรวม (Self):</strong> <?= number_format($totals['overall'], 2) ?> / 1000</p>
                <a class="btn primary" href="/company/assessment.php">ไปยังแบบประเมิน</a>
            <?php elseif ($user['role'] === 'auditor'): ?>
                <p>สำหรับกรรมการ ตรวจสอบข้อมูลทุกบริษัทและให้คะแนนได้ที่หน้ารายบริษัท</p>
                <div class="summary-grid">
                    <div>
                        <span>บริษัททั้งหมด</span>
                        <strong><?= $stats['companies'] ?? 0 ?></strong>
                    </div>
                    <div>
                        <span>ส่งประเมินแล้ว</span>
                        <strong><?= $stats['submitted'] ?? 0 ?></strong>
                    </div>
                    <div>
                        <span>ตรวจสอบแล้ว</span>
                        <strong><?= $stats['reviewed'] ?? 0 ?></strong>
                    </div>
                </div>
                <a class="btn primary" href="/auditor/companies.php">ดูรายการบริษัท</a>
            <?php else: ?>
                <p>ผู้ดูแลระบบสามารถจัดการบัญชีผู้ใช้และตั้งค่าตัวชี้วัดได้</p>
                <div class="summary-grid">
                    <div>
                        <span>ผู้ใช้งาน</span>
                        <strong><?= $stats['users'] ?? 0 ?></strong>
                    </div>
                    <div>
                        <span>บริษัท</span>
                        <strong><?= $stats['companies'] ?? 0 ?></strong>
                    </div>
                </div>
                <a class="btn primary" href="/admin/users.php">จัดการผู้ใช้</a>
            <?php endif; ?>
        </div>
        <div class="card chart-card">
            <h3>Radar Overview</h3>
            <canvas id="radarChart" width="400" height="240"></canvas>
        </div>
    </div>

    <div class="card">
        <h3>คะแนนราย Pillar</h3>
        <div class="pillar-summary">
            <?php foreach ($pillarMeta as $code => $pillar): ?>
                <div>
                    <span><?= htmlspecialchars($code) ?></span>
                    <strong><?= number_format($totals['pillars'][$code] ?? 0, 2) ?></strong>
                    <small>น้ำหนัก <?= htmlspecialchars($pillar['weight']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const radarData = {
        labels: <?= json_encode(array_keys($pillarMeta)) ?>,
        datasets: [{
            label: 'Score',
            data: <?= json_encode(array_values(array_map(fn($code) => $totals['pillars'][$code] ?? 0, array_keys($pillarMeta)))) ?>,
            fill: true,
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            borderColor: 'rgba(59, 130, 246, 1)',
            pointBackgroundColor: 'rgba(59, 130, 246, 1)'
        }]
    };

    const radarConfig = {
        type: 'radar',
        data: radarData,
        options: {
            scales: {
                r: {
                    beginAtZero: true,
                    max: 300
                }
            }
        }
    };

    const radarChart = document.getElementById('radarChart');
    if (radarChart) {
        new Chart(radarChart, radarConfig);
    }
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
