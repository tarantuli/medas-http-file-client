<?php

declare(strict_types=1);

namespace Medas\HttpFileClient;

use Medas\Core\{Attributes\Service, Interfaces\DataStorage};
use Medas\HttpClient\{Body, Exceptions\BadRequest, Request, RequestController, ResponseCodes};

#[Service]
readonly class Controller implements DataStorage
{
    public function __construct(
        private ClientManager     $clientManager,
        private RequestController $requestController,
    )
    {
    }

    public function exists(string $path, Client|null $client = null): bool
    {
        $request = $this->createRequest($client, $path, ['return' => 'null']);

        try {
            return $this->requestController->execute($request)->code === ResponseCodes::NO_CONTENT;
        }
        catch (BadRequest) {
            return false;
        }
    }

    public function delete(string $path, Client|null $client = null): bool
    {
        $request = $this->createRequest($client, $path);

        $request->method = 'DELETE';

        try {
            return $this->requestController->execute($request)->code === ResponseCodes::OK;
        }
        catch (BadRequest) {
            return false;
        }
    }

    public function content(string $path, Client|null $client = null): string|null
    {
        $request = $this->createRequest($client, $path);

        return $this->requestController->execute($request)->body;
    }

    public function size(string $path, Client|null $client = null): string|null
    {
        $request = $this->createRequest($client, $path, ['return' => 'size']);

        return $this->requestController->execute($request)->body;
    }

    public function modificationTime(string $path, Client|null $client = null): \DateTime|null
    {
        $request = $this->createRequest($client, $path, ['return' => 'modificationTime']);
        $timestamp = $this->requestController->execute($request)->body;

        return $timestamp === null ? null : new \DateTime('@' . $timestamp);
    }

    public function store(
        string         $path,
        string         $content,
        \DateTime|null $modificationTime = null,
        Client|null    $client = null,
    ): bool
    {
        $request = $this->createRequest($client, $path);

        $request->method = 'POST';

        $request->body = new Body([
            'content' => $content,
            'modificationTime' => $modificationTime,
        ], 'application/json');

        return $this->requestController->execute($request)->code === ResponseCodes::CREATED;
    }

    private function createRequest(Client|null $client, string $path, array $queryArguments = []): Request
    {
        $client ??= $this->clientManager->default();
        $request = new Request($client->url . '/' . $path);

        if ($client->authorizationHeader !== null) {
            $request->headers['Authorization'] = $client->authorizationHeader;
        }

        $request->queryArguments = $queryArguments;

        return $request;
    }
}
