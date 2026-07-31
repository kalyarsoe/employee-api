<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface EmployeeRepositoryInterface extends BaseRepositoryInterface
{
    public function bulkInsert(array $data, ?int $chunkSize = null): int;

    public function upsertEmployees(array $data, array $uniqueBy = ['email'], ?array $updateColumns = null): int;

    public function getQueryBuilder(): Builder;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;

    public function existsByEmailOrPhone(string $email, ?string $phone = null, ?int $ignoreId = null): bool;
}
