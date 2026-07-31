<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->canPublishProjects(), 403);

        return view('creator.dashboard', [
            'user' => $request->user(),
        ]);
    }
}
