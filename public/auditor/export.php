<?php
require __DIR__ . '/../../lib/auth.php';
require __DIR__ . '/../../lib/assessment.php';

require_login();
require_role('auditor');

$pdo = require __DIR__ . '/../../lib/db.php';
$indicators = get_indicators($pdo);

$companyStmt = $pdo->query('SELECT * FROM companies ORDER BY name');
$companies = $companyStmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="hicm-summary.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Company', 'Total Score', 'H1', 'I2', 'C3', 'M4']);

foreach ($companies as $company) {
    $assessment = get_or_create_assessment($pdo, (int) $company['id']);
    $scores = get_scores($pdo, (int) $assessment['id']);
    $totals = calculate_totals($indicators, $scores, 'auditor');
    fputcsv($output, [
        $company['name'],
        number_format($totals['overall'], 2, '.', ''),
        number_format($totals['pillars']['H1'] ?? 0, 2, '.', ''),
        number_format($totals['pillars']['I2'] ?? 0, 2, '.', ''),
        number_format($totals['pillars']['C3'] ?? 0, 2, '.', ''),
        number_format($totals['pillars']['M4'] ?? 0, 2, '.', ''),
    ]);
}

fclose($output);
exit;
