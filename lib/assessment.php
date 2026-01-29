<?php
require_once __DIR__ . '/data.php';

function ensure_indicators_seeded(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM indicators')->fetchColumn();
    if ($count > 0) {
        return;
    }
    $data = load_hicm_data();
    $stmt = $pdo->prepare('INSERT INTO indicators (pillar_code, code, title, description, criteria, default_self_level, default_self_score, default_auditor_level, default_auditor_score)
        VALUES (:pillar_code, :code, :title, :description, :criteria, :default_self_level, :default_self_score, :default_auditor_level, :default_auditor_score)');
    foreach ($data['indicators'] as $indicator) {
        $stmt->execute([
            ':pillar_code' => $indicator['pillar'],
            ':code' => $indicator['code'],
            ':title' => $indicator['title'],
            ':description' => $indicator['description'],
            ':criteria' => $indicator['criteria'],
            ':default_self_level' => $indicator['default_self_level'],
            ':default_self_score' => $indicator['default_self_score'],
            ':default_auditor_level' => $indicator['default_auditor_level'],
            ':default_auditor_score' => $indicator['default_auditor_score'],
        ]);
    }
}

function get_or_create_assessment(PDO $pdo, int $companyId): array
{
    $stmt = $pdo->prepare('SELECT * FROM assessments WHERE company_id = :company_id ORDER BY id DESC LIMIT 1');
    $stmt->execute([':company_id' => $companyId]);
    $assessment = $stmt->fetch();

    if ($assessment) {
        return $assessment;
    }

    $stmt = $pdo->prepare('INSERT INTO assessments (company_id, status) VALUES (:company_id, "draft")');
    $stmt->execute([':company_id' => $companyId]);

    $stmt = $pdo->prepare('SELECT * FROM assessments WHERE id = :id');
    $stmt->execute([':id' => $pdo->lastInsertId()]);
    return $stmt->fetch();
}

function get_indicators(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM indicators ORDER BY pillar_code, code')->fetchAll();
}

function get_indicator_max_score(array $indicator): float
{
    $values = [
        (float) ($indicator['default_self_score'] ?? 0),
        (float) ($indicator['default_auditor_score'] ?? 0),
    ];
    return max($values);
}

function calculate_points(float $level, array $indicator): float
{
    $maxScore = get_indicator_max_score($indicator);
    return round($level * $maxScore, 2);
}

function get_scores(PDO $pdo, int $assessmentId): array
{
    $stmt = $pdo->prepare('SELECT * FROM assessment_scores WHERE assessment_id = :assessment_id');
    $stmt->execute([':assessment_id' => $assessmentId]);
    $scores = [];
    foreach ($stmt->fetchAll() as $row) {
        $scores[$row['indicator_id']] = $row;
    }
    return $scores;
}

function calculate_totals(array $indicators, array $scores, string $mode): array
{
    $totals = [
        'overall' => 0,
        'pillars' => [],
    ];

    foreach ($indicators as $indicator) {
        $scoreRow = $scores[$indicator['id']] ?? null;
        $value = 0;
        if ($mode === 'auditor') {
            $value = $scoreRow['auditor_score'] ?? 0;
        } else {
            $value = $scoreRow['self_score'] ?? 0;
        }
        $totals['overall'] += (float) $value;
        $totals['pillars'][$indicator['pillar_code']] = ($totals['pillars'][$indicator['pillar_code']] ?? 0) + (float) $value;
    }

    return $totals;
}
