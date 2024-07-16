<?php
namespace App\Services;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class OpenAIService
{
    private const ERROR_MESSAGE = 'Sorry, we are unable to generate content at the moment. Please try again later.';
    private const BASE_URL = 'https://api.openai.com/v1';

    private $client;
    private $apiKey;
    private $assistantId;
    private $organizationId;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.openai.api_key');
        $this->assistantId = config('services.openai.assistant_id');
        $this->organizationId = config('services.openai.organization_id');    
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    private function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'OpenAI-Beta' => 'assistants=v2',
            'OpenAI-Organization' => $this->organizationId
        ];
    }

    private function handleError($statusCode)
    {
        return [
            'message' => self::ERROR_MESSAGE,
            'status_code' => $statusCode
        ];
    }

    private function createThread()
    {
        try {
            $response = $this->client->post(self::BASE_URL . '/threads', [
                'headers' => $this->getHeaders(),
                'json' => new \stdClass()
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                return $this->handleError($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $this->handleError(500);
        }
    }

    private function createMessage($threadId, $messageContent)
    {
        try {
            $response = $this->client->post(self::BASE_URL . "/threads/$threadId/messages", [
                'headers' => $this->getHeaders(),
                'json' => [
                    'role' => 'user',
                    'content' => $messageContent
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                return $this->handleError($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $this->handleError(500);
        }
    }

    private function runAssistant($threadId)
    {
        try {
            $response = $this->client->post(self::BASE_URL . "/threads/$threadId/runs", [
                'headers' => $this->getHeaders(),
                'json' => [
                    'assistant_id' => $this->assistantId
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                return $this->handleError($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $this->handleError(500);
        }
    }

    public function getRun($threadId, $runId)
    {
        try {
            $response = $this->client->get(self::BASE_URL . "/threads/$threadId/runs/$runId", [
                'headers' => $this->getHeaders()
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                return $this->handleError($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $this->handleError(500);
        }
    }

    private function getThreadResponse($threadId)
    {
        try {
            $response = $this->client->get(self::BASE_URL . "/threads/$threadId/messages", [
                'headers' => $this->getHeaders()
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                return $this->handleError($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $this->handleError(500);
        }
    }

    public function getAssistantResponse($message)
    {
        $thread = $this->createThread();
        if (isset($thread['status_code']) && $thread['status_code'] >= 400) {
            return $this->handleError(500);
        }
        $messageResponse = $this->createMessage($thread['id'], $message);
        if (isset($messageResponse['status_code']) && $messageResponse['status_code'] >= 400) {
            return $this->handleError(500);
        }
        $run = $this->runAssistant($thread['id']);
        if (isset($run['status_code']) && $run['status_code'] >= 400) {
            return $this->handleError(500);
        }
        while ($run['status'] === 'queued' || $run['status'] === 'in_progress') {
            sleep(2);
            $run = $this->getRun($thread['id'], $run['id']);
            if (isset($run['status_code']) && $run['status_code'] >= 400) {
                return $this->handleError(500);
            }
        }
        if ($run['status'] === 'completed') {
            $response = $this->getThreadResponse($thread['id']);
            if (isset($response['status_code']) && $response['status_code'] >= 400) {
                return $this->handleError(500);
            }
            return ['data' => $response['data'][0]['content'][0]['text']['value']];
        }
        return $this->handleError(500);
    }
}