<?php

namespace App\Http\Controllers;

use App\Support\BuildsInternshipTrackerData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrCounterController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('HrCounter', BuildsInternshipTrackerData::hrCounter($request->user()));
    }
}
