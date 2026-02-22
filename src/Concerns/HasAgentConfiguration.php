<?php

declare(strict_types=1);

namespace Pixelworxio\FilamentAiAction\Concerns;

use Illuminate\Database\Eloquent\Model;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Actions\RunAgentActionJob;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\ActionMode;

/**
 * Provides full AI agent configuration and execution for Filament actions.
 *
 * Apply this trait to any Filament Action or BulkAction to gain the fluent
 * API for configuring an agent class, streaming mode, user instruction input,
 * column persistence, queued dispatch, provider/model overrides, and context
 * enrichment callbacks.
 */
trait HasAgentConfiguration
{
    /** @var class-string<AgentAction> */
    public string $agentClass = '';

    public bool $showUserInstruction = false;

    public string $userInstructionPlaceholder = '';

    public bool $streaming = false;

    public ?string $persistColumn = null;

    public ActionMode $mode = ActionMode::Sync;

    public string $queue = 'default';

    public ?string $providerOverride = null;

    public ?string $modelOverride = null;

    /** @var callable|null */
    public $contextCallback = null;

    /**
     * Set the agent class to use for this action.
     *
     * @param class-string<AgentAction> $agentClass The fully-qualified agent class name.
     * @return static
     */
    public function agent(string $agentClass): static
    {
        $this->agentClass = $agentClass;

        return $this;
    }

    /**
     * Show a free-text instruction input in the modal before running the agent.
     *
     * @param string $placeholder The placeholder text for the instruction field.
     * @return static
     */
    public function withUserInstruction(string $placeholder = ''): static
    {
        $this->showUserInstruction = true;
        $this->userInstructionPlaceholder = $placeholder;

        return $this;
    }

    /**
     * Enable streaming response rendering in the modal.
     *
     * @param bool $condition When false, streaming is not enabled.
     * @return static
     */
    public function stream(bool $condition = true): static
    {
        $this->streaming = $condition;

        return $this;
    }

    /**
     * Write the agent result to the given model column after successful execution.
     *
     * Text results are stored as a plain string. Structured results are
     * JSON-encoded before storage.
     *
     * @param string $column The Eloquent model column name.
     * @return static
     */
    public function persistResultTo(string $column): static
    {
        $this->persistColumn = $column;

        return $this;
    }

    /**
     * Dispatch the agent as a queued background job instead of running inline.
     *
     * For bulk actions, one RunAgentActionJob is dispatched per record.
     *
     * @param string $queue The queue name to dispatch onto.
     * @return static
     */
    public function queued(string $queue = 'default'): static
    {
        $this->mode = ActionMode::Queued;
        $this->queue = $queue;

        return $this;
    }

    /**
     * Override the provider and model for this action only.
     *
     * @param string $provider The provider key (e.g. "anthropic", "openai").
     * @param string $model    The model identifier.
     * @return static
     */
    public function usingProvider(string $provider, string $model): static
    {
        $this->providerOverride = $provider;
        $this->modelOverride = $model;

        return $this;
    }

    /**
     * Register a callback to enrich the AgentContext before execution.
     *
     * The callback receives the built context and the Eloquent record and must
     * return the (potentially modified) context.
     *
     * @param callable(AgentContext, Model): AgentContext $callback
     * @return static
     */
    public function withContext(callable $callback): static
    {
        $this->contextCallback = $callback;

        return $this;
    }

    /**
     * Run the configured agent synchronously or dispatch it as a queued job.
     *
     * Handles both single-record and bulk-record invocations. When queued, one
     * job is dispatched per record and the method returns immediately.
     *
     * @return void
     */
    protected function runAgent(): void
    {
        /** @var AgentAction $agent */
        $agent = app($this->agentClass);

        if ($this->providerOverride !== null && $this->modelOverride !== null) {
            $agent = $this->applyProviderOverride($agent);
        }

        $records = $this->resolveRecords();

        foreach ($records as $record) {
            $context = $this->buildContext($agent, $record);

            if ($this->mode === ActionMode::Queued) {
                dispatch(new RunAgentActionJob($agent, $context))->onQueue($this->queue);
                continue;
            }

            /** @var RunAgentAction $runner */
            $runner = app(RunAgentAction::class);
            $result = $runner->execute($agent, $context);

            if ($this->persistColumn !== null) {
                $this->persistResult($result, $record);
            }
        }
    }

    /**
     * Write an AgentResult to the configured model column and save the record.
     *
     * @param AgentResult $result The result to persist.
     * @param Model       $record The Eloquent model to update.
     * @return void
     */
    protected function persistResult(AgentResult $result, Model $record): void
    {
        $value = $result->isStructured()
            ? json_encode($result->structured)
            : $result->text;

        $record->{$this->persistColumn} = $value;
        $record->save();
    }

    /**
     * Resolve the record(s) to process for this invocation.
     *
     * Returns an array of models regardless of whether this is a single-record
     * action or a bulk action so the run loop can iterate uniformly.
     *
     * @return array<int, Model>
     */
    private function resolveRecords(): array
    {
        if (method_exists($this, 'getRecords')) {
            /** @var iterable<Model> $records */
            $records = $this->getRecords();

            return collect($records)->values()->all();
        }

        /** @var Model $record */
        $record = $this->getRecord();

        return [$record];
    }

    /**
     * Build the AgentContext for the given record, applying any enrichment callback.
     *
     * @param AgentAction $agent  The agent (unused here but available for future use).
     * @param Model       $record The Eloquent model for this invocation.
     * @return AgentContext The constructed context.
     */
    private function buildContext(AgentAction $agent, Model $record): AgentContext
    {
        $records = $this->resolveRecords();

        $context = count($records) > 1
            ? AgentContext::fromRecords($records)
            : AgentContext::fromRecord($record);

        if ($this->showUserInstruction) {
            $userInstruction = $this->getMountedActionData()['user_instruction'] ?? '';
            $context = $context->withMeta('userInstruction', $userInstruction);
        }

        if ($this->contextCallback !== null) {
            $context = ($this->contextCallback)($context, $record);
        }

        return $context;
    }

    /**
     * Wrap the agent's provider and model with the configured overrides.
     *
     * Returns an anonymous proxy that delegates all contract methods to the
     * original agent but overrides provider() and model() with the configured
     * values.
     *
     * @param AgentAction $agent The original agent instance.
     * @return AgentAction The agent with overridden provider/model.
     */
    private function applyProviderOverride(AgentAction $agent): AgentAction
    {
        $providerOverride = $this->providerOverride;
        $modelOverride = $this->modelOverride;

        return new class ($agent, $providerOverride, $modelOverride) implements AgentAction {
            public function __construct(
                private readonly AgentAction $inner,
                private readonly string $provider,
                private readonly string $model,
            ) {}

            public function instructions(AgentContext $context): string
            {
                return $this->inner->instructions($context);
            }

            public function prompt(AgentContext $context): string
            {
                return $this->inner->prompt($context);
            }

            public function provider(): string
            {
                return $this->provider;
            }

            public function model(): string
            {
                return $this->model;
            }

            public function handle(AgentContext $context): AgentResult
            {
                return $this->inner->handle($context);
            }
        };
    }
}
