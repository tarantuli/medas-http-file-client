# medas-http-file-client

Part of the [Medas framework](https://github.com/tarantuli/medas-core).

## Description

A `DataStorage` implementation that reads and writes files over HTTP rather than the local filesystem. Useful when file storage is managed by a separate service (e.g., a dedicated media server or a remote `medas-file-system` endpoint) and PHP needs to interact with it via HTTP.

`Client` is a value object holding the base URL of the remote file server and an optional `Authorization` header value. `ClientManager` holds a registry of named clients keyed by URL and tracks a default. `Controller` implements `DataStorage` and delegates every operation to `medas-http-client`'s `RequestController`, automatically attaching the correct `Authorization` header and falling back to the default client when none is explicitly passed.

**Operations supported:**

| Method                                       | HTTP                           | Description                                                               |
|----------------------------------------------|--------------------------------|---------------------------------------------------------------------------|
| `exists($path)`                              | GET `?return=null`             | Returns `true` if the server responds with 204                            |
| `content($path)`                             | GET                            | Returns the raw response body, or `null` on 4xx                           |
| `size($path)`                                | GET `?return=size`             | Returns the size string, or `null` on 4xx                                 |
| `modificationTime($path)`                    | GET `?return=modificationTime` | Returns a `\DateTime` from a Unix timestamp, or `null`                    |
| `store($path, $content, $modificationTime?)` | POST                           | Sends content + optional modification time as JSON; returns `true` on 201 |
| `delete($path)`                              | DELETE                         | Returns `true` on 200, `false` on 4xx                                     |

## Usage

### Package developer context

Register the package and configure at least one client:

```php
use Medas\HttpFileClient\HttpFileClientPackage;

HttpFileClientPackage::instance();
```

**Registering a client:**

```php
use Medas\HttpFileClient\{Client, ClientManager};
use Medas\Core\Attributes\Service;

#[Service]
readonly class AppBootstrap
{
    public function __construct(
        private ClientManager $clientManager,
    ) {}

    public function boot(): void
    {
        // Register a client with Bearer token auth; mark it as the default
        $this->clientManager->register(
            new Client(
                url: 'https://files.example.com/api',
                authorizationHeader: 'Bearer ' . $this->resolveToken(),
            ),
            asDefault: true,
        );

        // Register a second client for a different storage server (no auth)
        $this->clientManager->register(
            new Client(
                url: 'https://archive.example.com/api',
                authorizationHeader: null,
            ),
        );
    }
}
```

The first registered client is always set as the default, regardless of `$asDefault`.

**Reading and writing files via the default client:**

```php
use Medas\HttpFileClient\Controller;
use Medas\Core\Attributes\Service;

#[Service]
readonly class ReportStorage
{
    public function __construct(
        private Controller $storage,
    ) {}

    public function save(string $filename, string $content): bool
    {
        return $this->storage->store(
            path: 'reports/' . $filename,
            content: $content,
            modificationTime: new \DateTime(),
        );
    }

    public function load(string $filename): string|null
    {
        return $this->storage->content('reports/' . $filename);
    }

    public function remove(string $filename): bool
    {
        return $this->storage->delete('reports/' . $filename);
    }
}
```

**Checking existence and metadata:**

```php
if ($this->storage->exists('reports/q1-2026.csv')) {
    $size    = $this->storage->size('reports/q1-2026.csv');
    $modTime = $this->storage->modificationTime('reports/q1-2026.csv');

    echo "Size: $size, last modified: " . $modTime?->format('Y-m-d H:i:s');
}
```

**Using a non-default client explicitly:**

```php
use Medas\HttpFileClient\{Client, ClientManager, Controller};

// Look up the archive client by its base URL
$archiveClient = $this->clientManager->find('https://archive.example.com/api');

// Pass it directly to any Controller method
$content = $this->storage->content('backups/2025-12.tar.gz', $archiveClient);
```

**Using `Controller` as a `DataStorage`:**

Because `Controller` implements `DataStorage`, it can be injected wherever the interface is expected:

```php
use Medas\Core\Interfaces\DataStorage;
use Medas\Core\Attributes\Service;

#[Service]
readonly class FileProcessor
{
    public function __construct(
        // Resolved to Controller automatically by the DI container
        private DataStorage $storage,
    ) {}
}
```

### Backend user context

The remote server must expose an HTTP API that the `Controller` can talk to. The expected contract per operation is:

| Operation             | Method | Path                              | Response                                                 |
|-----------------------|--------|-----------------------------------|----------------------------------------------------------|
| Check existence       | GET    | `/{path}?return=null`             | 204 if found, 4xx if not                                 |
| Get content           | GET    | `/{path}`                         | 200 with body content                                    |
| Get size              | GET    | `/{path}?return=size`             | 200 with size in body                                    |
| Get modification time | GET    | `/{path}?return=modificationTime` | 200 with Unix timestamp in body                          |
| Store                 | POST   | `/{path}`                         | 201 on success; JSON body: `{content, modificationTime}` |
| Delete                | DELETE | `/{path}`                         | 200 on success, 4xx otherwise                            |

All 4xx responses are caught internally — they return `false` or `null` rather than propagating as exceptions. Network-level failures (`CurlError`) and 5xx responses are not caught and will propagate.
