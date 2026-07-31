<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeValidationRules;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
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

    public function index(Request $request): JsonResponse
    {
        $defaultPerPage = config('employee.default_per_page', 15);
        $perPage = (int) $request->get('per_page', $defaultPerPage);
        $search = $request->get('search');

        $employees = $this->employeeRepository->paginate($perPage, $search);

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $employee = $this->employeeRepository->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            EmployeeValidationRules::createRules(),
            EmployeeValidationRules::messages()
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $employee = $this->employeeRepository->create($request->all());

        return response()->json([
            'success' => true,
            'data' => $employee,
            'message' => 'Employee created successfully.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            EmployeeValidationRules::updateRules($id),
            EmployeeValidationRules::messages()
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $employee = $this->employeeRepository->update($id, $request->all());

        return response()->json([
            'success' => true,
            'data' => $employee,
            'message' => 'Employee updated successfully.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->employeeRepository->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Employee soft deleted successfully.',
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $this->employeeRepository->restore($id);

        return response()->json([
            'success' => true,
            'message' => 'Employee restored successfully.',
        ]);
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->employeeRepository->forceDelete($id);

        return response()->json([
            'success' => true,
            'message' => 'Employee permanently deleted successfully.',
        ]);
    }

    public function generateFaker(Request $request): JsonResponse
    {
        $defaultCount = config('employee.default_faker_count', 10000);
        $count = (int) $request->get('count', $defaultCount);
        $result = $this->employeeService->generateFakerEmployees($count);

        return response()->json($result);
    }

    public function exportCsv(Request $request)
    {
        $format = $request->get('format', 'csv');
        return $this->employeeService->exportEmployees($format);
    }

    public function importCsv(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                function ($attribute, $value, $fail) {
                    if ($value && method_exists($value, 'getClientOriginalName')) {
                        $filename = strtolower($value->getClientOriginalName());
                        if (!str_contains($filename, 'employee')) {
                            $fail("The uploaded file name '{$value->getClientOriginalName()}' is invalid. Please upload an employee file named 'employees.csv' or 'employees.xlsx' or 'employees.xls'.");
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
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->employeeService->importEmployeesFromFile($request->file('file'));

        return response()->json($result);
    }
}
