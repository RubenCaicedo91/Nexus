<?php
/**
 * Generador de diagrama MER (PlantUML) a partir de migraciones Laravel.
 * Uso: php scripts/generate_mer_diagram.php
 * Salida: docs/mer_diagram.puml
 */

$dir = __DIR__ . '/../database/migrations';
$outDir = __DIR__ . '/../docs';
if (!is_dir($outDir)) mkdir($outDir, 0755, true);
$pumlFile = $outDir . '/mer_diagram.puml';

$tables = [];
$relations = [];

foreach (glob($dir . '/*.php') as $file) {
    $code = file_get_contents($file);

    // Detectar nombre de tabla
    if (preg_match_all('/Schema::create\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $code, $m)) {
        foreach ($m[1] as $table) {
            if (!isset($tables[$table])) $tables[$table] = [];
        }
    }

    // Detectar columnas
    if (preg_match_all('/\$table->([a-zA-Z0-9_]+)\(([^)]*)\)/', $code, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $colType = $match[1];
            $args = $match[2];
            $before = substr($code, 0, strpos($code, $match[0]));
            if (preg_match_all('/Schema::create\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $before, $mt)) {
                $table = end($mt[1]);
            } else {
                continue;
            }
            if (!isset($tables[$table])) $tables[$table] = [];
            if (preg_match('/[\'"]([a-zA-Z0-9_]+)[\'"]/', $args, $mc)) {
                $colName = $mc[1];
            } else {
                $colName = trim($args);
            }
            $tables[$table][] = [$colType, $colName];

            // Heurística: si termina en _id, inferir posible relación
            if (preg_match('/([a-zA-Z0-9_]+)_id$/', $colName, $match)) {
                $guessedTable = $match[1] . 's'; // Ej: docente_id → docentes
                $relations[] = [$colName, 'id', $guessedTable];
            }
        }
    }

    // Relaciones explícitas con foreign()->references()->on()
    if (preg_match_all('/->foreign\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\).*?->references\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\).*?->on\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $code, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $fromCol = $match[1];
            $toCol = $match[2];
            $toTable = $match[3];
            $relations[] = [$fromCol, $toCol, $toTable];
        }
    }

    // Relaciones con foreignId()->constrained()
    if (preg_match_all('/->foreignId\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\)->constrained\(\s*[\'"]?([a-zA-Z0-9_]*)[\'"]?\s*\)/', $code, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $fromCol = $match[1];
            $toTable = $match[2] ?: preg_replace('/_id$/', '', $fromCol);
            $toCol = 'id';
            $relations[] = [$fromCol, $toCol, $toTable];
        }
    }
}

// Generar PlantUML
$lines = [];
$lines[] = '@startuml';
$lines[] = 'hide circle';
$lines[] = '';

foreach ($tables as $table => $cols) {
    $lines[] = "entity {$table} {";
    foreach ($cols as $col) {
        $lines[] = "  {$col[0]} {$col[1]}";
    }
    $lines[] = "}";
    $lines[] = '';
}

foreach ($relations as [$fromCol, $toCol, $toTable]) {
    $fromTable = null;
    foreach ($tables as $t => $cols) {
        foreach ($cols as $c) {
            if (strcasecmp($c[1], $fromCol) === 0) {
                $fromTable = $t;
                break 2;
            }
        }
    }
    if ($fromTable) {
        $lines[] = "{$fromTable}::{$fromCol} --> {$toTable}::{$toCol}";
    } else {
        $lines[] = "'Relación no identificada: {$fromCol} --> {$toTable}::{$toCol}'";
    }
}

$lines[] = '';
$lines[] = '@enduml';
file_put_contents($pumlFile, implode("\n", $lines));
echo "MER generado en: {$pumlFile}\n";
