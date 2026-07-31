<?php

namespace App\GraphQL\Queries;

use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeQueries
{
    protected EmployeeRepositoryInterface $employeeRepository;

    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function index($_, array $args)
    {
        $first = $args['first'] ?? config('employee.default_per_page', 15);
        $search = $args['search'] ?? null;

        $paginator = $this->employeeRepository->paginate($first, $search);

        return [
            'paginatorInfo' => [
                'count' => $paginator->count(),
                'currentPage' => $paginator->currentPage(),
                'firstItem' => $paginator->firstItem(),
                'hasMorePages' => $paginator->hasMorePages(),
                'lastItem' => $paginator->lastItem(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'data' => $paginator->items(),
        ];
    }

    public function show($_, array $args)
    {
        return $this->employeeRepository->findOrFail((int) $args['id']);
    }
}
