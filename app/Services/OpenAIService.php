<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Http\Constants\Routes;
use App\Http\Constants\Messages;
use Symfony\Component\HttpFoundation\Response;

class OpenAIService
{
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

    private function isError($response)
    {
        return isset($response['status_code']) && $response['status_code'] == Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function handleError()
    {
        abort(Response::HTTP_INTERNAL_SERVER_ERROR, Messages::OPENAI_ERROR_MESSAGE);
    }

    private function createThread()
    {
        try {
            $response = $this->client->post(Routes::OPENAI_BASE_URL . Routes::THREADS, [
                'headers' => $this->getHeaders(),
                'json' => new \stdClass()
            ]);
            if ($response->getStatusCode() == Response::HTTP_OK) {
                return json_decode($response->getBody(), true);
            } else {
                return $this->handleError($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $this->handleError();
        }
    }

    private function createMessage($threadId, $messageContent)
    {
        try {
            $response = $this->client->post(Routes::OPENAI_BASE_URL . Routes::THREADS . "/$threadId" . Routes::MESSAGES, [
                'headers' => $this->getHeaders(),
                'json' => [
                    'role' => 'user',
                    'content' => $messageContent
                ]
            ]);
            if ($response->getStatusCode() == Response::HTTP_OK) {
                return json_decode($response->getBody(), true);
            } else {
                return $this->handleError($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $this->handleError();
        }
    }

    private function runAssistant($threadId)
    {
        try {
            $response = $this->client->post(Routes::OPENAI_BASE_URL . Routes::THREADS . "/$threadId" . Routes::RUNS, [
                'headers' => $this->getHeaders(),
                'json' => [
                    'assistant_id' => $this->assistantId
                ]
            ]);
            if ($response->getStatusCode() == Response::HTTP_OK) {
                return json_decode($response->getBody(), true);
            } else {
                return $this->handleError($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $this->handleError();
        }
    }

    private function getRun($threadId, $runId)
    {
        try {
            $response = $this->client->get(Routes::OPENAI_BASE_URL . Routes::THREADS . "/$threadId" . Routes::RUNS . "/$runId", [
                'headers' => $this->getHeaders()
            ]);
            if ($response->getStatusCode() == Response::HTTP_OK) {
                return json_decode($response->getBody(), true);
            } else {
                return $this->handleError($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $this->handleError();
        }
    }

    private function getThreadResponse($threadId)
    {
        try {
            $response = $this->client->get(Routes::OPENAI_BASE_URL . Routes::THREADS . "/$threadId" . Routes::MESSAGES, [
                'headers' => $this->getHeaders()
            ]);
            if ($response->getStatusCode() == Response::HTTP_OK) {
                return json_decode($response->getBody(), true);
            } else {
                return $this->handleError($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $this->handleError();
        }
    }

    public function getAssistantResponse($message)
    {
        $thread = $this->createThread();
        if ($this->isError($thread)) {
            return $this->handleError();
        }
        $messageResponse = $this->createMessage($thread['id'], $message);
        if ($this->isError($messageResponse)) {
            return $this->handleError();
        }
        $run = $this->runAssistant($thread['id']);
        if ($this->isError($run)) {
            return $this->handleError();
        }
        while ($run['status'] === 'queued' || $run['status'] === 'in_progress') {
            sleep(2);
            $run = $this->getRun($thread['id'], $run['id']);
            if ($this->isError($run)) {
                return $this->handleError();
            }
        }
        if ($run['status'] === 'completed') {
            $response = $this->getThreadResponse($thread['id']);
            if ($this->isError($response)) {
                return $this->handleError();
            }
            return ['data' => $response['data'][0]['content'][0]['text']['value']];
        }
        return $this->handleError();
    }
}
