<?php

namespace App\GraphQL\Mutations;

use App\Http\Requests\EmployeeValidationRules;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Validator;

class EmployeeMutations
{
    protected EmployeeRepositoryInterface $employeeRepository;
    protected EmployeeService $employeeService;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepository,
        EmployeeService $employeeService
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->employeeService = $employeeService;
    }

    public function create($_, array $args)
    {
        $input = $args['input'];

        $validator = Validator::make(
            $input,
            EmployeeValidationRules::createRules(),
            EmployeeValidationRules::messages()
        );

        if ($validator->fails()) {
            throw new Error($validator->errors()->first());
        }

        return $this->employeeRepository->create($input);
    }

    public function update($_, array $args)
    {
        $id = (int) $args['id'];
        $input = array_filter($args['input'], fn($value) => $value !== null);

        $validator = Validator::make(
            $input,
            EmployeeValidationRules::updateRules($id),
            EmployeeValidationRules::messages()
        );

        if ($validator->fails()) {
            throw new Error($validator->errors()->first());
        }

        return $this->employeeRepository->update($id, $input);
    }

    public function delete($_, array $args): bool
    {
        $id = (int) $args['id'];
        return $this->employeeRepository->delete($id);
    }

    public function restore($_, array $args): bool
    {
        $id = (int) $args['id'];
        return $this->employeeRepository->restore($id);
    }

    public function forceDelete($_, array $args): bool
    {
        $id = (int) $args['id'];
        return $this->employeeRepository->forceDelete($id);
    }

    public function generateFaker($_, array $args): array
    {
        $defaultCount = config('employee.default_faker_count', 10000);
        $maxCount = config('employee.max_faker_count', 50000);
        $count = (int) ($args['count'] ?? $defaultCount);

        if ($count < 1 || $count > $maxCount) {
            throw new Error("Count must be between 1 and " . number_format($maxCount) . ".");
        }

        return $this->employeeService->generateFakerEmployees($count);
    }

    public function importCsv($_, array $args): array
    {
        $file = $args['file'] ?? null;
        if (!$file) {
            throw new Error('Please select a CSV or Excel file to upload.');
        }

        $validator = Validator::make(['file' => $file], [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                function ($attribute, $value, $fail) {
                    if ($value && method_exists($value, 'getClientOriginalName')) {
                        $filename = strtolower($value->getClientOriginalName());
                        if (!str_contains($filename, 'employee')) {
                            $fail("The uploaded file name '{$value->getClientOriginalName()}' is invalid. Please upload an employee file named 'employees.csv' or 'employees.xlsx'.");
                        }
                    }
                },
            ],
        ], [
            'file.required' => 'Please select a CSV or Excel file to upload.',
            'file.file'     => 'The uploaded item must be a valid file.',
            'file.mimes'    => 'Invalid file format. The file extension must be .csv, .xlsx, or .xls.',
        ]);

        if ($validator->fails()) {
            throw new Error($validator->errors()->first());
        }

        return $this->employeeService->importEmployeesFromFile($file);
    }
}
