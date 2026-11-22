<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Exceptions\Handler;
use Core\Http\Pipeline;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Router;
use Core\Middleware\CsrfMiddleware;
use Core\Middleware\SecurityHeadersMiddleware;
use Tests\TestBase;

class FullRequestLifecycleTest extends TestBase
{
    protected Router $router;
    protected Handler $handler;
    private string $tempLogDir;
    private string $tempLogFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = $this->app->make(Router::class);
        $this->handler = $this->app->make(Handler::class);

        $this->tempLogDir = sys_get_temp_dir() . '/flipa_lifecycle_logs_' . uniqid();
        $this->tempLogFile = 'flipa.log';
        $logger = new \Core\Log\Logger($this->tempLogDir, $this->tempLogFile);
        $this->app->singleton(\Core\Log\Logger::class, fn() => $logger);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['_csrf_token']);

        $logPath = $this->tempLogDir . DIRECTORY_SEPARATOR . $this->tempLogFile;
        if (file_exists($logPath)) {
            unlink($logPath);
        }
        if (is_dir($this->tempLogDir)) {
            rmdir($this->tempLogDir);
        }

        parent::tearDown();
    }

    private function createRequest(
        string $method = 'GET',
        string $uri = '/',
        array $post = [],
        array $query = [],
        array $headers = []
    ): Request {
        $server = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'    => $uri,
            'REMOTE_ADDR'    => '127.0.0.1',
            'HTTP_HOST'      => 'localhost',
        ];

        foreach ($headers as $key => $value) {
            $normalizedKey = str_replace('-', '_', strtoupper($key));
            if ($normalizedKey === 'CONTENT_TYPE') {
                $server['CONTENT_TYPE'] = $value;
            } else {
                $server["HTTP_$normalizedKey"] = $value;
            }
        }

        return new Request(
            method: $method,
            uri: $uri,
            ip: '127.0.0.1',
            host: 'localhost',
            post: $post,
            query: $query,
            cookies: [],
            headers: $server,
            files: []
        );
    }

    private function getResponseBody(Response $response): string
    {
        return (fn() => (string) $this->body)->call($response);
    }

    private function handleRequest(Request $request, array $middlewares = [SecurityHeadersMiddleware::class]): Response
    {
        $this->app->singleton(Request::class, fn() => $request);

        try {
            return (new Pipeline($this->app))
                ->send($request)
                ->through($middlewares)
                ->then(function (Request $req) {
                    $result = $this->router->dispatch($req);
                    return $result instanceof Response ? $result : new Response(body: $result);
                });
        } catch (\Throwable $e) {
            return $this->handler->render($request, $e);
        }
    }

    public function test_get_request_traverses_pipeline_and_returns_successful_response(): void
    {
        $request = $this->createRequest(method: 'GET', uri: '/');

        $response = $this->handleRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Flipa', $this->getResponseBody($response));
        $this->assertArrayHasKey('X-Content-Type-Options', $response->getHeaders());
        $this->assertSame('nosniff', $response->getHeaders()['X-Content-Type-Options']);
    }

    public function test_post_request_with_valid_csrf_token_reaches_controller_action(): void
    {
        $this->router->post('/lifecycle/action', fn() => 'Action Executed Successfully');
        $_SESSION['_csrf_token'] = 'valid_token_secret_123';

        $request = $this->createRequest(
            method: 'POST',
            uri: '/lifecycle/action',
            post: ['_csrf_token' => 'valid_token_secret_123']
        );

        $response = $this->handleRequest($request, [SecurityHeadersMiddleware::class, CsrfMiddleware::class]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Action Executed Successfully', $this->getResponseBody($response));
    }

    public function test_post_request_without_csrf_token_is_blocked_by_middleware_and_handled(): void
    {
        $this->router->post('/lifecycle/protected', fn() => 'Should Not Run');
        $_SESSION['_csrf_token'] = 'valid_token_secret_123';

        $request = $this->createRequest(method: 'POST', uri: '/lifecycle/protected', post: []);

        $response = $this->handleRequest($request, [SecurityHeadersMiddleware::class, CsrfMiddleware::class]);

        $this->assertSame(419, $response->getStatusCode());
        $this->assertStringContainsString('<h1>419</h1>', $this->getResponseBody($response));
    }

    public function test_request_to_nonexistent_route_produces_404_response_through_handler(): void
    {
        $request = $this->createRequest(method: 'GET', uri: '/nonexistent/page');

        $response = $this->handleRequest($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('<h1>404</h1>', $this->getResponseBody($response));
    }

    public function test_request_with_wrong_method_produces_405_response_with_allow_header(): void
    {
        $this->router->get('/lifecycle/only-get', fn() => 'get response');

        $request = $this->createRequest(method: 'POST', uri: '/lifecycle/only-get');

        $response = $this->handleRequest($request);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertArrayHasKey('Allow', $response->getHeaders());
        $this->assertSame('GET', $response->getHeaders()['Allow']);
        $this->assertStringContainsString('<h1>405</h1>', $this->getResponseBody($response));
    }

    public function test_controller_with_form_request_validation_failure_produces_422_response(): void
    {
        $request = $this->createRequest(
            method: 'POST',
            uri: '/dashboard',
            post: ['name' => 'A']
        );

        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('<h1>422</h1>', $this->getResponseBody($response));
    }

    public function test_full_lifecycle_with_json_request_returns_json_response_on_error(): void
    {
        $request = $this->createRequest(
            method: 'GET',
            uri: '/api/unknown',
            headers: ['Accept' => 'application/json']
        );

        $response = $this->handleRequest($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);

        $payload = json_decode($this->getResponseBody($response), true);
        $this->assertSame(404, $payload['code']);
        $this->assertArrayHasKey('error', $payload);
    }
}
