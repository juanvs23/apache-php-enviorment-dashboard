<?php
/**
 * Funciones puras — sin estado, sin efectos secundarios.
 *
 * - Crypto: encriptar / desencriptar (AES-256-CBC)
 * - Sistema: get_os
 * - UI: type_badge
 */

function encriptar(string $clave, string $texto): string {
    $metodo    = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($metodo);
    $iv        = openssl_random_pseudo_bytes($iv_length);

    return base64_encode(
        $iv . openssl_encrypt($texto, $metodo, $clave, 0, $iv)
    );
}

function desencriptar(string $clave, string $texto_encriptado): string|false {
    $texto_encriptado = base64_decode($texto_encriptado, true);
    if ($texto_encriptado === false) {
        return false;
    }

    $metodo    = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($metodo);
    $iv        = substr($texto_encriptado, 0, $iv_length);

    return openssl_decrypt(
        substr($texto_encriptado, $iv_length),
        $metodo, $clave, 0, $iv
    );
}

function get_os(): string {
    $os = php_uname('s');

    if (str_contains($os, 'Linux'))   return 'Linux';
    if (str_contains($os, 'Windows')) return 'Windows';
    if (str_contains($os, 'Darwin'))  return 'Mac';

    return 'Desconocido';
}

function type_badge(string $type): string {
    $colors = [
        'wordpress' => 'primary',
        'phpmyadmin' => 'warning',
        'laravel'    => 'danger',
        'symfony'    => 'info',
        'static'     => 'secondary',
    ];

    $color = $colors[strtolower(trim($type))] ?? 'secondary';

    return sprintf(
        '<span class="badge bg-%s ms-2">%s</span>',
        $color,
        htmlspecialchars(trim($type))
    );
}
