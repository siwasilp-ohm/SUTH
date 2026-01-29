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
$attachmentStmt = $pdo->prepare('SELECT attachments.*, assessment_scores.indicator_id FROM attachments JOIN assessment_scores ON attachments.assessment_score_id = assessment_scores.id WHERE assessment_scores.assessment_id = :assessment_id');
$attachmentStmt->execute([':assessment_id' => $assessment['id']]);
$attachmentsByIndicator = [];
foreach ($attachmentStmt->fetchAll() as $row) {
    $attachmentsByIndicator[$row['indicator_id']][] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    foreach ($indicators as $indicator) {
        $indicatorId = $indicator['id'];
        $levelValue = (float) ($_POST['auditor_score'][$indicatorId] ?? 0);
        $pointsValue = calculate_points($levelValue, $indicator);

        $stmt = $pdo->prepare('INSERT INTO assessment_scores (assessment_id, indicator_id, auditor_level, auditor_score)
            VALUES (:assessment_id, :indicator_id, :auditor_level, :auditor_score)
            ON DUPLICATE KEY UPDATE auditor_level = VALUES(auditor_level), auditor_score = VALUES(auditor_score)');
        $stmt->execute([
            ':assessment_id' => $assessment['id'],
            ':indicator_id' => $indicatorId,
            ':auditor_level' => $levelValue,
            ':auditor_score' => $pointsValue ?: null,
        ]);
    }

    if ($action === 'finalize') {
        $stmt = $pdo->prepare('UPDATE assessments SET status = \"reviewed\" WHERE id = :id');
        $stmt->execute([':id' => $assessment['id']]);
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
        <div class="status-chip">สถานะ: <?= htmlspecialchars($assessment['status']) ?></div>
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
                                            <option value="<?= $option ?>" <?= isset($scoreRow['auditor_level']) && (float)$scoreRow['auditor_level'] === (float)$option ? 'selected' : '' ?>><?= $option ?></option>
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
                            <div class="attachment-list">
                                <strong>ไฟล์แนบ</strong>
                                <?php if (!empty($attachmentsByIndicator[$indicator['id']])): ?>
                                    <ul>
                                        <?php foreach ($attachmentsByIndicator[$indicator['id']] as $attachment): ?>
                                            <li>
                                                <a href="/download.php?id=<?= $attachment['id'] ?>"><?= htmlspecialchars($attachment['original_name']) ?></a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="muted">ไม่มีไฟล์แนบ</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="actions">
            <button class="btn ghost" type="submit" name="action" value="save">บันทึกคะแนนกรรมการ</button>
            <button class="btn primary" type="submit" name="action" value="finalize">สรุปผลและปิดการประเมิน</button>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
