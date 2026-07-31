<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByUsernameOrEmail(string $username): ?User
    {
        /** @var User|null $user */
        $user = $this->model->where('username', $username)
                            ->orWhere('email', $username)
                            ->first();

        return $user;
    }
}
