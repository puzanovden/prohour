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

        $passwordIsValid = password_verify($password, $user['password']);

        if (!$passwordIsValid && $user['password'] === $password) {
            $passwordIsValid = true;
            $this->userRepository->updateUserPassword((int)$user['id'], $password);
        }

        if (!$passwordIsValid) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $user['avatar'] ?? '';
        $_SESSION['user_role'] = $user['role'] ?? 'employee';
        $_SESSION['team_id'] = $user['team_id'] ?? 1;

        if (isset($user['role'])) {
            $_SESSION['user_role'] = $user['role'];
        }

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

    public function getCurrentUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return $this->userRepository->getUserById((int)$_SESSION['user_id']);
    }

    public function updateCurrentUserName(string $name): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $updated = $this->userRepository->updateUserName((int)$_SESSION['user_id'], $name);

        if ($updated) {
            $_SESSION['user_name'] = $name;
        }

        return $updated;
    }

    public function updateCurrentUserPassword(string $password): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        return $this->userRepository->updateUserPassword((int)$_SESSION['user_id'], $password);
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_role']);
        unset($_SESSION['user_avatar']);
        unset($_SESSION['team_id']);
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function getCurrentUserId(): ?int
    {
        return self::isLoggedIn() ? (int)$_SESSION['user_id'] : null;
    }

    public function updateCurrentUserAvatar(string $avatar): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $updated = $this->userRepository->updateUserAvatar((int)$_SESSION['user_id'], $avatar);

        if ($updated) {
            $_SESSION['user_avatar'] = $avatar;
        }

        return $updated;
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();

        if (!self::isAdmin()) {
            header('Location: tasks.php');
            exit;
        }
    }

    public static function getCurrentTeamId(): int
    {
        return (int)($_SESSION['team_id'] ?? 1);
    }

    public static function isManager(): bool
    {
        return ($_SESSION['user_role'] ?? '') === 'manager';
    }

    public static function canSeeAllTeamData(): bool
    {
        $role = $_SESSION['user_role'] ?? 'employee';

        return in_array($role, ['admin', 'manager'], true);
    }

    public static function getCurrentRole(): string
    {
        return $_SESSION['user_role'] ?? 'employee';
    }

    private function setUserSession(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $user['avatar'] ?? '';
        $_SESSION['user_role'] = $user['role'] ?? 'employee';
        $_SESSION['team_id'] = $user['team_id'] ?? 1;
    }

    public function loginWithGooglePayload(array $payload): bool
    {
        $googleId = (string)($payload['sub'] ?? '');
        $email = (string)($payload['email'] ?? '');
        $name = (string)($payload['name'] ?? '');
        $avatar = (string)($payload['picture'] ?? '');

        if ($googleId === '' || $email === '') {
            return false;
        }

        $user = $this->userRepository->getUserByGoogleId($googleId);

        if (!$user) {
            $user = $this->userRepository->getUserByEmail($email);

            if ($user) {
                $this->userRepository->linkGoogleToUser((int)$user['id'], $googleId);
                $user = $this->userRepository->getUserByEmail($email);
            } else {
                $newUserId = $this->userRepository->createGoogleUser(
                    $email,
                    $name !== '' ? $name : $email,
                    $googleId,
                    $avatar,
                    1,
                    'employee'
                );

                $user = $this->userRepository->getUserById($newUserId);
            }
        }

        if (!$user) {
            return false;
        }

        $this->setUserSession($user);

        return true;
    }
}