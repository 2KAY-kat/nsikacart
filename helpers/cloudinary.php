<?php
require __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Cloudinary;
use Cloudinary\Api\Exception\ApiError;

function cloudinary_log($msg) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) mkdir($logDir, 0775, true);
    error_log(date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, 3, $logDir . '/cloudinary.log');
}

function getCloudinaryInstance(): Cloudinary {
    $cloud = getenv('CLOUDINARY_CLOUD_NAME');
    $key = getenv('CLOUDINARY_API_KEY');
    $secret = getenv('CLOUDINARY_API_SECRET');

    if (!$cloud || !$key || !$secret) {
        throw new Exception('Missing Cloudinary credentials.');
    }

    return new Cloudinary([
        'cloud' => [
            'cloud_name' => $cloud,
            'api_key' => $key,
            'api_secret' => $secret,
        ],
        'url' => ['secure' => true],
    ]);
}

function cloudinary_upload(string $source, array $options = []) {
    try {
        $cloudinary = getCloudinaryInstance();
        $opts = array_merge([
            'use_filename' => true,
            'unique_filename' => true,
            'resource_type' => 'image'
        ], $options);
        return $cloudinary->uploadApi()->upload($source, $opts);
    } catch (Exception $e) {
        cloudinary_log("Upload failed: " . $e->getMessage());
        throw $e;
    }
}

function cloudinary_delete(string $public_id) {
    if (empty($public_id)) return ['result' => 'skipped (empty id)'];
    try {
        $cloudinary = getCloudinaryInstance();
        $res = $cloudinary->uploadApi()->destroy($public_id);
        cloudinary_log("Deleted $public_id => " . json_encode($res));
        return $res;
    } catch (ApiError $e) {
        cloudinary_log("Delete API error for $public_id: " . $e->getMessage());
        return ['result' => 'error', 'message' => $e->getMessage()];
    } catch (Exception $e) {
        cloudinary_log("Delete error for $public_id: " . $e->getMessage());
        return ['result' => 'error', 'message' => $e->getMessage()];
    }
}