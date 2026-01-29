<?php
require __DIR__ . '/../lib/auth.php';

require_login();
$pdo = require __DIR__ . '/../lib/db.php';

$attachmentId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT attachments.*, assessments.company_id FROM attachments
    JOIN assessment_scores ON attachments.assessment_score_id = assessment_scores.id
    JOIN assessments ON assessment_scores.assessment_id = assessments.id
    WHERE attachments.id = :id');
$stmt->execute([':id' => $attachmentId]);
$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$user = current_user();
if ($user['role'] === 'company' && (int) $attachment['company_id'] !== (int) $user['company_id']) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$storagePath = __DIR__ . '/../storage/attachments/' . $attachment['file_path'];
if (!file_exists($storagePath)) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($attachment['original_name']) . '"');
header('Content-Length: ' . filesize($storagePath));
readfile($storagePath);
exit;
