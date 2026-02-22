<?php

declare(strict_types=1);

namespace Pixelworxio\FilamentAiAction;

use Filament\Tables\Actions\BulkAction;
use Pixelworxio\FilamentAiAction\Concerns\HasAgentConfiguration;
use Pixelworxio\FilamentAiAction\Concerns\HasStreamingModal;

/**
 * A first-class Filament bulk action that runs an AI agent against selected records.
 *
 * AiBulkAction extends Filament's BulkAction and applies the same fluent
 * configuration API as AiAction. When queued, one RunAgentActionJob is
 * dispatched per selected record. When run synchronously, each record is
 * processed in turn and results are optionally persisted to a model column.
 */
class AiBulkAction extends BulkAction
{
    use HasAgentConfiguration;
    use HasStreamingModal;

    /**
     * Create a pre-configured AiBulkAction instance.
     *
     * Sets sensible defaults: a sparkles icon, primary colour, and wires the
     * action callback to runAgent() which iterates over all selected records.
     *
     * @param string $name The action name, used as the HTML id and form key.
     * @return static
     */
    public static function make(string $name = 'ai-bulk'): static
    {
        $static = parent::make($name);

        $static
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->label(config('filament-ai-action.default_label', 'Ask AI'))
            ->action(function () use ($static): void {
                $static->runAgent();
            });

        return $static;
    }
}
