<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Services\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class ContentControllerTest extends TestCase
{
    use WithoutMiddleware; 

    public function testGetContentResponse()
    {
        $mockService = \Mockery::mock(OpenAIService::class);
        $mockService->shouldReceive('getAssistantResponse') 
                    ->once()
                    ->andReturn(['data' => 'Mocked response']);

        $this->app->instance(OpenAIService::class, $mockService);

        $requestData = [
            'keywords' => 'wordpress speed hosting',
            'license_key' => 'your license key 34',
            'website' => 'https://www.example.com'
        ];

        $response = $this->json('POST', '/api/content', $requestData);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => 'Mocked response'
                 ]);
    }
}