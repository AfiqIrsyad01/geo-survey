<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Approval;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function store(Request $request, Survey $survey)
    {
        $request->validate([
            'decision' => 'required|in:approved,rejected',
            'comments' => 'nullable|string',
        ]);

        Approval::create([
            'survey_id' => $survey->id,
            'user_id' => auth()->id(),
            'decision' => $request->decision,
            'comments' => $request->comments,
        ]);

        $survey->update(['status' => $request->decision]);

        // Notify the Staff who submitted the survey
        \App\Models\AppNotification::create([
            'user_id' => $survey->user_id,
            'message' => "Your survey for project '{$survey->project->name}' has been " . strtoupper($request->decision),
            'is_read' => false
        ]);
        
        return back()->with('success', 'Survey updated successfully.');
    }
}
