<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Auth;

class AuthQueries
{
    public function me($_, array $args)
    {
        return Auth::user();
    }
}
