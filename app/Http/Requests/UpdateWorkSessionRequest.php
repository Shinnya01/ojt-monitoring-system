<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateWorkSessionRequest extends FormRequest
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
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $start = $this->input('start_time');
                $end = $this->input('end_time');
                $break = (int) $this->integer('break_minutes');

                if (! $start || ! $end) {
                    return;
                }

                [$startHour, $startMinute] = array_map('intval', explode(':', $start));
                [$endHour, $endMinute] = array_map('intval', explode(':', $end));

                $diff = (($endHour * 60) + $endMinute) - (($startHour * 60) + $startMinute);

                if ($break > $diff) {
                    $validator->errors()->add('break_minutes', 'Break minutes cannot exceed the session length.');
                }
            },
        ];
    }
}
