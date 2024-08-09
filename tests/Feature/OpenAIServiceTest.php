<?php

namespace Tests\Feature;

use App\Services\OpenAIService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class OpenAIServiceTest extends TestCase
{
    public function testGetAssistantResponse()
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'thread_id'])),
            new Response(200, [], json_encode(['id' => 'message_id'])),
            new Response(200, [], json_encode(['status' => 'in_progress', 'id' => 'run_id'])),
            new Response(200, [], json_encode(['status' => 'in_progress', 'id' => 'run_id'])),
            new Response(200, [], json_encode(['status' => 'completed', 'id' => 'run_id'])),
            new Response(200, [], json_encode(['data' => [['content' => [['text' => ['value' => 'Mocked response']]]]]])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $openAIService = new OpenAIService();
        $openAIService->setClient($client);
        $response = $openAIService->getAssistantResponse('Test message');

        $this->assertEquals('Mocked response', $response['data']);
    }
}
