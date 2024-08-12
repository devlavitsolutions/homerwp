<?php

namespace App\Services;

use App\Constants\Defaults;
use App\Http\Constants\Messages;
use App\Http\Constants\Routes;
use App\Http\Contracts\IContentInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Response;

class OpenAIService implements IContentInterface
{
    private $apiKey;
    private $assistantId;
    private $client;
    private $organizationId;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.openai.api_key');
        $this->assistantId = config('services.openai.assistant_id');
        $this->organizationId = config('services.openai.organization_id');
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

        while ('queued' === $run['status'] || 'in_progress' === $run['status']) {
            if ((time() - $startTime) > Defaults::MAX_OPENAI_WAIT_TIME) {
                return $this->handleError();
            }

            sleep(2);

            $run = $this->getRun($thread['id'], $run['id']);
            if ($this->isError($run)) {
                return $this->handleError();
            }
        }

        if ('completed' === $run['status']) {
            $response = $this->getThreadResponse($thread['id']);
            if ($this->isError($response)) {
                return $this->handleError();
            }

            $markdownContent = $response['data'][0]['content'][0]['text']['value'];

            return $this->extractAndConvertContent($markdownContent, $message);
        }

        return $this->handleError();
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    private function convertMarkdownToHtml($markdown)
    {
        $parsedown = new \Parsedown();
        $html = $parsedown->text($markdown);

        return str_replace("\n", '', $html);
    }

    private function createMessage($threadId, $messageContent)
    {
        try {
            $response = $this->client->post(
                Routes::OPENAI_BASE_URL.Routes::THREADS.'/'.$threadId.Routes::MESSAGES,
                [
                    'headers' => $this->getHeaders(),
                    'json' => [
                        'role' => 'user',
                        'content' => $messageContent,
                    ],
                ]
            );
            if (Response::HTTP_OK === $response->getStatusCode()) {
                return json_decode($response->getBody(), true);
            }

            return $this->handleError($response->getStatusCode());
        } catch (GuzzleException $e) {
            return $this->handleError();
        }
    }

    private function createThread()
    {
        try {
            $response = $this->client->post(
                Routes::OPENAI_BASE_URL.Routes::THREADS,
                [
                    'headers' => $this->getHeaders(),
                    'json' => new \stdClass(),
                ]
            );

            if (Response::HTTP_OK === $response->getStatusCode()) {
                return json_decode($response->getBody(), true);
            }

            return $this->handleError($response->getStatusCode());
        } catch (GuzzleException $e) {
            return $this->handleError();
        }
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
        $firstKeyword = ($keywords[0] ?? '');
        $secondKeyword = ($keywords[1] ?? '');

        // Find and replace the first occurrence of the message string in the articleHtmlContent
        $messageFound = false;
        $firstReplacementDone = false;
        if (stripos($articleHtmlContent, $message) !== false) {
            $articleHtmlContent = preg_replace_callback(
                '/\b'.preg_quote($message, '/').'\b/i',
                function ($matches) use ($message, &$firstReplacementDone) {
                    if (!$firstReplacementDone) {
                        $firstReplacementDone = true;
                        return '<a href="https://en.wikipedia.org/w/index.php?search='.urlencode($message).'">'.$matches[0].'</a>';
                    }
                    return $matches[0];
                },
                $articleHtmlContent,
                1,
                $messageFound
            );
        }

        // If message string is not found, fall back to the first keyword
        if (!$messageFound && $firstKeyword) {
            $articleHtmlContent = preg_replace_callback(
                '/\b'.preg_quote($firstKeyword, '/').'\b/i',
                function ($matches) use ($firstKeyword) {
                    return '<a href="https://en.wikipedia.org/w/index.php?search='.urlencode($firstKeyword).'">'.$matches[0].'</a>';
                },
                $articleHtmlContent,
                1
            );
        }

        // Find and replace the second occurrence of the message string in the articleHtmlContent
        $secondMessageFound = false;
        $secondReplacementDone = false;
        if (stripos($articleHtmlContent, $message) !== false) {
            $articleHtmlContent = preg_replace_callback(
                '/\b'.preg_quote($message, '/').'\b/i',
                function ($matches) use (&$secondReplacementDone) {
                    if (!$secondReplacementDone) {
                        $secondReplacementDone = true;
                        return '<a href="/">'.$matches[0].'</a>';
                    }
                    return $matches[0];
                },
                $articleHtmlContent,
                1,
                $secondMessageFound
            );
        }

        // If another message string is not found, fall back to the second keyword
        if (!$secondMessageFound && $secondKeyword) {
            $articleHtmlContent = preg_replace_callback(
                '/\b'.preg_quote($secondKeyword, '/').'\b/i',
                function ($matches) {
                    return '<a href="/">'.$matches[0].'</a>';
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
            'focusKeywords' => strtolower(trim($message)),
        ];
    }

    private function getHeaders()
    {
        return [
            'Authorization' => 'Bearer '.$this->apiKey,
            'OpenAI-Beta' => 'assistants=v2',
            'OpenAI-Organization' => $this->organizationId,
        ];
    }

    private function getRun($threadId, $runId)
    {
        try {
            $response = $this->client->get(
                Routes::OPENAI_BASE_URL.Routes::THREADS.'/'.$threadId.Routes::RUNS.'/'.$runId,
                [
                    'headers' => $this->getHeaders(),
                ]
            );

            if (Response::HTTP_OK === $response->getStatusCode()) {
                return json_decode($response->getBody(), true);
            }

            return $this->handleError($response->getStatusCode());
        } catch (GuzzleException $e) {
            return $this->handleError();
        }
    }

    private function getThreadResponse($threadId)
    {
        try {
            $response = $this->client->get(
                Routes::OPENAI_BASE_URL.Routes::THREADS.'/'.$threadId.Routes::MESSAGES,
                [
                    'headers' => $this->getHeaders(),
                ]
            );
            if (Response::HTTP_OK === $response->getStatusCode()) {
                return json_decode($response->getBody(), true);
            }

            return $this->handleError($response->getStatusCode());
        } catch (GuzzleException $e) {
            return $this->handleError();
        }
    }

    private function handleError($statusCode = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        abort($statusCode, Messages::OPENAI_ERROR_MESSAGE);
    }

    private function isError($response)
    {
        return isset($response['status_code']) && Response::HTTP_INTERNAL_SERVER_ERROR === $response['status_code'];
    }

    private function runAssistant($threadId)
    {
        try {
            $response = $this->client->post(
                Routes::OPENAI_BASE_URL.Routes::THREADS.'/'.$threadId.Routes::RUNS,
                [
                    'headers' => $this->getHeaders(),
                    'json' => [
                        'assistant_id' => $this->assistantId,
                    ],
                ]
            );
            if (Response::HTTP_OK === $response->getStatusCode()) {
                return json_decode($response->getBody(), true);
            }

            return $this->handleError($response->getStatusCode());
        } catch (GuzzleException $e) {
            return $this->handleError();
        }
    }
}
