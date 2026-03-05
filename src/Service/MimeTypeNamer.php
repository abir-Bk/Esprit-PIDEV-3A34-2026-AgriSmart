<?php
// src/Service/MimeTypeNamer.php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

/** @implements NamerInterface<object> */
class MimeTypeNamer implements NamerInterface
{
    public function name(object $object, PropertyMapping $mapping): string
    {
        $file = $mapping->getFile($object);

        if ($file instanceof UploadedFile) {
            $mimeType = $file->getMimeType();

            // Map MIME -> extension fiable
            $mimeToExt = [
                'application/pdf'   => 'pdf',
                'application/x-pdf' => 'pdf',
                'image/jpeg'        => 'jpg',
                'image/jpg'         => 'jpg',
                'image/png'         => 'png',
                'image/gif'         => 'gif',
                'image/webp'        => 'webp',
            ];

            if (isset($mimeToExt[$mimeType])) {
                $ext = $mimeToExt[$mimeType];
            } else {
                // Fallback sur l'extension originale du fichier client
                $ext = strtolower($file->getClientOriginalExtension());
            }

            // Whitelist de sécurité
            $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (empty($ext) || !in_array($ext, $allowed, true)) {
                $ext = 'bin';
            }
        } else {
            if ($file === null) {
                $ext = 'bin';
            } else {
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION)) ?: 'bin';
            }
        }

        return sprintf('%s.%s', uniqid('', true), $ext);
    }
}