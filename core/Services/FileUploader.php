<?php
declare(strict_types=1);

namespace Syncro\Services;

use Exception;
use finfo;

class FileUploader
{
    /**
     * Uploads a file securely.
     *
     * @param array $file The $_FILES['input_name'] array.
     * @param string $destination The directory to save the file.
     * @param array $allowedMimeTypes Allowed MIME types.
     * @return string The path to the uploaded file.
     * @throws Exception If validation or upload fails.
     */
    public static function upload(array $file, string $destination, array $allowedMimeTypes = []): string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid parameters.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file sent.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('Exceeded filesize limit.');
            default:
                throw new Exception('Unknown error during file upload.');
        }

        // Rigorously check true MIME type using finfo_file
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $trueMimeType = $finfo->file($file['tmp_name']);

        if (!empty($allowedMimeTypes) && !in_array($trueMimeType, $allowedMimeTypes, true)) {
            throw new Exception('Invalid file format. Detected MIME type: ' . $trueMimeType);
        }

        // Strip executable extensions
        $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $executableExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'sh', 'exe', 'bat', 'cmd', 'js', 'jar', 'jsp', 'pl', 'py', 'rb'];
        if (in_array($originalExt, $executableExtensions, true)) {
            throw new Exception('Executable files are not allowed.');
        }

        $safeExt = $originalExt ? '.' . $originalExt : '';

        // Generate randomized filename
        $fileName = bin2hex(random_bytes(16)) . $safeExt;
        
        // Ensure destination directory exists
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                throw new Exception('Failed to create upload destination directory.');
            }
        }

        $targetFile = rtrim($destination, '/') . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            throw new Exception('Failed to move uploaded file.');
        }

        return $targetFile;
    }
}
