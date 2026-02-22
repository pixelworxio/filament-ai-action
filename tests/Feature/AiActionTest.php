<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Pixelworxio\FilamentAiAction\AiAction;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Actions\RunAgentActionJob;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;

// ---------------------------------------------------------------------------
// Stub agent and model used across tests
// ---------------------------------------------------------------------------

class StubAgent implements AgentAction
{
    use InteractsWithAgent;

    public function instructions(AgentContext $context): string
    {
        return 'Summarise the record.';
    }

    public function prompt(AgentContext $context): string
    {
        return 'Please summarise.';
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }
}

class StubModel extends Model
{
    protected $table = 'stub_models';
    protected $guarded = [];
    public $timestamps = false;

    public string $ai_summary = '';
    public string $ai_structured = '';

    /** @return bool Prevent DB writes in tests. */
    public function save(array $options = []): bool
    {
        return true;
    }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

beforeEach(function (): void {
    FakeAgentAction::reset();
});

it('runs the agent synchronously and records the call', function (): void {
    FakeAgentAction::fakeResponse(StubAgent::class, 'This is the summary.');

    $record = new StubModel(['id' => 1]);

    $action = AiAction::make('ai')->agent(StubAgent::class);
    $action->record($record);

    $reflection = new ReflectionMethod($action, 'runAgent');
    $reflection->setAccessible(true);
    $reflection->invoke($action);

    FakeAgentAction::assertAgentCalled(StubAgent::class, 1);
});

it('persists text result to the model column', function (): void {
    FakeAgentAction::fakeResponse(StubAgent::class, 'Summary text.');

    $record = new StubModel(['id' => 1]);

    $action = AiAction::make('ai')
        ->agent(StubAgent::class)
        ->persistResultTo('ai_summary');

    $action->record($record);

    $reflection = new ReflectionMethod($action, 'runAgent');
    $reflection->setAccessible(true);
    $reflection->invoke($action);

    expect($record->ai_summary)->toBe('Summary text.');
});

it('persists JSON for structured results', function (): void {
    $structured = ['score' => 9, 'tags' => ['important', 'urgent']];

    FakeAgentAction::fakeResponse(StubAgent::class, '{"score":9}', $structured);

    $record = new StubModel(['id' => 1]);

    $action = AiAction::make('ai')
        ->agent(StubAgent::class)
        ->persistResultTo('ai_structured');

    $action->record($record);

    $reflection = new ReflectionMethod($action, 'runAgent');
    $reflection->setAccessible(true);
    $reflection->invoke($action);

    expect($record->ai_structured)->toBe(json_encode($structured));
});

it('dispatches RunAgentActionJob and does not execute inline when queued', function (): void {
    Queue::fake();
    FakeAgentAction::fakeResponse(StubAgent::class, 'Should not see this.');

    $record = new StubModel(['id' => 1]);

    $action = AiAction::make('ai')
        ->agent(StubAgent::class)
        ->queued('default');

    $action->record($record);

    $reflection = new ReflectionMethod($action, 'runAgent');
    $reflection->setAccessible(true);
    $reflection->invoke($action);

    Queue::assertPushed(RunAgentActionJob::class);
    FakeAgentAction::assertAgentNotCalled(StubAgent::class);
});

it('calls the withContext callback and passes its return value to the agent', function (): void {
    FakeAgentAction::fakeResponse(StubAgent::class, 'ctx enriched');

    $record = new StubModel(['id' => 1]);
    $callbackCalled = false;

    $action = AiAction::make('ai')
        ->agent(StubAgent::class)
        ->withContext(function (AgentContext $ctx, Model $rec) use (&$callbackCalled): AgentContext {
            $callbackCalled = true;
            return $ctx->withMeta('enriched', true);
        });

    $action->record($record);

    $reflection = new ReflectionMethod($action, 'runAgent');
    $reflection->setAccessible(true);
    $reflection->invoke($action);

    expect($callbackCalled)->toBeTrue();
    FakeAgentAction::assertAgentCalled(StubAgent::class, 1);
});

it('stores provider and model overrides via usingProvider()', function (): void {
    $action = AiAction::make('ai')
        ->agent(StubAgent::class)
        ->usingProvider('openai', 'gpt-4o');

    expect($action->providerOverride)->toBe('openai');
    expect($action->modelOverride)->toBe('gpt-4o');
});
