<?php

namespace App\Database\Services;

use App\Constants\Defaults;
use App\Constants\Ops;
use App\Database\Constants\ActivationCol;
use App\Database\Constants\Table;
use App\Database\Constants\TokenCol;
use App\Database\Constants\UserCol;
use App\Database\Interfaces\IUserDbService;
use App\Database\Models\User;
use App\Helpers\Generators;
use App\Http\Constants\Field;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class UserDbService implements IUserDbService
{
    public function countAdmins(): int
    {
        return User::where(UserCol::IS_ADMIN, true)->count();
    }

    public function create(string $email, string $password): User
    {
        return User::create([
            UserCol::EMAIL => $email,
            UserCol::PASSWORD => Generators::encryptPassword($password),
            UserCol::LICENSE_KEY => Generators::generateLicenseKey(),
            UserCol::IS_ADMIN => false,
        ]);
    }

    public function selectAllUsers(int $zeroBasedPageIndex): LengthAwarePaginator
    {
        $pageName = 'page';

        return User::paginate(
            Defaults::PAGE_SIZE,
            [Ops::WILDCARD],
            $pageName,
            $zeroBasedPageIndex,
        );
    }

    public function selectUserByEmail(string $email): User
    {
        return User::where(UserCol::EMAIL, $email)->firstOrFail();
    }

    public function selectUserById(string $id): User
    {
        return User::findOrFail($id);
    }

    public function selectUserByLicenseKey(string $licenseKey): User
    {
        return User::where(UserCol::LICENSE_KEY, $licenseKey)
            ->firstOrFail();
    }

    public function selectUserDetailsByLicenseKey(string $licenseKey): Model
    {
        $userDetails = User::where(
            Table::USERS.'.'.UserCol::LICENSE_KEY,
            Ops::EQ,
            $licenseKey,
        )
            ->leftJoin(
                Table::TOKENS,
                Table::USERS.'.'.UserCol::ID,
                Ops::EQ,
                Table::TOKENS.'.'.TokenCol::USER_ID,
            )
            ->select(
                Table::USERS.'.'.Ops::WILDCARD,
                Table::TOKENS.'.'.TokenCol::FREE_TOKENS,
                Table::TOKENS.'.'.TokenCol::PAID_TOKENS,
                Table::TOKENS.'.'.TokenCol::LAST_USED,
            )
            ->with([
                Table::ACTIVATIONS => function ($query) {
                    $query->select(ActivationCol::WEBSITE, ActivationCol::USER_ID);
                },
            ])
            ->firstOrFail();

        $userDetails[Field::WEBSITES] = array_map(
            function ($entry) {
                return $entry[ActivationCol::WEBSITE];
            },
            $userDetails
                ->activations
                ->toArray()
        );
        unset($userDetails[Table::ACTIVATIONS]);

        $userDetails[Field::FREE_TOKENS] = $this->calculateRemainingFreeTokens(
            $userDetails[TokenCol::FREE_TOKENS],
            $userDetails[TokenCol::LAST_USED],
        );
        unset($userDetails[TokenCol::FREE_TOKENS]);

        return $userDetails;
    }

    public function updateField(
        string $updateColumn,
        mixed $updateValue,
        string $searchColumn,
        mixed $searchValue,
    ): void {
        $user = User::where($searchColumn, $searchValue)->firstOrFail();
        $user[$updateColumn] = $updateValue;
        $user->save();
    }

    private function calculateRemainingFreeTokens(
        ?int $freeTokensUsedThisMonth,
        ?string $dateTimelastUsed,
    ) {
        $usedTokens = $freeTokensUsedThisMonth ?? 0;

        if ($this->checkIfDateBelongsToCurrentMonth($dateTimelastUsed)) {
            return max(Defaults::FREE_TOKENS_PER_MONTH - $usedTokens, 0);
        }

        return Defaults::FREE_TOKENS_PER_MONTH;
    }

    private function checkIfDateBelongsToCurrentMonth(?string $dateTimeLastUsed)
    {
        if (null === $dateTimeLastUsed) {
            return false;
        }

        $currentDate = new \DateTime();
        $startOfMonth = $currentDate->setTime(0, 0, 0);
        $lastDateTime = new \DateTime($dateTimeLastUsed);

        return $lastDateTime >= $startOfMonth;
    }
}
