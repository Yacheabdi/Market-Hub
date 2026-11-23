<?php
/**
 * Campus Market Hub - Media Storage Service
 * Handles Cloudinary Cloud Uploads with automatic fallback to local XAMPP/disk uploads.
 */

namespace App\Core;

class Storage {
    /**
     * Upload an uploaded file from $_FILES
     * Returns the public URL or relative path to store in the database.
     */
    public static function uploadProductImage(array $file): ?string {
        if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $appConfig = require dirname(__DIR__) . '/config/app.php';
        $cloudConfig = require dirname(__DIR__) . '/config/cloudinary.php';

        $maxSize = $appConfig['upload']['max_file_size'] ?? 5242880;
        $allowedTypes = $appConfig['upload']['allowed_types'] ?? ['image/jpeg', 'image/png', 'image/webp'];

        // Check file size
        if ($file['size'] > $maxSize) {
            throw new \Exception("File is too large. Maximum allowed size is 5MB.");
        }

        // Verify real MIME type using finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedTypes)) {
            throw new \Exception("Invalid image format. Allowed formats: JPG, PNG, WEBP, GIF.");
        }

        // 1. Try Cloudinary if enabled and credentials provided
        if (!empty($cloudConfig['enabled']) && !empty($cloudConfig['cloud_name']) && !empty($cloudConfig['api_key'])) {
            try {
                $cloudUrl = self::uploadToCloudinary($file['tmp_name'], $cloudConfig);
                if ($cloudUrl) {
                    return $cloudUrl;
                }
            } catch (\Exception $e) {
                error_log("Cloudinary upload failed, falling back to local: " . $e->getMessage());
            }
        }

        // 2. Local Fallback (Default for XAMPP / offline campus testing)
        return self::uploadToLocal($file, $mime, $appConfig);
    }

    /**
     * Save to local public/uploads/products/ directory
     */
    private static function uploadToLocal(array $file, string $mime, array $appConfig): string {
        $uploadDir = $appConfig['upload']['local_path'];
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];

        $ext = $extensions[$mime] ?? 'jpg';
        $filename = 'product_' . bin2hex(random_bytes(10)) . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception("Failed to save uploaded image to local storage directory.");
        }

        return $appConfig['upload']['web_path'] . $filename;
    }

    /**
     * Upload directly to Cloudinary via REST API
     */
    private static function uploadToCloudinary(string $filePath, array $config): ?string {
        $timestamp = time();
        $paramsToSign = [
            'folder'    => $config['folder'],
            'timestamp' => $timestamp
        ];
        ksort($paramsToSign);

        $toSign = "";
        foreach ($paramsToSign as $k => $v) {
            $toSign .= "{$k}={$v}&";
        }
        $toSign = rtrim($toSign, '&') . $config['api_secret'];
        $signature = sha1($toSign);

        $url = "https://api.cloudinary.com/v1_1/{$config['cloud_name']}/image/upload";

        $postData = [
            'file'      => new \CURLFile($filePath),
            'api_key'   => $config['api_key'],
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder'    => $config['folder']
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['secure_url'] ?? $result['url'] ?? null;
        }

        return null;
    }

    /**
     * Resolve image URL for frontend rendering
     */
    public static function resolveUrl(?string $path): string {
        if (empty($path)) {
            return 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&auto=format&fit=crop&q=80';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return $path;
    }
}
