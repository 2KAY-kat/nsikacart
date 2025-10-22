<?php
require __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Cloudinary;
use Cloudinary\Api\Exception\ApiError;

function cloudinary_log($msg) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) mkdir($logDir, 0775, true);
    error_log(date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, 3, $logDir . '/cloudinary_errors.log');
}

function getCloudinaryInstance(): Cloudinary {
    $cloud = getenv('CLOUDINARY_CLOUD_NAME') ?: null;
    $key = getenv('CLOUDINARY_API_KEY') ?: null;
    $secret = getenv('CLOUDINARY_API_SECRET') ?: null;

    if (!$cloud || !$key || !$secret) {
        cloudinary_log('Missing Cloudinary credentials (CLOUDINARY_CLOUD_NAME|API_KEY|API_SECRET).');
        throw new Exception('Cloudinary credentials not configured.');
    }

    return new Cloudinary([
        'cloud' => [
            'cloud_name' => $cloud,
            'api_key' => $key,
            'api_secret' => $secret
        ],
        'url' => ['secure' => true]
    ]);
}

function cloudinary_upload(string $source, array $options = []) {
    try {
        $cloudinary = getCloudinaryInstance();
        $defaultOptions = [
            'use_filename' => true,
            'unique_filename' => true,
            'resource_type' => 'image'
        ];
        $opts = array_merge($defaultOptions, $options);
        $resp = $cloudinary->uploadApi()->upload($source, $opts);
        return $resp;
    } catch (ApiError $ae) {
        cloudinary_log('Cloudinary API error: ' . $ae->getMessage());
        throw $ae;
    } catch (Exception $e) {
        cloudinary_log('Cloudinary upload error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Extract Cloudinary public_id from a given Cloudinary URL
 */
function cloudinary_extract_public_id(string $url): ?string {
    try {
        if (empty($url)) return null;
        $parsed = parse_url($url, PHP_URL_PATH);
        if (!$parsed) return null;

        $parts = explode('/', trim($parsed, '/'));
        $file = end($parts);
        $folderParts = array_slice($parts, array_search('upload', $parts) + 1, -1);
        $folderPath = implode('/', $folderParts);
        $public_id = pathinfo($file, PATHINFO_FILENAME);

        return $folderPath ? "$folderPath/$public_id" : $public_id;
    } catch (Exception $e) {
        cloudinary_log("Extract public_id error: " . $e->getMessage());
        return null;
    }
}

/**
 * Delete an image from Cloudinary by public_id
 */
function cloudinary_delete(string $public_id) {
    try {
        $cloudinary = getCloudinaryInstance();
        $result = $cloudinary->uploadApi()->destroy($public_id);
        return $result;
    } catch (ApiError $ae) {
        cloudinary_log("Delete API error: " . $ae->getMessage());
        throw $ae;
    } catch (Exception $e) {
        cloudinary_log("Delete error: " . $e->getMessage());
        throw $e;
    }
}