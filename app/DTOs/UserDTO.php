<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\UserRole;

final class UserDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly UserRole $role,
        public readonly bool $is_active = true,
        public readonly ?string $employee_id = null,
        public readonly ?float $hourly_rate = null,
        public readonly ?array $permissions = null,
        public readonly ?string $phone = null,
        public readonly ?string $address = null,
        public readonly ?string $hire_date = null,
        public readonly ?string $password = null,
    ) {}

    public function toCreateArray(): array
    {
        $data = $this->toArray();
        if ($this->password !== null && $this->password !== '' && $this->password !== '0') {
            $data['password'] = bcrypt($this->password);
        }
        unset($data['password']);

        return array_filter($data, fn ($value): bool => $value !== null);
    }

    public function toUpdateArray(): array
    {
        $data = $this->toArray();
        unset($data['password']); // Don't update password through regular DTO

        return array_filter($data, fn ($value): bool => $value !== null);
    }
}
