<?php

namespace App\Services;

use App\Exports\EmployeesExport;
use App\Imports\EmployeesImport;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeService
{
    protected EmployeeRepositoryInterface $employeeRepository;

    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Bulk generate employees using Faker and store in database efficiently.
     */
    public function generateFakerEmployees(?int $count = null): array
    {
        $count = $count ?: config('employee.default_faker_count', 10000);
        $maxCount = config('employee.max_faker_count', 50000);

        if ($count > $maxCount) {
            $count = $maxCount;
        }

        $faker = Faker::create();
        $batchSize = config('employee.batch_size', 1000);
        $now = now()->toDateTimeString();
        $totalInserted = 0;

        for ($i = 0; $i < $count; $i += $batchSize) {
            $currentBatch = min($batchSize, $count - $i);
            $batchData = [];

            for ($j = 0; $j < $currentBatch; $j++) {
                $uniqueKey = str_replace('.', '', microtime(true) . rand(1000, 9999) . "_{$i}_{$j}");
                $email = "user_{$uniqueKey}@" . $faker->freeEmailDomain();
                $phone = "+1" . rand(200, 999) . rand(100, 999) . sprintf('%04d', rand(0, 9999));

                $batchData[] = [
                    'first_name' => $faker->firstName(),
                    'last_name'  => $faker->lastName(),
                    'email'      => $email,
                    'phone'      => $phone,
                    'address'    => $faker->address(),
                    'salary'     => $faker->randomFloat(2, 30000, 150000),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $totalInserted += $this->employeeRepository->bulkInsert($batchData, $batchSize);
        }

        return [
            'success' => true,
            'count'   => $totalInserted,
            'message' => "Successfully generated and inserted {$totalInserted} employees using Faker.",
        ];
    }

    /**
     * Export employees to CSV or Excel file.
     */
    public function exportEmployees(string $format = 'csv')
    {
        $format = strtolower($format);
        if ($format === 'xlsx' || $format === 'excel') {
            return Excel::download(new EmployeesExport($this->employeeRepository), 'employees.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        return Excel::download(new EmployeesExport($this->employeeRepository), 'employees.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Backward-compatible helper to export CSV.
     */
    public function exportEmployeesToCsv(string $fileName = 'employees.csv')
    {
        return $this->exportEmployees('csv');
    }

    /**
     * Import employees from CSV / Excel file with validation and duplicate prevention.
     */
    public function importEmployeesFromFile($file): array
    {
        $import = new EmployeesImport($this->employeeRepository);
        Excel::import($import, $file);

        return [
            'success'            => true,
            'processed_rows'     => $import->processedRows,
            'skipped_rows'       => $import->skippedRows,
            'errors'             => $import->errors,
            'message'            => "Successfully imported/updated {$import->processedRows} employee records. Skipped {$import->skippedRows} rows with errors.",
        ];
    }
}
