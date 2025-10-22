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

    try {
        return new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloud,
                'api_key'    => $key,
                'api_secret' => $secret,
            ],
            'url' => ['secure' => true]
        ]);
    } catch (Exception $e) {
        cloudinary_log('Cloudinary init error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Upload a file to Cloudinary.
 * $source can be a path to tmp file or a data-url/remote url supported by Cloudinary.
 * Returns upload response array on success.
 */
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
