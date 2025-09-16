<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
        return [
            'username' => 'required|string|max:50|unique:users',
            'email' => 'required|email|max:100|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'age' => 'required|integer|between:18,100',
            'gender' => 'nullable|in:male,female,other',
            'fitness_level' => 'nullable|in:beginner,medium,expert',
            'target_muscle_groups' => 'nullable|array',
            'target_muscle_groups.*' => 'in:core,upper_body,lower_body',
            'fitness_goals' => 'nullable|array',
            'fitness_goals.*' => 'in:weight_loss,muscle_gain,endurance,strength,general_fitness',
            'activity_level' => 'nullable|in:sedentary,lightly_active,moderately_active,very_active',
            'medical_conditions' => 'nullable|string',
            'workout_experience_years' => 'nullable|integer|min:0',
            'available_equipment' => 'nullable|array',
            'available_equipment.*' => 'in:none,dumbbells,resistance_bands,yoga_mat,other',
            'time_constraints_minutes' => 'nullable|integer|min:1',
            'phone_number' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'age.between' => 'Age must be between 18 and 100.',
        ];
    }
}
