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
        $levelValue = (float) ($_POST['self_score'][$indicatorId] ?? 0);
        $detailsValue = trim($_POST['details'][$indicatorId] ?? '');
        $pointsValue = calculate_points($levelValue, $indicator);

        $stmt = $pdo->prepare('INSERT INTO assessment_scores (assessment_id, indicator_id, self_level, self_score, details)
            VALUES (:assessment_id, :indicator_id, :self_level, :self_score, :details)
            ON DUPLICATE KEY UPDATE self_level = VALUES(self_level), self_score = VALUES(self_score), details = VALUES(details)');
        $stmt->execute([
            ':assessment_id' => $assessment['id'],
            ':indicator_id' => $indicatorId,
            ':self_level' => $levelValue,
            ':self_score' => $pointsValue ?: null,
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
            $storedName = time() . '-' . $filename;
            $targetPath = $uploadDir . $storedName;
            if (move_uploaded_file($tmpName, $targetPath)) {
                $stmt = $pdo->prepare('INSERT INTO attachments (assessment_score_id, file_path, original_name) VALUES (:assessment_score_id, :file_path, :original_name)');
                $stmt->execute([
                    ':assessment_score_id' => $scoreId,
                    ':file_path' => $storedName,
                    ':original_name' => $filename,
                ]);
            }
        }
    }

    if ($action === 'submit') {
        $stmt = $pdo->prepare('UPDATE assessments SET status = \"submitted\", submitted_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $assessment['id']]);
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
        <div class="status-chip">สถานะ: <?= htmlspecialchars($assessment['status']) ?></div>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert success">บันทึกข้อมูลเรียบร้อยแล้ว</div>
        <?php endif; ?>
    </div>

    <form method="post" enctype="multipart/form-data">
        <div class="card summary-card">
            <h3>คะแนนประเมินตนเองแบบ Real-time</h3>
            <div class="summary-grid">
                <div>
                    <span>รวมทั้งหมด</span>
                    <strong id="total-score">0</strong>
                </div>
                <?php foreach ($grouped as $pillar => $items): ?>
                    <div>
                        <span><?= htmlspecialchars($pillar) ?></span>
                        <strong class="pillar-total" data-pillar-total="<?= htmlspecialchars($pillar) ?>">0</strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
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
                                    <select name="self_score[<?= $indicator['id'] ?>]" data-pillar="<?= htmlspecialchars($indicator['pillar_code']) ?>" data-max-score="<?= get_indicator_max_score($indicator) ?>">
                                        <?php foreach ([0, 0.25, 0.5, 0.75, 1.0] as $option): ?>
                                            <option value="<?= $option ?>" <?= isset($scoreRow['self_level']) && (float)$scoreRow['self_level'] === (float)$option ? 'selected' : '' ?>><?= $option ?></option>
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
                            <div class="attachment-list">
                                <strong>ไฟล์แนบที่อัปโหลดแล้ว</strong>
                                <?php if (!empty($attachmentsByIndicator[$indicator['id']])): ?>
                                    <ul>
                                        <?php foreach ($attachmentsByIndicator[$indicator['id']] as $attachment): ?>
                                            <li>
                                                <a href="/download.php?id=<?= $attachment['id'] ?>"><?= htmlspecialchars($attachment['original_name']) ?></a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="muted">ยังไม่มีไฟล์แนบ</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="actions">
            <button class="btn ghost" type="submit" name="action" value="save">บันทึกแบบประเมิน</button>
            <button class="btn primary" type="submit" name="action" value="submit">ส่งแบบประเมิน</button>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
