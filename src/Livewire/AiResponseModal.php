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
 * On mount the component resolves the configured agent class, builds an
 * AgentContext from the passed record, and executes the agent via
 * RunAgentAction. When streaming is enabled the response is emitted
 * incrementally via Livewire v4's stream() helper. On completion the loading
 * skeleton is replaced with either the streaming-text or structured-output
 * sub-component depending on the result type.
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
    public string $agentClass = '';

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
     * Mount the component and immediately trigger the agent run.
     *
     * @param class-string<AgentAction> $agentClass              The agent class to execute.
     * @param bool                      $streaming               Whether to use streaming output.
     * @param bool                      $showUserInstruction     Whether a user instruction input is shown.
     * @param string                    $userInstructionPlaceholder Placeholder for the instruction field.
     * @param int|null                  $recordId                The primary key of the Eloquent record.
     * @param string|null               $recordClass             The fully-qualified Eloquent model class.
     * @return void
     */
    public function mount(
        string $agentClass = '',
        bool $streaming = false,
        bool $showUserInstruction = false,
        string $userInstructionPlaceholder = '',
        ?int $recordId = null,
        ?string $recordClass = null,
    ): void {
        $this->agentClass = $agentClass;
        $this->streaming = $streaming;
        $this->showUserInstruction = $showUserInstruction;
        $this->userInstructionPlaceholder = $userInstructionPlaceholder;
        $this->recordId = $recordId;
        $this->recordClass = $recordClass;

        $this->runAgent();
    }

    /**
     * Execute the agent and update component state with the result.
     *
     * Resolves the agent from the container, builds an AgentContext from the
     * mounted record, runs the agent via RunAgentAction, and updates all
     * reactive properties upon completion. When streaming is enabled the
     * response text is emitted in chunks via the Livewire v4 stream() helper.
     *
     * @return void
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

    /**
     * Render the Livewire component view.
     *
     * @return View
     */
    public function render(): View
    {
        return view('filament-ai-action::ai-response-modal');
    }

    /**
     * Execute the agent synchronously and update component state once complete.
     *
     * @param RunAgentAction $runner  The action runner.
     * @param AgentAction    $agent   The resolved agent.
     * @param AgentContext   $context The built context.
     * @return void
     */
    private function executeSync(RunAgentAction $runner, AgentAction $agent, AgentContext $context): void
    {
        $result = $runner->execute($agent, $context);

        $this->applyResult($result);
    }

    /**
     * Execute the agent and stream text chunks to the component via stream().
     *
     * Uses the Livewire v4 stream() helper to push incremental chunks to the
     * $response property. On completion the final result state is applied.
     *
     * @param RunAgentAction $runner  The action runner.
     * @param AgentAction    $agent   The resolved agent.
     * @param AgentContext   $context The built context.
     * @return void
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

    /**
     * Apply a completed AgentResult to the component's reactive properties.
     *
     * @param AgentResult $result The completed agent result.
     * @return void
     */
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

    /**
     * Build an AgentContext from the mounted record identifier.
     *
     * When no record class is available an empty context with no record is built.
     *
     * @return AgentContext
     */
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
