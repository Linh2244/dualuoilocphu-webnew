<?php
// Các hàm đọc/ghi file JSON có file-lock để tránh mất dữ liệu khi ghi đồng thời
declare(strict_types=1);

function storage_init(): void
{
    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0755, true);
    }
}

function storage_path(string $file): string
{
    return DATA_DIR . '/' . $file;
}

function storage_read(string $file): array
{
    $path = storage_path($file);
    if (!is_file($path)) {
        return [];
    }
    $json = file_get_contents($path);
    if ($json === false || $json === '') {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function storage_append(string $file, array $entry): bool
{
    storage_init();
    $path = storage_path($file);
    $fp = @fopen($path, 'c+');
    if ($fp === false) {
        return false;
    }
    flock($fp, LOCK_EX);

    $size = filesize($path);
    $json = $size > 0 ? fread($fp, $size) : '';
    $data = $json ? (json_decode($json, true) ?: []) : [];
    if (!is_array($data)) {
        $data = [];
    }
    $data[] = $entry;

    ftruncate($fp, 0);
    rewind($fp);
    $written = fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);

    flock($fp, LOCK_UN);
    fclose($fp);
    return $written !== false;
}

function storage_save(string $file, array $data): bool
{
    storage_init();
    $path = storage_path($file);
    $fp = @fopen($path, 'c+');
    if ($fp === false) {
        return false;
    }
    flock($fp, LOCK_EX);

    ftruncate($fp, 0);
    rewind($fp);
    $written = fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);

    flock($fp, LOCK_UN);
    fclose($fp);
    return $written !== false;
}

// Trả JSON và dừng script
function storage_respond_json(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Đọc body JSON của request POST
function storage_get_post_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
}

// ===== Mã hóa dữ liệu nhạy cảm (AES-256-GCM) =====
// Key lưu trong encryption.key (nằm trong thư mục dữ liệu, ngoài web root).
// Trường đã mã hóa có tiền tố "enc:"; giá trị cũ chưa mã hóa vẫn đọc được (fallback).

function storage_encryption_key(): string
{
    storage_init();
    $key = @file_get_contents(ENCRYPTION_KEY_FILE);
    if ($key === false || strlen($key) < 32) {
        $key = random_bytes(32);
        @file_put_contents(ENCRYPTION_KEY_FILE, $key, LOCK_EX);
        @chmod(ENCRYPTION_KEY_FILE, 0600);
    }
    return substr($key, 0, 32);
}

function storage_encrypt(string $plain): string
{
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', storage_encryption_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) {
        return '';
    }
    return base64_encode($iv . $tag . $cipher);
}

function storage_decrypt(string $payload): string
{
    $raw = base64_decode($payload, true);
    if ($raw === false || strlen($raw) < 29) {
        return '';
    }
    $iv     = substr($raw, 0, 12);
    $tag    = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $dec = openssl_decrypt($cipher, 'aes-256-gcm', storage_encryption_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return $dec === false ? '' : $dec;
}

function storage_encrypt_field(?string $plain): ?string
{
    if ($plain === null || $plain === '') {
        return $plain;
    }
    return 'enc:' . storage_encrypt($plain);
}

function storage_decrypt_field(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    if (strncmp($value, 'enc:', 4) === 0) {
        return storage_decrypt(substr($value, 4));
    }
    return (string)$value; // dữ liệu cũ chưa mã hóa
}
