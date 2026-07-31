<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Employee System Configuration Constants
    |--------------------------------------------------------------------------
    |
    | Configuration constants for Employee management system avoiding hardcoded
    | magic numbers across repositories, services, imports/exports, and resolvers.
    |
    */

    // Pagination constants
    'default_per_page' => env('EMPLOYEE_PER_PAGE', 15),
    'max_per_page' => env('EMPLOYEE_MAX_PER_PAGE', 100),

    // Bulk creation & Faker seeding constants
    'default_faker_count' => env('EMPLOYEE_DEFAULT_FAKER_COUNT', 10000),
    'max_faker_count' => env('EMPLOYEE_MAX_FAKER_COUNT', 50000),

    // Data processing batch & chunk sizes
    'chunk_size' => env('EMPLOYEE_CHUNK_SIZE', 1000),
    'batch_size' => env('EMPLOYEE_BATCH_SIZE', 1000),

];
