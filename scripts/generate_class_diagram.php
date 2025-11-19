<?php
/**
 * Generador sencillo de diagrama de clases (PlantUML) para el directorio `app/`.
 * Requiere `nikic/php-parser` (instalado por Composer en este proyecto si se usa).
 * Uso: php scripts/generate_class_diagram.php
 * Salida: `docs/class_diagram.puml`
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

$appDir = realpath(__DIR__ . '/../app');
if (!is_dir($appDir)) {
    fwrite(STDERR, "Directorio app/ no encontrado. Ejecuta el script desde la raíz del proyecto.\n");
    exit(1);
}

$parser = (new ParserFactory())->createForHostVersion();

$classes = [];

class ClassCollector extends NodeVisitorAbstract
{
    private $namespace = '';
    private $classes;

    public function __construct(& $classes)
    {
        $this->classes = & $classes;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->namespace = $node->name ? $node->name->toString() : '';
        }

        if ($node instanceof Node\Stmt\Class_) {
            $name = $node->name->name ?? null;
            if (!$name) return null;
            $fqcn = $this->namespace ? $this->namespace . '\\' . $name : $name;

            $class = [
                'name' => $fqcn,
                'short' => $name,
                'properties' => [],
                'methods' => [],
                'extends' => null,
                'implements' => [],
                'traits' => [],
            ];

            if ($node->extends) {
                $class['extends'] = $node->extends->toString();
            }

            foreach ($node->implements as $impl) {
                $class['implements'][] = $impl->toString();
            }

            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Property) {
                    foreach ($stmt->props as $prop) {
                        $visibility = 'public';
                        if ($stmt->isProtected()) $visibility = 'protected';
                        if ($stmt->isPrivate()) $visibility = 'private';
                        $class['properties'][] = [$visibility, '$' . $prop->name->name];
                    }
                }

                if ($stmt instanceof Node\Stmt\ClassMethod) {
                    $visibility = 'public';
                    if ($stmt->isProtected()) $visibility = 'protected';
                    if ($stmt->isPrivate()) $visibility = 'private';
                    $params = [];
                    foreach ($stmt->params as $p) {
                        $params[] = '$' . ($p->var->name ?? 'arg');
                    }
                    $class['methods'][] = [$visibility, $stmt->name->name . '(' . implode(', ', $params) . ')'];
                }

                if ($stmt instanceof Node\Stmt\TraitUse) {
                    foreach ($stmt->traits as $t) {
                        $class['traits'][] = $t->toString();
                    }
                }
            }

            $this->classes[$fqcn] = $class;
        }
    }
}

$traverser = new NodeTraverser();
$traverser->addVisitor(new ClassCollector($classes));

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir));
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if ($file->getExtension() !== 'php') continue;

    $code = file_get_contents($file->getPathname());
    try {
        $ast = $parser->parse($code);
        if ($ast) $traverser->traverse($ast);
    } catch (\Throwable $e) {
        fwrite(STDERR, "Advertencia: no se pudo parsear {$file->getPathname()}: " . $e->getMessage() . "\n");
    }
}

// Generar PlantUML
$outDir = __DIR__ . '/../docs';
if (!is_dir($outDir)) mkdir($outDir, 0755, true);
$pumlFile = $outDir . '/class_diagram.puml';

$lines = [];
$lines[] = '@startuml';
$lines[] = "skinparam classAttributeIconSize 0";
$lines[] = '';

foreach ($classes as $class) {
    $short = str_replace('\\', '.', $class['name']);
    $lines[] = "class {$short} {";
    foreach ($class['properties'] as $prop) {
        [$vis, $name] = $prop;
        $prefix = $vis === 'public' ? '+' : ($vis === 'protected' ? '#' : '-');
        $lines[] = "    {$prefix} {$name}";
    }
    foreach ($class['methods'] as $m) {
        [$vis, $sig] = $m;
        $prefix = $vis === 'public' ? '+' : ($vis === 'protected' ? '#' : '-');
        $lines[] = "    {$prefix} {$sig}";
    }
    $lines[] = "}";
    $lines[] = '';
}

// Relaciones (extends, implements, traits)
foreach ($classes as $class) {
    $from = str_replace('\\', '.', $class['name']);
    if ($class['extends']) {
        $to = str_replace('\\', '.', $class['extends']);
        $lines[] = "{$from} --|> {$to}";
    }
    foreach ($class['implements'] as $impl) {
        $to = str_replace('\\', '.', $impl);
        $lines[] = "{$from} ..|> {$to} : implements";
    }
    foreach ($class['traits'] as $trait) {
        $to = str_replace('\\', '.', $trait);
        $lines[] = "{$from} ..> {$to} : uses";
    }
}

$lines[] = '';
$lines[] = '@enduml';

file_put_contents($pumlFile, implode("\n", $lines));

echo "PlantUML generado en: {$pumlFile}\n";

exit(0);
