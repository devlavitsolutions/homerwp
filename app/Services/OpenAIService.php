<?php
namespace App\Services;

use GuzzleHttp\Client;

class OpenAIService
{
	private $client;
	private $apiKey;
	private $assistantId;
	private $organizationId;

	public function __construct()
	{
		$this->client = new Client();
		$this->apiKey = env('OPENAI_API_KEY');
		$this->assistantId = env('CHATGPT_ASSISTANT_ID');
		$this->organizationId = env('OPENAI_ORGANIZATION_ID');	
	}

	private function getHeaders()
	{
		return [
			'Authorization' => 'Bearer ' . $this->apiKey,
			'OpenAI-Beta' => 'assistants=v2',
			'OpenAI-Organization' => $this->organizationId
		];
	}

	private function createThread()
    {
        try {
            $response = $this->client->post('https://api.openai.com/v1/threads', [
                'headers' => $this->getHeaders(),
                'json' => new \stdClass()
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                return ['error' => 'Non-successful response code: ' . $response->getStatusCode()];
            }
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function createMessage($threadId, $messageContent)
    {
        try {
            $response = $this->client->post("https://api.openai.com/v1/threads/$threadId/messages", [
                'headers' => $this->getHeaders(),
                'json' => [
                    'role' => 'user',
                    'content' => $messageContent
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                return ['error' => 'Non-successful response code: ' . $response->getStatusCode()];
            }
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function runAssistant($threadId)
    {
        try {
            $response = $this->client->post("https://api.openai.com/v1/threads/$threadId/runs", [
                'headers' => $this->getHeaders(),
                'json' => [
                    'assistant_id' => $this->assistantId
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                return ['error' => 'Non-successful response code: ' . $response->getStatusCode()];
            }
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getRun($threadId, $runId)
    {
        try {
            $response = $this->client->get("https://api.openai.com/v1/threads/$threadId/runs/$runId", [
                'headers' => $this->getHeaders()
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                return ['error' => 'Non-successful response code: ' . $response->getStatusCode()];
            }
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getThreadResponse($threadId)
    {
        try {
            $response = $this->client->get("https://api.openai.com/v1/threads/$threadId/messages", [
                'headers' => $this->getHeaders()
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                return ['error' => 'Non-successful response code: ' . $response->getStatusCode()];
            }
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getAssistantResponse($message)
    {
        $thread = $this->createThread();
        if (isset($thread['error'])) {
            return $thread;
        }

        $messageResponse = $this->createMessage($thread['id'], $message);
        if (isset($messageResponse['error'])) {
            return $messageResponse;
        }

        $run = $this->runAssistant($thread['id']);
        if (isset($run['error'])) {
            return $run;
        }

        while ($run['status'] === 'queued' || $run['status'] === 'in_progress') {
            sleep(2);
            $run = $this->getRun($thread['id'], $run['id']);
            if (isset($run['error'])) {
                return $run;
            }
        }

        if ($run['status'] === 'completed') {
            $response = $this->getThreadResponse($thread['id']);
            if (isset($response['error'])) {
                return $response;
            }
            return ['data' => $response['data'][0]['content'][0]['text']['value']];
        }

        return ['error' => 'Failed to generate response'];
    }
}