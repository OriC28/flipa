<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Exceptions\MiddlewareException;
use Core\Http\Pipeline;
use Core\Http\Request;
use Core\Http\Response;
use Tests\Fixtures\Http\ExecutionLog;
use Tests\Fixtures\Http\InvalidMiddleware;
use Tests\Fixtures\Http\MiddlewareA;
use Tests\Fixtures\Http\MiddlewareB;
use Tests\TestBase;

class PipelineTest extends TestBase
{
    protected Pipeline $pipeline;
    protected function setUp(): void
    {
        parent::setUp();
        $this->pipeline = new Pipeline($this->app);
    }
    public function test_send_and_through_methods_return_pipeline_instance(): void
    {
        $request = Request::capture();
        $pipelineInstance_1 = $this->pipeline->send($request);
        $pipelineInstance_2 = $this->pipeline->through([MiddlewareA::class, MiddlewareB::class]);
        $this->assertInstanceOf(Pipeline::class, $pipelineInstance_1);
        $this->assertInstanceOf(Pipeline::class, $pipelineInstance_2);
        $this->assertSame($pipelineInstance_1, $pipelineInstance_2);
    }
    public function test_through_throws_exception_for_invalid_middleware(): void
    {
        $this->expectException(MiddlewareException::class);
        $this->pipeline->through([InvalidMiddleware::class]);
    }
    public function test_pipeline_executes_middlewares_in_order_and_then_destination(): void
    {
        $request = Request::capture();
        $log = new ExecutionLog();
        $response = $this->pipeline
            ->send($request)
            ->through([new MiddlewareA($log), new MiddlewareB($log)])
            ->then(function ($req) use ($log) {
                $log->entries[] = 'Destination';
                return new Response(body: 'ok');
            });
        $this->assertSame(['MiddlewareA', 'MiddlewareB', 'Destination'], $log->entries);
        $this->assertInstanceOf(Response::class, $response);
    }
    public function test_pipeline_resolves_middleware_from_container_when_string_is_provided(): void
    {
        $request = Request::capture();
        $log = new ExecutionLog();
        $middlewareASinglenton = new MiddlewareA($log);

        $this->app->singleton(MiddlewareA::class, fn() => $middlewareASinglenton);

        $this->pipeline
            ->send($request)
            ->through([MiddlewareA::class])
            ->then(fn() => new Response(body: 'ok'));

        // Assert: We verify that the singleton instance was used by checking
        // the state of the injected $log object, which is known only to this middleware.
        $this->assertSame(['MiddlewareA'], $log->entries);
    }
}
