<?php

declare(strict_types=1);

namespace Pixelworxio\FilamentAiAction\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

/**
 * Livewire v4 component that renders the AI response inside a Filament modal.
 *
 * mount() only stores props. The actual agent run is triggered by wire:init="runAgent"
 * in the view so it executes as a proper Livewire action request — a requirement
 * for $this->stream() to work correctly.
 */
class AiResponseModal extends Component
{
    /** The raw text response accumulated from the agent. */
    public string $response = '';

    /** Whether the agent is currently running. */
    public bool $loading = true;

    /** Whether the agent has finished producing output. */
    public bool $complete = false;

    /** Number of input (prompt) tokens consumed. */
    public int $inputTokens = 0;

    /** Number of output (completion) tokens generated. */
    public int $outputTokens = 0;

    /** Whether the result is structured output. */
    public bool $isStructured = false;

    /** The decoded structured data, or null for text results. */
    public mixed $structuredData = null;

    /** @var class-string<AgentAction> */
    #[Locked]
    public string $agentClass;

    /** The serialised Eloquent record passed to the agent context. */
    #[Locked]
    public ?int $recordId = null;

    /** The fully-qualified Eloquent model class for the record. */
    #[Locked]
    public ?string $recordClass = null;

    /** Whether to stream the response incrementally. */
    #[Locked]
    public bool $streaming = false;

    /** Whether to display a free-text user instruction input. */
    #[Locked]
    public bool $showUserInstruction = false;

    /** Placeholder text for the user instruction field. */
    #[Locked]
    public string $userInstructionPlaceholder = '';

    /**
     * Store props only. Agent execution is deferred to runAgent() via wire:init.
     *
     * @param  class-string<AgentAction>|null  $agentClass
     */
    public function mount(
        ?string $agentClass = null,
        bool $streaming = false,
        bool $showUserInstruction = false,
        string $userInstructionPlaceholder = '',
        ?int $recordId = null,
        ?string $recordClass = null,
    ): void {
        /** @var class-string<AgentAction> $agentClassValue */
        $agentClassValue = $agentClass ?? '';
        $this->agentClass = $agentClassValue;
        $this->streaming = $streaming;
        $this->showUserInstruction = $showUserInstruction;
        $this->userInstructionPlaceholder = $userInstructionPlaceholder;
        $this->recordId = $recordId;
        $this->recordClass = $recordClass;
    }

    /**
     * Execute the agent and update component state with the result.
     *
     * Called via wire:init so it runs as a proper Livewire action request,
     * which is required for $this->stream() to work correctly.
     */
    public function runAgent(): void
    {
        /** @var AgentAction $agent */
        $agent = app($this->agentClass);

        $context = $this->buildContext();

        /** @var RunAgentAction $runner */
        $runner = app(RunAgentAction::class);

        if ($this->streaming) {
            $this->executeStreaming($runner, $agent, $context);
        } else {
            $this->executeSync($runner, $agent, $context);
        }
    }

    public function render(): View
    {
        /** @var view-string $viewName */
        $viewName = 'filament-ai-action::ai-response-modal';

        return view($viewName);
    }

    private function executeSync(RunAgentAction $runner, AgentAction $agent, AgentContext $context): void
    {
        $result = $runner->execute($agent, $context);

        $this->applyResult($result);
    }

    /**
     * Execute the agent and stream text chunks via Livewire v4's stream() helper.
     *
     * Requires this method to be invoked as a Livewire action (e.g. via wire:init),
     * not from mount(), so the HTTP response supports chunked streaming.
     */
    private function executeStreaming(RunAgentAction $runner, AgentAction $agent, AgentContext $context): void
    {
        $result = $runner->execute($agent, $context);

        $fullText = $result->text;
        $chunkSize = 20;
        $offset = 0;
        $length = mb_strlen($fullText);

        while ($offset < $length) {
            $chunk = mb_substr($fullText, $offset, $chunkSize);
            $offset += $chunkSize;

            $this->stream(
                to: 'response',
                content: $chunk,
            );
        }

        $this->applyResult($result);
    }

    private function applyResult(AgentResult $result): void
    {
        $this->response = $result->text;
        $this->loading = false;
        $this->complete = true;
        $this->inputTokens = $result->inputTokens;
        $this->outputTokens = $result->outputTokens;

        if ($result->isStructured()) {
            $this->isStructured = true;
            $this->structuredData = is_array($result->structured)
                ? $result->structured
                : (array) $result->structured;
        }
    }

    private function buildContext(): AgentContext
    {
        if ($this->recordClass !== null && $this->recordId !== null) {
            /** @var Model $record */
            $record = $this->recordClass::find($this->recordId);

            return AgentContext::fromRecord($record);
        }

        return new AgentContext(
            record: null,
            records: [],
            meta: [],
            userInstruction: null,
            panelId: null,
            resourceClass: null,
        );
    }
}
