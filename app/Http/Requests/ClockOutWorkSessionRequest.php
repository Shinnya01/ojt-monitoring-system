<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ClockOutWorkSessionRequest extends FormRequest
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
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $activeSession = $this->user()?->workSessions()
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();

                if ($activeSession === null) {
                    return;
                }

                $elapsedMinutes = $activeSession->start_time->diffInMinutes(now());

                if ((int) $this->integer('break_minutes') > $elapsedMinutes) {
                    $validator->errors()->add('break_minutes', 'Break minutes cannot exceed the session length.');
                }
            },
        ];
    }
}
