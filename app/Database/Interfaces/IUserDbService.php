<?php

namespace App\Database\Interfaces;

use App\Database\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface IUserDbService
{
    function create(string $email, string $password): User;
    function selectAllUsers(int $zeroBasedPageIndex): LengthAwarePaginator;
    function selectUserById(string $id): User;
    function selectUserByLicenseKey(string $licenseKey): User;
    function selectUserByEmail(string $email): User;
    function selectUserDetailsByLicenseKey(string $licenseKey): Model;
    function updateField(
        string $updateColumn,
        mixed $updateValue,
        string $searchColumn,
        mixed $searchValue
    ): void;
    function countAdmins(): int;
}
