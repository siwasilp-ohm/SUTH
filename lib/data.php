<?php
function load_hicm_data(): array
{
    $path = __DIR__ . '/../data/hicm-indicators.json';
    $raw = file_get_contents($path);
    return json_decode($raw, true);
}

function group_indicators_by_pillar(array $indicators): array
{
    $grouped = [];
    foreach ($indicators as $indicator) {
        $grouped[$indicator['pillar']][] = $indicator;
    }
    return $grouped;
}
