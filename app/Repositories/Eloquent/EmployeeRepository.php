<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }

    public function paginate(?int $perPage = null, ?string $search = null): LengthAwarePaginator
    {
        $perPage = $perPage ?: config('employee.default_per_page', 15);
        $query = $this->model->newQuery();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    public function bulkInsert(array $data, ?int $chunkSize = null): int
    {
        $chunkSize = $chunkSize ?: config('employee.chunk_size', 1000);
        $insertedCount = 0;

        foreach (array_chunk($data, $chunkSize) as $chunk) {
            $insertedCount += $this->model->insertOrIgnore($chunk);
        }

        return $insertedCount;
    }

    public function upsertEmployees(array $data, array $uniqueBy = ['email'], ?array $updateColumns = null): int
    {
        $chunkSize = config('employee.chunk_size', 1000);

        if (empty($updateColumns)) {
            $updateColumns = ['first_name', 'last_name', 'phone', 'address', 'salary', 'updated_at', 'deleted_at'];
        }

        $processedCount = 0;
        foreach (array_chunk($data, $chunkSize) as $chunk) {
            $processedCount += $this->model->upsert($chunk, $uniqueBy, $updateColumns);
        }

        return $processedCount;
    }

    public function getQueryBuilder(): Builder
    {
        return $this->model->newQuery();
    }

    public function restore(int $id): bool
    {
        /** @var Employee $employee */
        $employee = $this->model->withTrashed()->findOrFail($id);
        return (bool) $employee->restore();
    }

    public function forceDelete(int $id): bool
    {
        /** @var Employee $employee */
        $employee = $this->model->withTrashed()->findOrFail($id);
        return (bool) $employee->forceDelete();
    }

    public function existsByEmailOrPhone(string $email, ?string $phone = null, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where(function ($q) use ($email, $phone) {
                $q->where('email', $email);
                if (!empty($phone)) {
                    $q->orWhere('phone', $phone);
                }
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
