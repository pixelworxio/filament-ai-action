<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Pixelworxio\FilamentAiAction\AiBulkAction;
use Pixelworxio\LaravelAiAction\Actions\RunAgentActionJob;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;

// ---------------------------------------------------------------------------
// Stubs
// ---------------------------------------------------------------------------

class BulkStubAgent implements AgentAction
{
    use InteractsWithAgent;

    public function instructions(AgentContext $context): string
    {
        return 'Summarise the records.';
    }

    public function prompt(AgentContext $context): string
    {
        return 'Please summarise all records.';
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }
}

class BulkStubModel extends Model
{
    protected $table = 'bulk_stub_models';
    protected $guarded = [];
    public $timestamps = false;

    public string $ai_result = '';

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

it('passes all selected records to the agent via AgentContext::fromRecords()', function (): void {
    FakeAgentAction::fakeResponse(BulkStubAgent::class, 'bulk result');

    $action = AiBulkAction::make('ai-bulk')->agent(BulkStubAgent::class);
    $action->cachedTestRecords = [
        new BulkStubModel(['id' => 1]),
        new BulkStubModel(['id' => 2]),
        new BulkStubModel(['id' => 3]),
    ];

    $reflection = new ReflectionMethod($action, 'runAgent');
    $reflection->setAccessible(true);
    $reflection->invoke($action);

    // Called once per record (trait iterates records individually)
    FakeAgentAction::assertAgentCalled(BulkStubAgent::class, 3);
});

it('writes the result to every selected record when persistResultTo is set', function (): void {
    FakeAgentAction::fakeResponse(BulkStubAgent::class, 'per record result');

    $records = [
        new BulkStubModel(['id' => 1]),
        new BulkStubModel(['id' => 2]),
    ];

    $action = AiBulkAction::make('ai-bulk')
        ->agent(BulkStubAgent::class)
        ->persistResultTo('ai_result');

    $action->cachedTestRecords = $records;

    $reflection = new ReflectionMethod($action, 'runAgent');
    $reflection->setAccessible(true);
    $reflection->invoke($action);

    foreach ($records as $record) {
        expect($record->ai_result)->toBe('per record result');
    }
});

it('dispatches one RunAgentActionJob per record when queued', function (): void {
    Queue::fake();

    $action = AiBulkAction::make('ai-bulk')
        ->agent(BulkStubAgent::class)
        ->queued('default');

    $action->cachedTestRecords = [
        new BulkStubModel(['id' => 1]),
        new BulkStubModel(['id' => 2]),
        new BulkStubModel(['id' => 3]),
    ];

    $reflection = new ReflectionMethod($action, 'runAgent');
    $reflection->setAccessible(true);
    $reflection->invoke($action);

    Queue::assertPushed(RunAgentActionJob::class, 3);
    FakeAgentAction::assertAgentNotCalled(BulkStubAgent::class);
});
