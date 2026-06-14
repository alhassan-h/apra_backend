<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'drugs'              => ['nullable', 'array'],
            'drugs.*.drug_name'  => ['nullable', 'string', 'max:255'],
            'drugs.*.dosage'     => ['nullable', 'string', 'max:100'],
            'drugs.*.frequency'  => ['nullable', 'string', 'max:100'],
            'drugs.*.duration'   => ['nullable', 'string', 'max:100'],
            'drugs.*.route'      => ['nullable', 'string', 'max:100'],
            'review_status'      => ['required', 'string', 'in:APPROVED,MODIFIED,REJECTED'],
            'review_notes'       => ['nullable', 'string', 'max:2000'],
        ];
    }
}
