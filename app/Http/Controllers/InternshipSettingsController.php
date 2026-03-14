<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertInternshipSettingRequest;
use Illuminate\Http\RedirectResponse;

class InternshipSettingsController extends Controller
{
    public function store(UpsertInternshipSettingRequest $request): RedirectResponse
    {
        $request->user()->internshipSetting()->updateOrCreate(
            [],
            [
                'start_date' => $request->string('start_date')->toString(),
                'required_hours' => $request->integer('required_hours'),
                'regular_workdays' => $request->input('regular_workdays'),
                'default_start_time' => $request->string('default_start_time')->toString().':00',
                'default_end_time' => $request->string('default_end_time')->toString().':00',
                'setup_completed_at' => now(),
            ],
        );

        return redirect()->back();
    }
}
