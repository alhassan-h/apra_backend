<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    public function uploadPrescription(UploadedFile $file): string
    {
        $result = cloudinary()->upload($file->getRealPath(), [
            'folder'        => 'apra/prescriptions',
            'resource_type' => 'auto',
            'use_filename'  => false,
        ]);

        return $result->getSecurePath();
    }
}
