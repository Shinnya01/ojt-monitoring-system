<?php

namespace App\Http\Controllers;

use App\Support\BuildsInternshipTrackerData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Tasks', BuildsInternshipTrackerData::tasks($request->user()));
    }
}
