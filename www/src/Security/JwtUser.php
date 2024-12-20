<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class JwtUser implements UserInterface
{
private string $userId;
private array $roles;

public function __construct(string $userId, array $roles)
{
$this->userId = $userId;
$this->roles = $roles;
}

public function getRoles(): array
{
return $this->roles;
}

public function getPassword()
{
// Цей метод не використовується для JWT аутентифікації,
// але його необхідно реалізувати і повернути null
return null;
}

public function getSalt()
{
// Цей метод не використовується для JWT аутентифікації,
// але його необхідно реалізувати і повернути null
return null;
}

public function getUserId(): string
{
return $this->userId;
}

public function getUserIdentifier(): string
{
    return $this->userId;
}

    public function eraseCredentials()
{
// Цей метод не використовується для JWT аутентифікації,
// але його необхідно реалізувати і нічого не робити
}
}
