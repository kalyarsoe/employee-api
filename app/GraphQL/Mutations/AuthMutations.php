<?php

namespace App\GraphQL\Mutations;

use App\Repositories\Contracts\UserRepositoryInterface;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Hash;

class AuthMutations
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login($_, array $args): array
    {
        $username = $args['username'];
        $password = $args['password'];

        $user = $this->userRepository->findByUsernameOrEmail($username);

        if (!$user || !Hash::check($password, $user->password)) {
            throw new Error('Invalid username/email or password credentials.');
        }

        $tokenResult = $user->createToken('GraphQL API Token');

        return [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }
}
