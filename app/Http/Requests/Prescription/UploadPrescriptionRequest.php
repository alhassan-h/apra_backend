<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Foundation\Http\FormRequest;

class UploadPrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKilobytes = (int) config('app.prescription_max_upload_kb', 10240);

        return [
            'image' => [
                'required',
                'file',
                "max:{$maxKilobytes}",
                'mimes:jpg,jpeg,png,pdf',
            ],
        ];
    }
}
