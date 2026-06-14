<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $url = env('CLOUDINARY_URL');

        if (! $url) {
            throw new RuntimeException('CLOUDINARY_URL environment variable is not set.');
        }

        $this->cloudinary = new Cloudinary($url);
    }

    public function uploadPrescription(UploadedFile $file): string
    {
        $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder'        => 'apra/prescriptions',
            'resource_type' => 'auto',
            'use_filename'  => false,
        ]);

        return $result['secure_url'];
    }

    public function delete(string $publicId): bool
    {
        $result = $this->cloudinary->uploadApi()->destroy($publicId, ['resource_type' => 'image']);

        return ($result['result'] ?? '') === 'ok';
    }
}
