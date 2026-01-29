<?php
$pdo = require __DIR__ . '/../lib/db.php';
$data = json_decode(file_get_contents(__DIR__ . '/../data/hicm-indicators.json'), true);

$pdo->exec('DELETE FROM indicators');

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

echo 'Imported ' . count($data['indicators']) . " indicators.\n";
