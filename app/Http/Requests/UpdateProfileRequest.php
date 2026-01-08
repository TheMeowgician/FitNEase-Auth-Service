<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        // RESEARCH REQUIREMENT: Age must be between 18-54 years
        // This restriction minimizes the risk of exercise-related injuries
        // during high-intensity Tabata training workouts.
        return [
            'username' => 'sometimes|required|string|max:50|unique:users,username,' . $userId . ',user_id',
            'email' => 'sometimes|required|email|max:100|unique:users,email,' . $userId . ',user_id',
            'first_name' => 'sometimes|required|string|max:50',
            'last_name' => 'sometimes|required|string|max:50',
            'age' => 'sometimes|required|integer|between:18,54',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'target_muscle_groups' => 'sometimes|nullable|array',
            'target_muscle_groups.*' => 'in:core,upper_body,lower_body',
            'fitness_goals' => 'sometimes|nullable|array',
            'fitness_goals.*' => 'in:weight_loss,muscle_gain,endurance,strength,general_fitness',
            'activity_level' => 'sometimes|nullable|in:sedentary,lightly_active,moderately_active,very_active',
            'medical_conditions' => 'sometimes|nullable|string',
            'workout_experience_years' => 'sometimes|integer|min:0',
            'available_equipment' => 'sometimes|nullable|array',
            'available_equipment.*' => 'in:none,dumbbells,resistance_bands,yoga_mat,other',
            'time_constraints_minutes' => 'sometimes|integer|min:1',
            'phone_number' => 'sometimes|nullable|string|max:20',
            'profile_picture' => 'sometimes|nullable|string|max:255',
            'onboarding_completed' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email is already registered.',
            'age.between' => 'Age must be between 18 and 54 years for safety during high-intensity training.',
            'workout_experience_years.min' => 'Workout experience years must be at least 0.',
            'time_constraints_minutes.min' => 'Time constraints must be at least 1 minute.',
        ];
    }
}
