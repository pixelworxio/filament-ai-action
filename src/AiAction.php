<?php

declare(strict_types=1);

namespace Pixelworxio\FilamentAiAction;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Pixelworxio\FilamentAiAction\Concerns\HasAgentConfiguration;
use Pixelworxio\FilamentAiAction\Concerns\HasStreamingModal;

/**
 * A first-class Filament action that runs an AI agent via laravel-ai-action.
 *
 * AiAction wraps a Filament modal action with a streaming-capable response
 * modal. Configure the agent class and behaviour through the fluent API
 * provided by HasAgentConfiguration, then add the action to any Filament
 * resource, page, or table.
 */
class AiAction extends Action
{
    use HasAgentConfiguration;
    use HasStreamingModal;

    /**
     * Create a pre-configured AiAction instance.
     *
     * Sets sensible defaults: a sparkles icon, primary colour, an xl modal,
     * and wires the action callback to runAgent().
     *
     * @param  string|null  $name  The action name, used as the HTML id and form key.
     */
    public static function make(?string $name = null): static
    {
        $static = parent::make($name ?? 'ai');

        $static
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->label(config('filament-ai-action.default_label', 'Ask AI'))
            ->modalWidth(config('filament-ai-action.modal_size', 'xl'))
            ->modalContent(function () use ($static): \Illuminate\View\View {
                /** @var view-string $viewName */
                $viewName = 'filament-ai-action::ai-action-modal-content';

                /** @var Model|null $record */
                $record = method_exists($static, 'getRecord') ? $static->getRecord() : null;

                return view($viewName, [
                    'agentClass' => $static->agentClass,
                    'streaming' => $static->streaming,
                    'showUserInstruction' => $static->showUserInstruction,
                    'userInstructionPlaceholder' => $static->userInstructionPlaceholder,
                    'recordId' => $record?->getKey(),
                    'recordClass' => $record !== null ? $record::class : null,
                ]);
            })
            ->action(function () use ($static): void {
                $static->runAgent();
            });

        return $static;
    }
}
