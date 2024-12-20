<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class JwtUserProvider implements UserProviderInterface
{
    public function loadUserByIdentifier(string $userId): UserInterface
    {
        // Тут можна виконати логіку отримання користувача по JWT токену,
        // або створити "фіктивного" користувача з роллю отриманою з JWT claims

        $roles = ["ROLE_USER"]; // Отримані ролі з JWT claims

        return new JwtUser($userId, $roles);
    }

    public function refreshUser(UserInterface $user)
    {
        // Для stateless автентифікації з JwtAuthenticator
        return $user;
    }

    public function supportsClass($class)
    {
        return JwtUser::class === $class;
    }
}
