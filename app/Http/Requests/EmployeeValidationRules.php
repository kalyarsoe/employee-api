<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class EmployeeValidationRules
{
    /**
     * Get validation rules for creating an employee.
     */
    public static function createRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:1', 'max:100', 'regex:/^[\pL\s\-\.\']+$/u'],
            'last_name'  => ['required', 'string', 'min:1', 'max:100', 'regex:/^[\pL\s\-\.\']+$/u'],
            'email'      => ['required', 'string', 'email:rfc,dns', 'max:255', Rule::unique('employees', 'email')->whereNull('deleted_at')],
            'phone'      => ['nullable', 'string', 'max:50', 'regex:/^\+?[0-9\s\-\(\)\.]+$/', Rule::unique('employees', 'phone')->whereNull('deleted_at')],
            'address'    => ['nullable', 'string', 'max:1000'],
            'salary'     => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }

    /**
     * Get validation rules for updating an employee.
     */
    public static function updateRules(int $id): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'min:1', 'max:100', 'regex:/^[\pL\s\-\.\']+$/u'],
            'last_name'  => ['sometimes', 'string', 'min:1', 'max:100', 'regex:/^[\pL\s\-\.\']+$/u'],
            'email'      => ['sometimes', 'string', 'email:rfc,dns', 'max:255', Rule::unique('employees', 'email')->ignore($id)->whereNull('deleted_at')],
            'phone'      => ['nullable', 'string', 'max:50', 'regex:/^\+?[0-9\s\-\(\)\.]+$/', Rule::unique('employees', 'phone')->ignore($id)->whereNull('deleted_at')],
            'address'    => ['nullable', 'string', 'max:1000'],
            'salary'     => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public static function messages(): array
    {
        return [
            'first_name.regex' => 'First name must contain valid alphabetic characters, spaces, hyphens or periods.',
            'last_name.regex'  => 'Last name must contain valid alphabetic characters, spaces, hyphens or periods.',
            'email.email'      => 'Please provide a valid email address format.',
            'email.unique'     => 'An employee with this email address already exists.',
            'phone.regex'      => 'Phone number format is invalid. Allowed characters are numbers, spaces, +, -, (), .',
            'phone.unique'     => 'An employee with this phone number already exists.',
            'salary.min'       => 'Salary cannot be negative.',
            'salary.numeric'   => 'Salary must be a numeric value.',
        ];
    }
}
