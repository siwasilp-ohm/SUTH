<?php
require __DIR__ . '/../../lib/auth.php';
require __DIR__ . '/../../lib/assessment.php';

require_login();
require_role('auditor');

$pdo = require __DIR__ . '/../../lib/db.php';
ensure_indicators_seeded($pdo);

$companyId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM companies WHERE id = :id');
$stmt->execute([':id' => $companyId]);
$company = $stmt->fetch();
if (!$company) {
    http_response_code(404);
    echo 'Company not found.';
    exit;
}

$assessment = get_or_create_assessment($pdo, $companyId);
$indicators = get_indicators($pdo);
$scores = get_scores($pdo, (int) $assessment['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($indicators as $indicator) {
        $indicatorId = $indicator['id'];
        $scoreValue = $_POST['auditor_score'][$indicatorId] ?? null;

        $stmt = $pdo->prepare('INSERT INTO assessment_scores (assessment_id, indicator_id, auditor_score)
            VALUES (:assessment_id, :indicator_id, :auditor_score)
            ON DUPLICATE KEY UPDATE auditor_score = VALUES(auditor_score)');
        $stmt->execute([
            ':assessment_id' => $assessment['id'],
            ':indicator_id' => $indicatorId,
            ':auditor_score' => $scoreValue !== '' ? $scoreValue : null,
        ]);
    }

    header('Location: /auditor/company.php?id=' . $companyId . '&success=1');
    exit;
}

$grouped = [];
foreach ($indicators as $indicator) {
    $grouped[$indicator['pillar_code']][] = $indicator;
}

include __DIR__ . '/../partials/header.php';
?>
<section class="assessment">
    <div class="section-header">
        <h2>ตรวจสอบบริษัท: <?= htmlspecialchars($company['name']) ?></h2>
        <p>กรอกคะแนนกรรมการ (Auditor Score) แยกจากคะแนนบริษัท</p>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert success">บันทึกคะแนนเรียบร้อยแล้ว</div>
        <?php endif; ?>
    </div>

    <form method="post">
        <?php foreach ($grouped as $pillar => $items): ?>
            <div class="pillar-section">
                <h3><?= htmlspecialchars($pillar) ?></h3>
                <?php foreach ($items as $indicator): ?>
                    <?php $scoreRow = $scores[$indicator['id']] ?? []; ?>
                    <div class="indicator-card">
                        <div class="indicator-header">
                            <div>
                                <strong><?= htmlspecialchars($indicator['code']) ?></strong>
                                <span><?= htmlspecialchars($indicator['title']) ?></span>
                            </div>
                            <div class="score-select">
                                <label>คะแนนกรรมการ (0-1.0)
                                    <select name="auditor_score[<?= $indicator['id'] ?>]">
                                        <?php foreach ([0, 0.25, 0.5, 0.75, 1.0] as $option): ?>
                                            <option value="<?= $option ?>" <?= isset($scoreRow['auditor_score']) && (float)$scoreRow['auditor_score'] === (float)$option ? 'selected' : '' ?>><?= $option ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                        </div>
                        <p class="indicator-description"><?= nl2br(htmlspecialchars($indicator['description'])) ?></p>
                        <details>
                            <summary>ดูเกณฑ์การประเมิน</summary>
                            <div class="criteria"><?= nl2br(htmlspecialchars($indicator['criteria'])) ?></div>
                        </details>
                        <div class="indicator-inputs">
                            <label>
                                รายละเอียดที่บริษัทกรอก
                                <textarea readonly rows="3"><?= htmlspecialchars($scoreRow['details'] ?? '') ?></textarea>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="actions">
            <button class="btn primary" type="submit">บันทึกคะแนนกรรมการ</button>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
