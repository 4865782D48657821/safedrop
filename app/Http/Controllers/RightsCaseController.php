<?php

namespace App\Http\Controllers;

use App\Models\ModerationCase;
use App\Models\Project;
use App\Models\RightsCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RightsCaseController extends Controller
{
    public function create(): View
    {
        return view('rights.create', [
            'projects' => Project::query()
                ->publiclyVisible()
                ->orderBy('title')
                ->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')
                    ->whereIn('publication_status', config('safedrop.public_project_statuses.publication'))
                    ->whereIn('moderation_status', config('safedrop.public_project_statuses.moderation')),
            ],
            'claimant_name' => ['required', 'string', 'max:120'],
            'claimant_email' => ['required', 'email', 'max:255'],
            'claim_type' => ['required', Rule::in(config('safedrop.rights_claim_types'))],
            'details' => ['required', 'string', 'min:20', 'max:3000'],
        ]);

        $claimantEmail = strtolower($data['claimant_email']);
        $case = RightsCase::query()->firstOrCreate(
            [
                'fingerprint' => hash('sha256', implode('|', [
                    $data['project_id'] ?? '',
                    $claimantEmail,
                    $data['claim_type'],
                    trim($data['details']),
                ])),
            ],
            [
                'project_id' => $data['project_id'] ?? null,
                'claimant_name' => $data['claimant_name'],
                'claimant_email' => $claimantEmail,
                'claim_type' => $data['claim_type'],
                'details' => $data['details'],
            ],
        );

        ModerationCase::openForSubject(
            $case,
            'rights_case',
            'high',
            "Rights case: {$case->claim_type}",
        );

        return redirect()
            ->route('rights.create')
            ->with('status', 'Rights case submitted for moderation.');
    }
}
