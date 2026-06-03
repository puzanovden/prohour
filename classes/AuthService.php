<?php

namespace App\Services;

require_once __DIR__ . '/UserRepository.php';

use App\Repositories\UserRepository;
use PDO;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct(PDO $dbConnection)
    {
        $this->userRepository = new UserRepository($dbConnection);
    }

    public function login(string $email, string $password): bool
    {
        $user = $this->userRepository->getUserByEmail($email);

        if (!$user) {
            return false;
        }

        if ($user['password'] !== $password) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        return true;
    }

    public function register(string $email, string $password, string $name): bool
    {
        $existingUser = $this->userRepository->getUserByEmail($email);

        if ($existingUser) {
            return false;
        }

        $created = $this->userRepository->createUser($email, $password, $name);

        if (!$created) {
            return false;
        }

        return $this->login($email, $password);
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }
}