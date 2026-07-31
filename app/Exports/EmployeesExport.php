<?php

namespace App\Exports;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading, WithCustomCsvSettings
{
    protected EmployeeRepositoryInterface $employeeRepository;

    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function query()
    {
        return $this->employeeRepository->getQueryBuilder()->orderBy('id', 'asc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Address',
            'Salary',
        ];
    }

    /**
     * @param Employee $employee
     */
    public function map($employee): array
    {
        return [
            $employee->id,
            $employee->first_name,
            $employee->last_name,
            $employee->email,
            $employee->phone,
            $employee->address,
            $employee->salary,
        ];
    }

    public function chunkSize(): int
    {
        return config('employee.chunk_size', 1000);
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '"',
            'line_ending' => "\r\n",
            'use_bom' => true,
        ];
    }
}
