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
}
