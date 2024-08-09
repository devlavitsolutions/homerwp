<?php

namespace App\Database\Interfaces;

use App\Database\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface IUserDbService
{
    public function countAdmins(): int;

    public function create(string $email, string $password): User;

    public function selectAllUsers(int $zeroBasedPageIndex): LengthAwarePaginator;

    public function selectUserByEmail(string $email): User;

    public function selectUserById(string $id): User;

    public function selectUserByLicenseKey(string $licenseKey): User;

    public function selectUserDetailsByLicenseKey(string $licenseKey): Model;

    public function updateField(
        string $updateColumn,
        mixed $updateValue,
        string $searchColumn,
        mixed $searchValue,
    ): void;
}
