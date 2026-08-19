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
        ];
    }
}
