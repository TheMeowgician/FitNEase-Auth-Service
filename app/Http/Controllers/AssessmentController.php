<?php

namespace App\Http\Controllers;

use App\Models\FitnessAssessment;
use App\Models\User;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'assessment_type' => 'required|string|max:100',
            'assessment_data' => 'required|array',
            'score' => 'nullable|numeric|between:0,999.99',
            'notes' => 'nullable|string',
        ]);

        $assessment = FitnessAssessment::create([
            'user_id' => $request->user_id,
            'assessment_type' => $request->assessment_type,
            'assessment_data' => $request->assessment_data,
            'score' => $request->score,
            'notes' => $request->notes,
            'created_by' => $request->user()->user_id,
            'assessment_date' => now(),
        ]);

        return response()->json($assessment, 201);
    }

    public function index(Request $request)
    {
        $query = FitnessAssessment::with(['user', 'createdBy']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('assessment_type')) {
            $query->where('assessment_type', $request->assessment_type);
        }

        $assessments = $query->orderBy('assessment_date', 'desc')->paginate(20);

        return response()->json($assessments);
    }

    public function show($id)
    {
        $assessment = FitnessAssessment::with(['user', 'createdBy'])->find($id);

        if (!$assessment) {
            return response()->json(['error' => 'Assessment not found'], 404);
        }

        return response()->json($assessment);
    }

    public function update(Request $request, $id)
    {
        $assessment = FitnessAssessment::find($id);

        if (!$assessment) {
            return response()->json(['error' => 'Assessment not found'], 404);
        }

        $request->validate([
            'assessment_data' => 'sometimes|array',
            'score' => 'sometimes|numeric|between:0,999.99',
            'notes' => 'sometimes|nullable|string',
        ]);

        $assessment->update($request->only(['assessment_data', 'score', 'notes']));

        return response()->json($assessment);
    }

    public function destroy($id)
    {
        $assessment = FitnessAssessment::find($id);

        if (!$assessment) {
            return response()->json(['error' => 'Assessment not found'], 404);
        }

        $assessment->delete();

        return response()->json(['message' => 'Assessment deleted successfully']);
    }

    public function getUserAssessments($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $assessments = FitnessAssessment::where('user_id', $userId)
            ->with('createdBy')
            ->orderBy('assessment_date', 'desc')
            ->get();

        return response()->json($assessments);
    }

    public function getAssessmentsByType($userId, $type)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $assessments = FitnessAssessment::where('user_id', $userId)
            ->where('assessment_type', $type)
            ->with('createdBy')
            ->orderBy('assessment_date', 'desc')
            ->get();

        return response()->json($assessments);
    }

    /**
     * Check if user has submitted weekly assessment this week.
     * Returns status and last assessment details if exists.
     */
    public function getWeeklyAssessmentStatus(Request $request)
    {
        $userId = $request->user()->user_id;

        // Calculate start of current week (Monday 00:00:00)
        $weekStart = now()->startOfWeek(\Carbon\Carbon::MONDAY);
        $weekEnd = now()->endOfWeek(\Carbon\Carbon::SUNDAY);

        // Find weekly assessment submitted this week
        $thisWeekAssessment = FitnessAssessment::where('user_id', $userId)
            ->where('assessment_type', 'weekly')
            ->whereBetween('assessment_date', [$weekStart, $weekEnd])
            ->orderBy('assessment_date', 'desc')
            ->first();

        // Get last weekly assessment (regardless of week)
        $lastAssessment = FitnessAssessment::where('user_id', $userId)
            ->where('assessment_type', 'weekly')
            ->orderBy('assessment_date', 'desc')
            ->first();

        $completedThisWeek = $thisWeekAssessment !== null;

        return response()->json([
            'completed_this_week' => $completedThisWeek,
            'week_start' => $weekStart->toISOString(),
            'week_end' => $weekEnd->toISOString(),
            'this_week_assessment' => $thisWeekAssessment ? [
                'id' => $thisWeekAssessment->assessment_id,
                'submitted_at' => $thisWeekAssessment->assessment_date,
                'score' => $thisWeekAssessment->score,
            ] : null,
            'last_assessment' => $lastAssessment ? [
                'id' => $lastAssessment->assessment_id,
                'submitted_at' => $lastAssessment->assessment_date,
                'score' => $lastAssessment->score,
            ] : null,
        ]);
    }
}
