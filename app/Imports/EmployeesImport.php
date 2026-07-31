<?php

namespace App\Imports;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class EmployeesImport implements ToCollection, WithHeadingRow, WithChunkReading, WithCustomCsvSettings
{
    protected EmployeeRepositoryInterface $employeeRepository;
    public int $processedRows = 0;
    public int $skippedRows = 0;
    public array $errors = [];
    protected array $seenEmailsMap = [];
    protected int $currentRowNumber = 1;

    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function collection(Collection $rows)
    {
        $now = now();
        $insertData = [];

        $emails = $rows->pluck('email')->filter()->map(fn($e) => trim($e))->toArray();
        $phones = array_filter($rows->pluck('phone')->filter()->map(fn($p) => trim($p))->toArray());
        $ids    = array_filter($rows->pluck('id')->filter()->map(fn($id) => intval($id))->toArray());

        $existingRecords = Employee::withTrashed()
            ->where(function ($query) use ($emails, $phones, $ids) {
                if (!empty($ids)) {
                    $query->whereIn('id', $ids);
                }
                if (!empty($emails)) {
                    $query->orWhereIn('email', $emails);
                }
                if (!empty($phones)) {
                    $query->orWhereIn('phone', $phones);
                }
            })
            ->get(['id', 'email', 'phone']);

        $existingIds = array_flip($existingRecords->pluck('id')->toArray());
        $existingEmails = array_flip($existingRecords->pluck('email')->filter()->map(fn($e) => strtolower($e))->toArray());
        $existingPhones = array_flip($existingRecords->pluck('phone')->filter()->toArray());

        foreach ($rows as $row) {
            $this->currentRowNumber++;
            $empId = !empty($row['id']) ? intval($row['id']) : null;
            $rowIdentifier = $empId ? "[Employee ID: {$empId}]" : "[Row #{$this->currentRowNumber}]";

            $firstName = trim($row['first_name'] ?? '');
            $lastName  = trim($row['last_name'] ?? '');
            $email     = trim($row['email'] ?? '');
            $phone     = trim($row['phone'] ?? '') ?: null;
            $address   = trim($row['address'] ?? '') ?: null;
            $salary    = floatval($row['salary'] ?? 0.0);

            $validator = Validator::make([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $email,
                'phone'      => $phone,
                'salary'     => $salary,
            ], [
                'first_name' => 'required|string|max:100',
                'last_name'  => 'required|string|max:100',
                'email'      => 'required|email|max:255',
                'phone'      => 'nullable|string|max:50',
                'salary'     => 'required|numeric|min:0',
            ], [
                'first_name.required' => 'First name is required.',
                'last_name.required'  => 'Last name is required.',
                'email.required'      => 'Email address is required.',
                'email.email'         => 'Email must be a valid email format.',
                'salary.min'          => 'Salary cannot be negative.',
            ]);

            if ($validator->fails()) {
                $this->skippedRows++;
                $errorMessages = implode(' ', $validator->errors()->all());
                $this->errors[] = "{$rowIdentifier} Validation failed: {$errorMessages}";
                continue;
            }

            $lowerEmail = strtolower($email);

            if (isset($this->seenEmailsMap[$lowerEmail])) {
                $this->skippedRows++;
                $this->errors[] = "{$rowIdentifier} Duplicate entry skipped: Email '{$email}' appears multiple times in the import file.";
                continue;
            }

            if ($empId && isset($existingIds[$empId])) {
                $this->skippedRows++;
                $this->errors[] = "{$rowIdentifier} Duplicate database entry: An employee with ID {$empId} already exists in the database.";
                continue;
            }

            if (isset($existingEmails[$lowerEmail])) {
                $this->skippedRows++;
                $this->errors[] = "{$rowIdentifier} Duplicate database entry: An employee with email '{$email}' already exists in the database.";
                continue;
            }

            if ($phone && isset($existingPhones[$phone])) {
                $this->skippedRows++;
                $this->errors[] = "{$rowIdentifier} Duplicate database entry: An employee with phone '{$phone}' already exists in the database.";
                continue;
            }

            $this->seenEmailsMap[$lowerEmail] = true;

            $record = [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $email,
                'phone'      => $phone,
                'address'    => $address,
                'salary'     => $salary,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($empId) {
                $record['id'] = $empId;
            }

            $insertData[] = $record;
        }

        if (!empty($insertData)) {
            $inserted = $this->employeeRepository->bulkInsert($insertData);
            $this->processedRows += $inserted;
        }
    }

    public function chunkSize(): int
    {
        return config('employee.chunk_size', 1000);
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter'        => ',',
            'enclosure'        => '"',
            'escape_character' => '\\',
            'input_encoding'   => 'UTF-8',
        ];
    }
}
