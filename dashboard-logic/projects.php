<?php
/**
 * Proyectos — listado y parseo de metadatos.
 *
 * Responsabilidad única: leer el sistema de archivos, parsear
 * user-data.txt y devolver estructuras de proyecto limpias.
 */

function list_projects(): array {
    $root    = __DIR__ . '/..';
    $items   = array_diff(scandir($root), ['.', '..']);
    $projects = [];

    foreach ($items as $file) {
        if (is_file($root . '/' . $file) || $file === 'assets') {
            continue;
        }

        $data_file = $root . '/' . $file . '/user-data.txt';
        if (!file_exists($data_file)) {
            continue;
        }

        $raw = file_get_contents($data_file);
        if ($raw === false) {
            continue;
        }

        $lines = explode("\n", $raw);
        $projects[] = parse_project($file, $lines);
    }

    return $projects;
}

function parse_project(string $dir, array $lines): array {
    $fields = ['user' => '', 'password' => '', 'type' => ''];

    foreach ($lines as $line) {
        $parts = explode(':', $line, 2);
        if (!empty($parts[0]) && isset($parts[1])) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            if (isset($fields[$key])) {
                $fields[$key] = $val;
            }
        }
    }

    $slug   = strtolower(str_replace(' ', '-', $dir));
    $has_wp = file_exists(__DIR__ . '/../' . $dir . '/wp-config.php');

    return [
        'dir'       => $dir,
        'slug'      => $slug,
        'name'      => ucfirst(str_replace('-', ' ', $dir)),
        'badge'     => $fields['type'] ? type_badge($fields['type']) : '',
        'has_wp'    => $has_wp,
        'card_style'=> $has_wp ? 'border-start border-primary border-4' : '',
        'user'      => htmlspecialchars($fields['user']),
        'password'  => htmlspecialchars($fields['password']),
    ];
}
