<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Consts\TestApiEndpoints;
use Tests\Consts\TestData;
use Tests\Consts\TestJsonKeys;
use Tests\Consts\TestResponseMessages;
use Tests\Consts\TestStatusCodes;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private const BAD_ID = -1;

    private function login(
        string $email = TestData::SEED_EMAIL,
        string $password = TestData::SEED_PASSWORD,
    ) {
        $BEARER_TOKEN_PREFIX = 'Bearer ';

        $loginResponse = $this->postJson(TestApiEndpoints::LOGIN, [
            TestJsonKeys::EMAIL => $email,
            TestJsonKeys::PASSWORD => $password
        ]);

        $bearerToken = $BEARER_TOKEN_PREFIX . $loginResponse->decodeResponseJson()[TestJsonKeys::TOKEN];

        return $this->withHeader(TestJsonKeys::AUTHORIZATION, $bearerToken);
    }

    private function loginAndCreateASingleUser(
        $email = TestData::USER1_EMAIL,
        $password = TestData::USER1_PASSWORD,
    ): int {
        $newUserResponse = $this
            ->login()
            ->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => $email,
                TestJsonKeys::PASSWORD => $password,
            ])
            ->decodeResponseJson();

        $newUserId = $newUserResponse[TestJsonKeys::USER][TestJsonKeys::ID];

        return $newUserId;
    }

    private function getAdminUserId(): int
    {
        $userList = $this
            ->login()
            ->getJson(TestApiEndpoints::USERS)
            ->decodeResponseJson()[TestJsonKeys::DATA];

        $adminUsers = array_filter(
            $userList,
            function ($user) {
                return $user[TestJsonKeys::IS_ADMIN_CAMEL];
            }
        );

        return reset($adminUsers)[TestJsonKeys::ID];
    }

    private function assertUnsupportedMethodResponse(
        string $route,
        array $data,
        callable $unsupportedFunction,
        string $unsupportedMethodName,
        string ...$supportedMethods
    ) {
        $response = $unsupportedFunction($route, $data);

        $response
            ->assertStatus(TestStatusCodes::METHOD_NOT_SUPPORTED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::METHOD_NOT_SUPPORTED(
                    $unsupportedMethodName,
                    $route,
                    ...$supportedMethods
                )
            ]);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan(TestData::DB_SEED);
    }

    /** @test */
    public function loginSeedUserShouldReturnTokenAndBasicUserDetails()
    {
        $response = $this->postJson(TestApiEndpoints::LOGIN, [
            TestJsonKeys::EMAIL => TestData::SEED_EMAIL,
            TestJsonKeys::PASSWORD => TestData::SEED_PASSWORD
        ]);

        $response->assertStatus(TestStatusCodes::OK);

        $responseData = $response->decodeResponseJson();
        $this->assertIsString($responseData[TestJsonKeys::TOKEN]);
        $this->assertIsArray($responseData[TestJsonKeys::USER]);
        $user = $responseData[TestJsonKeys::USER];
        $this->assertEquals($user[TestJsonKeys::EMAIL], TestData::SEED_EMAIL);
        $this->assertEquals($user[TestJsonKeys::IS_ADMIN], true);
        $this->assertEquals($user[TestJsonKeys::IS_DISABLED], false);
    }
    /** @test */
    public function loginWithWrongPasswordShouldFailWithMessage()
    {
        $DUMMY_STRING_TO_BREAK_THE_PASSWORD = 'dummy-string-to-break-the-password';

        $response = $this->postJson(TestApiEndpoints::LOGIN, [
            TestJsonKeys::EMAIL => TestData::SEED_EMAIL,
            TestJsonKeys::PASSWORD => $DUMMY_STRING_TO_BREAK_THE_PASSWORD . TestData::SEED_PASSWORD
        ]);

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJson([
                TestJsonKeys::MESSAGE => TestResponseMessages::WRONG_CREDENTIALS
            ]);
    }
    /** @test */
    public function loginWithGetMethodShouldFailWithMessage()
    {
        $callable = \Closure::bind(function (...$item) {
            return $this->getJson(...$item);
        }, $this, $this);

        $this->assertUnsupportedMethodResponse(
            TestApiEndpoints::LOGIN,
            [
                TestJsonKeys::EMAIL => TestData::SEED_EMAIL,
                TestJsonKeys::PASSWORD => TestData::SEED_PASSWORD
            ],
            $callable,
            TestData::GET,
            TestData::POST
        );
    }

    /** @test */
    public function registerNewUserShouldReturnDetails()
    {
        $response = $this
            ->login()
            ->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => TestData::USER1_EMAIL,
                TestJsonKeys::PASSWORD => TestData::USER1_PASSWORD
            ]);

        $response
            ->assertStatus(TestStatusCodes::CREATED);

        $responseData = $response->decodeResponseJson();
        $user = $responseData[TestJsonKeys::USER];
        $this->assertIsArray($user);
        $this->assertEquals($user[TestJsonKeys::EMAIL], TestData::USER1_EMAIL);
        $this->assertIsString($user[TestJsonKeys::LICENSE_KEY]);
        $this->assertIsNumeric($user[TestJsonKeys::ID]);
    }
    /** @test */
    public function registerWithShortPasswordShouldFailWithMessage()
    {
        $response = $this
            ->login()
            ->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => TestData::USER1_EMAIL,
                TestJsonKeys::PASSWORD => TestData::SHORT_PASSWORD
            ]);

        $response
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::SHORT_PASSWORD,
            ])
            ->assertJsonFragment([
                TestJSONKeys::PASSWORD => [TestResponseMessages::SHORT_PASSWORD],
            ]);
    }
    /** @test */
    public function registerWithBadEmailShouldFailWithMessage()
    {
        $response = $this
            ->login()
            ->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => TestData::BAD_EMAIL,
                TestJsonKeys::PASSWORD => TestData::USER1_PASSWORD
            ]);

        $response
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::BAD_EMAIL,
            ])
            ->assertJsonFragment([
                TestJSONKeys::EMAIL => [TestResponseMessages::BAD_EMAIL],
            ]);
    }
    /** @test */
    public function registerWithoutPasswordShouldFailWithMessage()
    {
        $response = $this
            ->login()
            ->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => TestData::USER1_EMAIL,
                TestJsonKeys::PASSWORD => TestData::SHORT_PASSWORD
            ]);

        $response
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::SHORT_PASSWORD,
            ])
            ->assertJsonFragment([
                TestJSONKeys::PASSWORD => [TestResponseMessages::SHORT_PASSWORD],
            ]);
    }
    /** @test */
    public function registerWithExistingUserShouldFailWithMessage()
    {
        $response = $this
            ->login()
            ->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => TestData::SEED_EMAIL,
                TestJsonKeys::PASSWORD => TestData::SEED_PASSWORD
            ]);

        $response
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::EMAIL_ALREADY_TAKEN,
            ])
            ->assertJsonFragment([
                TestJSONKeys::EMAIL => [TestResponseMessages::EMAIL_ALREADY_TAKEN],
            ]);
    }
    /** @test */
    public function registerWithGetMethodShouldFailWithMessage()
    {
        $callable = \Closure::bind(function (...$item) {
            return $this->getJson(...$item);
        }, $this, $this);

        $this->assertUnsupportedMethodResponse(
            TestApiEndpoints::REGISTER,
            [
                TestJsonKeys::EMAIL => TestData::USER1_EMAIL,
                TestJsonKeys::PASSWORD => TestData::USER1_PASSWORD
            ],
            $callable,
            TestData::GET,
            TestData::POST
        );
    }
    /** @test */
    public function registerWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => TestData::USER1_EMAIL,
                TestJsonKeys::PASSWORD => TestData::USER1_PASSWORD
            ]);

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function registerWithNonAdminCredentialsShouldFailWithMessage()
    // {
    //     // TODO
    // }

    /** @test */
    public function getAllUsersShouldReturnList()
    {
        $this
            ->login()
            ->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => TestData::USER1_EMAIL,
                TestJsonKeys::PASSWORD => TestData::USER1_PASSWORD
            ]);

        $this
            ->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => TestData::USER2_EMAIL,
                TestJsonKeys::PASSWORD => TestData::USER2_PASSWORD
            ]);

        $responseData = $this
            ->getJson(TestApiEndpoints::USERS)
            ->assertStatus(TestStatusCodes::OK)
            ->decodeResponseJson();

        $data = $responseData[TestJsonKeys::DATA];
        $this->assertIsArray($data);
        $this->assertCount(3, $data);
        $this->assertIsArray($data[0]);
        $this->assertEquals($data[0][TestJsonKeys::EMAIL], TestData::SEED_EMAIL);
        $this->assertIsArray($data[1]);
        $this->assertEquals($data[1][TestJsonKeys::EMAIL], TestData::USER1_EMAIL);
        $this->assertIsArray($data[2]);
        $this->assertEquals($data[2][TestJsonKeys::EMAIL], TestData::USER2_EMAIL);
    }
    /** @test */
    public function getAllUsersByPageShouldPaginate()
    {
        $this->login();

        for ($count = 0; $count < 24; $count++) {
            $this->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => TestData::INDEXED_USER($count),
                TestJsonKeys::PASSWORD => TestData::USER1_PASSWORD
            ]);
        }

        $responseData = $this
            ->getJson(TestApiEndpoints::USERS_BY_PAGE(2))
            ->assertStatus(TestStatusCodes::OK)
            ->decodeResponseJson();

        $data = $responseData[TestJsonKeys::DATA];
        $this->assertIsArray($data);
        $this->assertCount(5, $data);
        $this->assertIsArray($data[0]);
        $this->assertEquals($data[0][TestJsonKeys::EMAIL], TestData::INDEXED_USER(19));
        $this->assertIsArray($data[1]);
        $this->assertEquals($data[1][TestJsonKeys::EMAIL], TestData::INDEXED_USER(20));
        $this->assertIsArray($data[2]);
        $this->assertEquals($data[2][TestJsonKeys::EMAIL], TestData::INDEXED_USER(21));
        $this->assertIsArray($data[3]);
        $this->assertEquals($data[3][TestJsonKeys::EMAIL], TestData::INDEXED_USER(22));
        $this->assertIsArray($data[4]);
        $this->assertEquals($data[4][TestJsonKeys::EMAIL], TestData::INDEXED_USER(23));
    }
    /** @test */
    public function getAllUsersWithWrongPageShouldRecover()
    {
        $this->login();

        for ($count = 0; $count < 24; $count++) {
            $this->postJson(TestApiEndpoints::REGISTER, [
                TestJsonKeys::EMAIL => TestData::INDEXED_USER($count),
                TestJsonKeys::PASSWORD => TestData::USER1_PASSWORD
            ]);
        }

        $responseData = $this
            ->getJson(TestApiEndpoints::USERS_BY_PAGE(3))
            ->assertStatus(TestStatusCodes::OK)
            ->decodeResponseJson();

        $data = $responseData[TestJsonKeys::DATA];
        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }
    /** @test */
    public function getAllUsersWithPostMethodShouldFailWithMessage()
    {
        $callable = \Closure::bind(function (...$item) {
            return $this->postJson(...$item);
        }, $this, $this);

        $this->assertUnsupportedMethodResponse(
            TestApiEndpoints::USERS,
            [],
            $callable,
            TestData::POST,
            TestData::GET,
            TestData::HEAD
        );
    }
    /** @test */
    public function getAllUsersWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->getJson(TestApiEndpoints::USERS);

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function getAllUsersWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }

    /**
     * @test
     * @depends registerNewUserShouldReturnDetails
     * */
    public function getUserShouldReturnDetails()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $responseData = $this
            ->getJson(TestApiEndpoints::USER_BY_ID($newUserId))
            ->assertStatus(TestStatusCodes::OK)
            ->decodeResponseJson();

        $user = $responseData[TestJsonKeys::USER];
        $this->assertIsArray($user);
        $this->assertEquals($user[TestJsonKeys::ID], $newUserId);
        $this->assertEquals($user[TestJsonKeys::EMAIL], TestData::USER1_EMAIL);
        $this->assertIsString($user[TestJsonKeys::LICENSE_KEY_CAMEL]);
        $this->assertEquals($user[TestJsonKeys::PAID_TOKENS], 0);
        $this->assertEquals($user[TestJsonKeys::FREE_TOKENS_REMAINING], TestData::FREE_MONTHLY_TOKENS);
    }
    /** @test */
    public function getUserWithWrongIdShouldFailWithMessage()
    {
        $this
            ->login()
            ->getJson(TestApiEndpoints::USER_BY_ID(self::BAD_ID))
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::BAD_USER_ID
            ])
            ->assertJsonFragment([
                TestJsonKeys::USER_ID => [TestResponseMessages::BAD_USER_ID]
            ]);
    }
    /** @test */
    public function getUserWithPostMethodShouldFailWithMessage()
    {
        $callable = \Closure::bind(function (...$item) {
            return $this->postJson(...$item);
        }, $this, $this);

        $newUserId = $this->loginAndCreateASingleUser();

        $this->assertUnsupportedMethodResponse(
            TestApiEndpoints::USER_BY_ID($newUserId),
            [],
            $callable,
            TestData::POST,
            TestData::GET,
            TestData::HEAD,
        );
    }
    /** @test */
    public function getUserWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->getJson(TestApiEndpoints::USER_BY_ID(0));

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function getUserWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }

    /** @test */
    public function setEmailShouldSucceed()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $putResponse = $this
            ->putJson(
                TestApiEndpoints::USER_EMAIL($newUserId),
                [TestJsonKeys::EMAIL => TestData::USER2_EMAIL]
            );

        $putResponse
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([
                TestJsonKeys::ID => $newUserId,
                TestJsonKeys::EMAIL => TestData::USER2_EMAIL,
            ]);

        $this
            ->getJson(TestApiEndpoints::USER_BY_ID($newUserId))
            ->assertJsonFragment([TestJsonKeys::EMAIL => TestData::USER2_EMAIL]);
    }
    /** @test */
    public function setEmailWithBadEmailShouldFailWithMessage()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $response = $this
            ->putJson(
                TestApiEndpoints::USER_EMAIL($newUserId),
                [TestJsonKeys::EMAIL => TestData::BAD_EMAIL]
            );

        $response
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::BAD_EMAIL,
            ])
            ->assertJsonFragment([
                TestJSONKeys::EMAIL => [TestResponseMessages::BAD_EMAIL],
            ]);
    }
    /** @test */
    public function setEmailWithGetMethodShouldFailWithMessage()
    {
        $callable = \Closure::bind(function (...$item) {
            return $this->getJson(...$item);
        }, $this, $this);

        $newUserId = $this->loginAndCreateASingleUser();

        $this->assertUnsupportedMethodResponse(
            TestApiEndpoints::USER_EMAIL($newUserId),
            [],
            $callable,
            TestData::GET,
            TestData::PUT,
        );
    }
    /** @test */
    public function setEmailWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->getJson(TestApiEndpoints::USER_BY_ID(0));

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function setEmailWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }

    /** @test */
    public function setTokensCountShouldSucceed()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $postResponse = $this
            ->postJson(
                TestApiEndpoints::USER_TOKENS($newUserId),
                [TestJsonKeys::PAID_TOKENS => TestData::SET_TOKEN_COUNT]
            );

        $postResponse
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([
                TestJsonKeys::ID => $newUserId,
                TestJsonKeys::PAID_TOKENS => TestData::SET_TOKEN_COUNT,
            ]);

        $this
            ->getJson(TestApiEndpoints::USER_BY_ID($newUserId))
            ->assertJsonFragment([TestJsonKeys::PAID_TOKENS => TestData::SET_TOKEN_COUNT]);
    }
    /** @test */
    public function setTokensCountWithBadUserIdShouldFailWithMessage()
    {
        $this
            ->login()
            ->postJson(
                TestApiEndpoints::USER_TOKENS(self::BAD_ID),
                [TestJsonKeys::PAID_TOKENS => TestData::SET_TOKEN_COUNT],
            )
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::BAD_USER_ID
            ])
            ->assertJsonFragment([
                TestJsonKeys::USER_ID => [TestResponseMessages::BAD_USER_ID]
            ]);
    }
    /** @test */
    public function setTokensCountWithNegativeNumberShouldFailWithMessage()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $this
            ->login()
            ->postJson(
                TestApiEndpoints::USER_TOKENS($newUserId),
                [TestJsonKeys::PAID_TOKENS => TestData::NEGATIVE_TOKEN_COUNT],
            )
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::REQUIRE_NON_NEGATIVE_TOKENS
            ])
            ->assertJsonFragment([
                TestJsonKeys::PAID_TOKENS => [TestResponseMessages::REQUIRE_NON_NEGATIVE_TOKENS]
            ]);
    }
    /** @test */
    public function setTokensCountWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->postJson(
                TestApiEndpoints::USER_TOKENS(0),
                [TestJsonKeys::PAID_TOKENS => TestData::SET_TOKEN_COUNT]
            );

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function setTokensCountWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }
    /** @test */
    public function addPaidTokensMultipleTimesShouldSucceed()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $putResponse0 = $this
            ->putJson(
                TestApiEndpoints::USER_TOKENS($newUserId),
                [TestJsonKeys::PAID_TOKENS => TestData::ADD_TOKEN_COUNT]
            );

        $putResponse0
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([
                TestJsonKeys::ID => $newUserId,
                TestJsonKeys::PAID_TOKENS => TestData::ADD_TOKEN_COUNT,
            ]);

        $this
            ->getJson(TestApiEndpoints::USER_BY_ID($newUserId))
            ->assertJsonFragment([TestJsonKeys::PAID_TOKENS => TestData::ADD_TOKEN_COUNT]);

        $putResponse1 = $this
            ->putJson(
                TestApiEndpoints::USER_TOKENS($newUserId),
                [TestJsonKeys::PAID_TOKENS => TestData::ADD_TOKEN_COUNT]
            );

        $putResponse1
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([
                TestJsonKeys::ID => $newUserId,
                TestJsonKeys::PAID_TOKENS => 2 * TestData::ADD_TOKEN_COUNT,
            ]);

        $this
            ->getJson(TestApiEndpoints::USER_BY_ID($newUserId))
            ->assertJsonFragment([TestJsonKeys::PAID_TOKENS => 2 * TestData::ADD_TOKEN_COUNT]);
    }
    /** @test */
    public function addTokensCountWithNegativeNumberShouldFailWithMessage()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $this
            ->login()
            ->putJson(
                TestApiEndpoints::USER_TOKENS($newUserId),
                [TestJsonKeys::PAID_TOKENS => TestData::NEGATIVE_TOKEN_COUNT],
            )
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::REQUIRE_NON_NEGATIVE_TOKENS
            ])
            ->assertJsonFragment([
                TestJsonKeys::PAID_TOKENS => [TestResponseMessages::REQUIRE_NON_NEGATIVE_TOKENS]
            ]);
    }
    /** @test */
    public function addTokensCountWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->putJson(
                TestApiEndpoints::USER_TOKENS(0),
                [TestJsonKeys::PAID_TOKENS => TestData::SET_TOKEN_COUNT]
            );

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function addTokensCountWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }
    /**
     * @test
     * @depends setTokensCountShouldSucceed
     */
    public function deleteTokensCountShouldSucceed()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $this
            ->postJson(
                TestApiEndpoints::USER_TOKENS($newUserId),
                [TestJsonKeys::PAID_TOKENS => TestData::SET_TOKEN_COUNT]
            );

        $deleteResponse = $this
            ->deleteJson(TestApiEndpoints::USER_TOKENS($newUserId));

        $deleteResponse
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([
                TestJsonKeys::ID => $newUserId,
                TestJsonKeys::PAID_TOKENS => 0,
            ]);

        $this
            ->getJson(TestApiEndpoints::USER_BY_ID($newUserId))
            ->assertJsonFragment([TestJsonKeys::PAID_TOKENS => 0]);
    }
    /** @test */
    public function deleteTokensCountWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->deleteJson(TestApiEndpoints::USER_TOKENS(0));

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function deleteTokensCountWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }
    /** @test */
    public function useTokensCountRouteWithGetMethodShouldFailWithMessage()
    {
        $callable = \Closure::bind(function (...$item) {
            return $this->getJson(...$item);
        }, $this, $this);

        $newUserId = $this->loginAndCreateASingleUser();

        $this->assertUnsupportedMethodResponse(
            TestApiEndpoints::USER_TOKENS($newUserId),
            [],
            $callable,
            TestData::GET,
            TestData::POST,
            TestData::PUT,
            TestData::DELETE,
        );
    }

    /** @test */
    public function getLicenseKeyShouldSucceed()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $response = $this
            ->getJson(TestApiEndpoints::USER_LICENSE_KEY($newUserId));

        $response
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([TestJsonKeys::ID => $newUserId]);

        $responseData = $response->decodeResponseJson();
        $this->assertIsString($responseData[TestJsonKeys::LICENSE_KEY_CAMEL]);
    }
    /** @test */
    public function getLicenseKeyWithBadUserIdShouldFailWithMessage()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $response = $this
            ->getJson(TestApiEndpoints::USER_LICENSE_KEY($newUserId));

        $response
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([TestJsonKeys::ID => $newUserId]);

        $responseData = $response->decodeResponseJson();
        $this->assertIsString($responseData[TestJsonKeys::LICENSE_KEY_CAMEL]);
    }
    /** @test */
    public function getLicenseKeyWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->getJson(TestApiEndpoints::USER_LICENSE_KEY(0));

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function getLicenseKeyWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }
    /**
     * @test
     * @depends getLicenseKeyShouldSucceed
     */
    public function resetLicenseKeyShouldSucceed()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $originalLicenseKey = $this
            ->getJson(TestApiEndpoints::USER_LICENSE_KEY($newUserId))
            ->decodeResponseJson()[TestJsonKeys::LICENSE_KEY_CAMEL];

        $deleteResponse = $this
            ->deleteJson(TestApiEndpoints::USER_LICENSE_KEY($newUserId));

        $deleteResponse->assertStatus(TestStatusCodes::OK);

        $deleteResponseData = $deleteResponse->decodeResponseJson();
        $this->assertEquals($deleteResponseData[TestJsonKeys::ID], $newUserId);
        $this->assertIsString($deleteResponseData[TestJsonKeys::LICENSE_KEY_CAMEL]);

        $updatedLicenseKey = $deleteResponseData[TestJsonKeys::LICENSE_KEY_CAMEL];

        $this->assertNotEquals($originalLicenseKey, $updatedLicenseKey);

        $this
            ->getJson(TestApiEndpoints::USER_LICENSE_KEY($newUserId))
            ->assertJson([
                TestJsonKeys::ID => $newUserId,
                TestJsonKeys::LICENSE_KEY_CAMEL => $updatedLicenseKey,
            ]);
    }
    /** @test */
    public function resetLicenseKeyWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->getJson(TestApiEndpoints::USER_LICENSE_KEY(0));

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function resetLicenseKeyWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }
    /** @test */
    public function useLicenseKeyRouteWithPostMethodShouldFailWithMessage()
    {
        $callable = \Closure::bind(function (...$item) {
            return $this->postJson(...$item);
        }, $this, $this);

        $newUserId = $this->loginAndCreateASingleUser();

        $this->assertUnsupportedMethodResponse(
            TestApiEndpoints::USER_LICENSE_KEY($newUserId),
            [],
            $callable,
            TestData::POST,
            TestData::GET,
            TestData::HEAD,
            TestData::DELETE,
        );
    }

    /**
     * @test
     * @depends getUserShouldReturnDetails
     */
    public function setIsDisabledShouldSucceed()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $putTrueResponse = $this
            ->putJson(
                TestApiEndpoints::USER_IS_DISABLED($newUserId),
                [TestJsonKeys::IS_DISABLED_CAMEL => true]
            );

        $putTrueResponse
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([
                TestJsonKeys::ID => $newUserId,
                TestJsonKeys::IS_DISABLED_CAMEL => true,
            ]);

        $this
            ->getJson(TestApiEndpoints::USER_BY_ID($newUserId))
            ->assertJsonFragment([TestJsonKeys::IS_DISABLED_CAMEL => true]);

        $putFalseResponse = $this
            ->putJson(
                TestApiEndpoints::USER_IS_DISABLED($newUserId),
                [TestJsonKeys::IS_DISABLED_CAMEL => false]
            );

        $putFalseResponse
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([
                TestJsonKeys::ID => $newUserId,
                TestJsonKeys::IS_DISABLED_CAMEL => false,
            ]);

        $this
            ->getJson(TestApiEndpoints::USER_BY_ID($newUserId))
            ->assertJsonFragment([TestJsonKeys::IS_DISABLED_CAMEL => false]);
    }
    /**
     * @test
     * @depends getAllUsersShouldReturnList
     */
    public function setIsDisabledOnAdminShouldFailWithMessage()
    {
        $adminUserId = $this->getAdminUserId();

        $this
            ->putJson(
                TestApiEndpoints::USER_IS_DISABLED($adminUserId),
                [TestJsonKeys::IS_DISABLED_CAMEL => true]
            )
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::ADMIN_USERS_MUST_BE_ENABLED,
            ]);
    }
    /** @test */
    public function setIsDisabledOnBadUserShouldFailWithMessage()
    {
        $this
            ->login()
            ->putJson(
                TestApiEndpoints::USER_IS_DISABLED(self::BAD_ID),
                [TestJsonKeys::IS_DISABLED_CAMEL => true]
            )
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::BAD_USER_ID
            ])
            ->assertJsonFragment([
                TestJsonKeys::USER_ID => [TestResponseMessages::BAD_USER_ID]
            ]);
    }
    /** @test */
    public function setIsDisabledWithGetMethodShouldFailWithMessage()
    {
        $callable = \Closure::bind(function (...$item) {
            return $this->getJson(...$item);
        }, $this, $this);

        $newUserId = $this->loginAndCreateASingleUser();

        $this->assertUnsupportedMethodResponse(
            TestApiEndpoints::USER_IS_DISABLED($newUserId),
            [],
            $callable,
            TestData::GET,
            TestData::PUT,
        );
    }
    /** @test */
    public function setIsDisabledWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->putJson(
                TestApiEndpoints::USER_IS_DISABLED(0),
                [TestJsonKeys::IS_DISABLED_CAMEL => true]
            );

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function setIsDisabledWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }

    /** @test */
    public function setIsAdminShouldSucceed()
    {
        $newUserId = $this->loginAndCreateASingleUser();

        $putTrueResponse = $this
            ->putJson(
                TestApiEndpoints::USER_IS_ADMIN($newUserId),
                [TestJsonKeys::IS_ADMIN_CAMEL => true]
            );

        $putTrueResponse
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([
                TestJsonKeys::ID => $newUserId,
                TestJsonKeys::IS_ADMIN_CAMEL => true,
            ]);

        $this
            ->getJson(TestApiEndpoints::USER_BY_ID($newUserId))
            ->assertJsonFragment([TestJsonKeys::IS_ADMIN_CAMEL => true]);

        $putFalseResponse = $this
            ->putJson(
                TestApiEndpoints::USER_IS_ADMIN($newUserId),
                [TestJsonKeys::IS_ADMIN_CAMEL => false]
            );

        $putFalseResponse
            ->assertStatus(TestStatusCodes::OK)
            ->assertJson([
                TestJsonKeys::ID => $newUserId,
                TestJsonKeys::IS_ADMIN_CAMEL => false,
            ]);

        $this
            ->getJson(TestApiEndpoints::USER_BY_ID($newUserId))
            ->assertJsonFragment([TestJsonKeys::IS_ADMIN_CAMEL => false]);
    }
    /** @test */
    public function setIsAdminOnLastAdminShouldFailWithMessage()
    {
        $adminUserId = $this->getAdminUserId();

        $this
            ->putJson(
                TestApiEndpoints::USER_IS_ADMIN($adminUserId),
                [TestJsonKeys::IS_ADMIN_CAMEL => false]
            )
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::AT_LEAST_ONE_ADMIN_NEEDED
            ]);
    }
    /** @test */
    public function setIsAdminOnBadUserShouldFailWithMessage()
    {
        $this
            ->login()
            ->putJson(
                TestApiEndpoints::USER_IS_ADMIN(self::BAD_ID),
                [TestJsonKeys::IS_ADMIN_CAMEL => true]
            )
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::BAD_USER_ID
            ])
            ->assertJsonFragment([
                TestJsonKeys::USER_ID => [TestResponseMessages::BAD_USER_ID]
            ]);
    }
    /** @test */
    public function setIsAdminWithGetMethodShouldFailWithMessage()
    {
        $callable = \Closure::bind(function (...$item) {
            return $this->getJson(...$item);
        }, $this, $this);

        $newUserId = $this->loginAndCreateASingleUser();

        $this->assertUnsupportedMethodResponse(
            TestApiEndpoints::USER_IS_ADMIN($newUserId),
            [],
            $callable,
            TestData::GET,
            TestData::PUT,
        );
    }
    /** @test */
    public function setIsAdminWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->putJson(
                TestApiEndpoints::USER_IS_DISABLED(0),
                [TestJsonKeys::IS_DISABLED_CAMEL => true]
            );

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function setIsAdminWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }

    /**
     * @test
     * @depends loginSeedUserShouldReturnTokenAndBasicUserDetails
     */
    public function setPasswordShouldSucceed()
    {
        $newUserId = $this->loginAndCreateASingleUser(
            TestData::USER1_EMAIL,
            TestData::USER1_PASSWORD
        );

        $this
            ->putJson(
                TestApiEndpoints::USER_PASSWORD($newUserId),
                [TestJsonKeys::PASSWORD => TestData::USER2_PASSWORD]
            )
            ->assertStatus(TestStatusCodes::NO_CONTENT);

        $this
            ->postJson(TestApiEndpoints::LOGIN, [
                TestJsonKeys::EMAIL => TestData::USER1_EMAIL,
                TestJsonKeys::PASSWORD => TestData::USER1_PASSWORD,
            ])
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::WRONG_CREDENTIALS
            ]);

        $this
            ->postJson(TestApiEndpoints::LOGIN, [
                TestJsonKeys::EMAIL => TestData::USER1_EMAIL,
                TestJsonKeys::PASSWORD => TestData::USER2_PASSWORD,
            ])
            ->assertStatus(TestStatusCodes::OK);
    }
    /** @test */
    public function setPasswordThatIsShortShouldFailWithMessage()
    {
        $newUserId = $this->loginAndCreateASingleUser(
            TestData::USER1_EMAIL,
            TestData::USER1_PASSWORD
        );

        $this
            ->putJson(
                TestApiEndpoints::USER_PASSWORD($newUserId),
                [TestJsonKeys::PASSWORD => TestData::SHORT_PASSWORD]
            )
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::SHORT_PASSWORD,
            ])
            ->assertJsonFragment([
                TestJSONKeys::PASSWORD => [TestResponseMessages::SHORT_PASSWORD],
            ]);
    }
    /** @test */
    public function setPasswordOnBadUserShouldFailWithMessage()
    {
        $this
            ->login()
            ->putJson(
                TestApiEndpoints::USER_PASSWORD(self::BAD_ID),
                [TestJsonKeys::PASSWORD => TestData::USER2_PASSWORD]
            )
            ->assertStatus(TestStatusCodes::UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::BAD_USER_ID,
            ])
            ->assertJsonFragment([
                TestJsonKeys::USER_ID => [TestResponseMessages::BAD_USER_ID],
            ]);
    }
    /** @test */
    public function setPasswordWithGetMethodShouldFailWithMessage()
    {
        $callable = \Closure::bind(function (...$item) {
            return $this->getJson(...$item);
        }, $this, $this);

        $this->assertUnsupportedMethodResponse(
            TestApiEndpoints::USER_PASSWORD(0),
            [TestJsonKeys::PASSWORD => TestData::USER2_PASSWORD],
            $callable,
            TestData::GET,
            TestData::PUT
        );
    }
    /** @test */
    public function setPasswordWithoutCredentialsShouldFailWithMessage()
    {
        $response = $this
            ->putJson(
                TestApiEndpoints::USER_PASSWORD(0),
                [TestJsonKeys::PASSWORD => TestData::USER1_PASSWORD]
            );

        $response
            ->assertStatus(TestStatusCodes::UNAUTHORIZED)
            ->assertJsonFragment([
                TestJsonKeys::MESSAGE => TestResponseMessages::UNAUTHORIZED,
            ]);
    }
    // /** @test */
    // public function setPasswordWithNonAdminCredentialsShouldFailWithMessage()
    // {
    // TODO
    // }
}
