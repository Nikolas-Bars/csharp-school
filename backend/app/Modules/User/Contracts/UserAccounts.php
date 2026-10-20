<?php

declare(strict_types=1);

namespace App\Modules\User\Contracts;

/**
 * Аккаунты учеников — то, что модуль User отдаёт другим модулям (Auth и дальше).
 * Модель User наружу не выходит: только DTO.
 */
interface UserAccounts
{
    /**
     * @return UserDTO|null null — почта уже занята
     */
    public function register(NewUserDTO $user): ?UserDTO;

    /**
     * @return UserDTO|null null — нет такой почты или пароль не подходит (что именно — не говорим)
     */
    public function findByCredentials(string $email, string $password): ?UserDTO;

    public function find(int $userId): ?UserDTO;

    /**
     * @return UserDTO|null null — такой почты нет
     */
    public function findByEmail(string $email): ?UserDTO;

    /**
     * Новый пароль открытым текстом — хэшируется при сохранении.
     *
     * @return bool false — ученика уже нет
     */
    public function changePassword(int $userId, string $password): bool;
}
