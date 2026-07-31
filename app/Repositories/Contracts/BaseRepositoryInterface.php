<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Model;

    public function findOrFail(int $id): Model;

    public function create(array $attributes): Model;

    public function update(int $id, array $attributes): Model;

    public function delete(int $id): bool;

    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator;
}
