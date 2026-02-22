<?php

declare(strict_types=1);

namespace Pixelworxio\FilamentAiAction\Widgets;

use Filament\Widgets\Widget;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

/**
 * An embeddable Filament dashboard widget that renders an AI agent result inline.
 *
 * Configure the agent class and an optional context builder callback, then
 * register the widget in your panel's getWidgets() array. The agent is executed
 * during mount and the result is rendered using the same streaming-text or
 * structured-output sub-components used by the modal. No modal is presented —
 * output appears inline within the widget card.
 */
class AiActionWidget extends Widget
{
    protected static string $view = 'filament-ai-action::widgets.ai-action-widget';

    /** @var int|string|array<string, int|string> */
    protected int | string | array $columnSpan = 'full';

    /** @var class-string<AgentAction> */
    protected static string $agentClass = '';

    /** @var callable|null */
    protected static $contextBuilder = null;

    /** The agent's text response. */
    public string $response = '';

    /** Whether the result is structured output. */
    public bool $isStructured = false;

    /** The decoded structured data. */
    public mixed $structuredData = null;

    /** Whether the agent is still running. */
    public bool $loading = true;

    /**
     * Set the agent class for this widget.
     *
     * Must be called before the widget is registered in the panel.
     *
     * @param class-string<AgentAction> $agentClass
     * @return static
     */
    public static function agent(string $agentClass): static
    {
        static::$agentClass = $agentClass;

        return new static();
    }

    /**
     * Provide a callable that builds the AgentContext for this widget.
     *
     * The callable receives no arguments and must return an AgentContext.
     *
     * @param callable(): AgentContext $builder
     * @return static
     */
    public static function contextBuilder(callable $builder): static
    {
        static::$contextBuilder = $builder;

        return new static();
    }

    /**
     * Execute the agent on mount and populate the widget's reactive properties.
     *
     * @return void
     */
    public function mount(): void
    {
        if (static::$agentClass === '') {
            $this->loading = false;

            return;
        }

        /** @var AgentAction $agent */
        $agent = app(static::$agentClass);

        $context = static::$contextBuilder !== null
            ? (static::$contextBuilder)()
            : new AgentContext(
                record: null,
                records: [],
                meta: [],
                userInstruction: null,
                panelId: null,
                resourceClass: null,
            );

        /** @var RunAgentAction $runner */
        $runner = app(RunAgentAction::class);
        $result = $runner->execute($agent, $context);

        $this->applyResult($result);
    }

    /**
     * Apply a completed AgentResult to the widget's reactive properties.
     *
     * @param AgentResult $result The completed result.
     * @return void
     */
    private function applyResult(AgentResult $result): void
    {
        $this->response = $result->text;
        $this->loading = false;

        if ($result->isStructured()) {
            $this->isStructured = true;
            $this->structuredData = is_array($result->structured)
                ? $result->structured
                : (array) $result->structured;
        }
    }
}
