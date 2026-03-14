<?php

namespace App\Http\Controllers;

use App\Support\BuildsInternshipTrackerData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', BuildsInternshipTrackerData::dashboard($request->user()));
    }
}
