<?php
require __DIR__ . '/../../lib/auth.php';
require __DIR__ . '/../../lib/assessment.php';

require_login();
require_role('company');

$user = current_user();
$pdo = require __DIR__ . '/../../lib/db.php';

ensure_indicators_seeded($pdo);
$assessment = get_or_create_assessment($pdo, (int) $user['company_id']);
$indicators = get_indicators($pdo);
$scores = get_scores($pdo, (int) $assessment['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($indicators as $indicator) {
        $indicatorId = $indicator['id'];
        $scoreValue = $_POST['self_score'][$indicatorId] ?? null;
        $detailsValue = trim($_POST['details'][$indicatorId] ?? '');

        $stmt = $pdo->prepare('INSERT INTO assessment_scores (assessment_id, indicator_id, self_score, details)
            VALUES (:assessment_id, :indicator_id, :self_score, :details)
            ON DUPLICATE KEY UPDATE self_score = VALUES(self_score), details = VALUES(details)');
        $stmt->execute([
            ':assessment_id' => $assessment['id'],
            ':indicator_id' => $indicatorId,
            ':self_score' => $scoreValue !== '' ? $scoreValue : null,
            ':details' => $detailsValue ?: null,
        ]);

        $scoreId = $pdo->lastInsertId();
        if (!$scoreId) {
            $stmt = $pdo->prepare('SELECT id FROM assessment_scores WHERE assessment_id = :assessment_id AND indicator_id = :indicator_id');
            $stmt->execute([
                ':assessment_id' => $assessment['id'],
                ':indicator_id' => $indicatorId,
            ]);
            $scoreId = $stmt->fetchColumn();
        }

        if (!empty($_FILES['evidence']['name'][$indicatorId])) {
            $uploadDir = __DIR__ . '/../../storage/attachments/';
            $filename = basename($_FILES['evidence']['name'][$indicatorId]);
            $tmpName = $_FILES['evidence']['tmp_name'][$indicatorId];
            $targetPath = $uploadDir . time() . '-' . $filename;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $stmt = $pdo->prepare('INSERT INTO attachments (assessment_score_id, file_path, original_name) VALUES (:assessment_score_id, :file_path, :original_name)');
                $stmt->execute([
                    ':assessment_score_id' => $scoreId,
                    ':file_path' => $targetPath,
                    ':original_name' => $filename,
                ]);
            }
        }
    }

    header('Location: /company/assessment.php?success=1');
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
        <h2>แบบประเมินตนเอง</h2>
        <p>กรอกคะแนนและแนบหลักฐานตามตัวชี้วัด HICM V2025</p>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert success">บันทึกข้อมูลเรียบร้อยแล้ว</div>
        <?php endif; ?>
    </div>

    <form method="post" enctype="multipart/form-data">
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
                                <label>คะแนน (0-1.0)
                                    <select name="self_score[<?= $indicator['id'] ?>]">
                                        <?php foreach ([0, 0.25, 0.5, 0.75, 1.0] as $option): ?>
                                            <option value="<?= $option ?>" <?= isset($scoreRow['self_score']) && (float)$scoreRow['self_score'] === (float)$option ? 'selected' : '' ?>><?= $option ?></option>
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
                                รายละเอียด/หลักฐานเชิงประจักษ์
                                <textarea name="details[<?= $indicator['id'] ?>]" rows="3"><?= htmlspecialchars($scoreRow['details'] ?? '') ?></textarea>
                            </label>
                            <label>
                                แนบไฟล์หลักฐาน
                                <input type="file" name="evidence[<?= $indicator['id'] ?>]">
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="actions">
            <button class="btn primary" type="submit">บันทึกแบบประเมิน</button>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
