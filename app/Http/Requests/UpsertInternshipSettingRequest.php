<?php

namespace App\Http\Requests;

use App\Models\InternshipSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertInternshipSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'required_hours' => ['required', 'integer', 'min:1', 'max:100000'],
            'regular_workdays' => ['required', 'array', 'min:1'],
            'regular_workdays.*' => ['required', 'string', Rule::in(InternshipSetting::WORKDAYS)],
            'default_start_time' => ['required', 'date_format:H:i'],
            'default_end_time' => ['required', 'date_format:H:i', 'after:default_start_time'],
        ];
    }
}
