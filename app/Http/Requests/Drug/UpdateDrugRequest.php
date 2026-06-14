<?php

namespace App\Http\Requests\Drug;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDrugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'generic_name' => ['sometimes', 'required', 'string', 'max:255'],
            'brand_name'   => ['nullable', 'string', 'max:255'],
            'aliases'      => ['nullable', 'array'],
            'aliases.*'    => ['string', 'max:100'],
            'nafdac_code'  => ['nullable', 'string', 'max:50'],
            'is_active'    => ['sometimes', 'boolean'],
        ];
    }
}
