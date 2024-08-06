<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Constants\Routes;
use App\Constants\Messages;
use App\Constants\Defaults;
use Symfony\Component\HttpFoundation\Response;
use Parsedown;
use App\Http\Contracts\IContentInterface;

class OpenAIService implements IContentInterface
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

    private function handleError($statusCode = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        abort($statusCode, Messages::OPENAI_ERROR_MESSAGE);
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

    private function convertMarkdownToHtml($markdown)
    {
        $parsedown = new \Parsedown();
        $html = $parsedown->text($markdown);
        $html = str_replace("\n", "", $html);
        return $html;
    }

    private function extractAndConvertContent($markdownContent, $message)
    {
        preg_match('/Title:(.+)/', $markdownContent, $titleMatches);
        preg_match('/Meta Description:(.+)/', $markdownContent, $metaDescriptionMatches);
        preg_match('/Slug:(.+)/', $markdownContent, $slugMatches);
        preg_match('/Article:(.+)/s', $markdownContent, $articleMatches);

        $title = preg_replace('/^\*\*\s?/', '', $titleMatches[1] ?? '');
        $metaDescription = preg_replace('/^\*\*\s?/', '', $metaDescriptionMatches[1] ?? '');
        $slug = preg_replace('/^\*\*\s?/', '', $slugMatches[1] ?? '');
        $article = preg_replace('/^\*\*\s?/', '', $articleMatches[1] ?? '');

        $articleHtmlContent = $this->convertMarkdownToHtml($article);

        $keywords = explode(' ', $message);
        $firstKeyword = $keywords[0] ?? '';
        $secondKeyword = $keywords[1] ?? '';

        if ($firstKeyword) {
            $articleHtmlContent = preg_replace_callback(
                '/\b' . preg_quote($firstKeyword, '/') . '\b/i',
                function ($matches) use ($firstKeyword) {
                    return '<a href="https://en.wikipedia.org/w/index.php?search=' . urlencode($firstKeyword) . '">' . $matches[0] . '</a>';
                },
                $articleHtmlContent,
                1
            );
        }

        if ($secondKeyword) {
            $articleHtmlContent = preg_replace_callback(
                '/\b' . preg_quote($secondKeyword, '/') . '\b/i',
                function ($matches) use ($secondKeyword) {
                    return '<a href="/">' . $matches[0] . '</a>';
                },
                $articleHtmlContent,
                1
            );
        }

        return [
            'title' => $title,
            'metaDescription' => $metaDescription,
            'slug' => $slug,
            'article' => $articleHtmlContent,
            'focusKeywords' => strtolower(trim($message))
        ];
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

        $startTime = time();

        while ($run['status'] === 'queued' || $run['status'] === 'in_progress') {
            if ((time() - $startTime) > Defaults::MAX_OPENAI_WAIT_TIME) {
                return $this->handleError();
            }

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

            $markdownContent = $response['data'][0]['content'][0]['text']['value'];
            return $this->extractAndConvertContent($markdownContent, $message);
        }
        return $this->handleError();
    }
}
