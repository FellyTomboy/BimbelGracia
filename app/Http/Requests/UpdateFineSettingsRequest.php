<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFineSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_penalty_enabled' => ['sometimes', 'boolean'],
            'late_penalty_enabled' => ['sometimes', 'boolean'],
            'billing_mode' => ['sometimes', 'string', 'in:daily,monthly'],
            'late_penalty_type' => ['sometimes', 'string', 'in:percent,fixed'],
            'late_penalty_value' => ['sometimes', 'numeric', 'min:0'],
            'attendance_penalty_type' => ['sometimes', 'string', 'in:percent,fixed'],
            'attendance_penalty_value' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
