<?php

declare(strict_types=1);

namespace Pixelworxio\FilamentAiAction\Concerns;

/**
 * Manages Livewire streaming modal state for Filament actions.
 *
 * Apply this trait alongside HasAgentConfiguration on any Filament action that
 * renders its result through the AiResponseModal Livewire component. The
 * trait exposes hooks that the modal component calls during its lifecycle to
 * initialise and incrementally update the streamed response.
 */
trait HasStreamingModal
{
    /**
     * Initialise the streaming modal state.
     *
     * Called by AiResponseModal on mount to signal that the action is ready
     * to begin streaming or rendering its response.
     *
     * @return void
     */
    public function mountStreamingModal(): void
    {
        // Invoked by the Livewire component on mount.
        // Override in the action class to perform any setup before streaming begins.
    }

    /**
     * Handle an incremental text chunk during streaming.
     *
     * Called by AiResponseModal each time a new chunk is received from the
     * AI provider. The chunk is appended to the displayed response via
     * Livewire's reactive property updates.
     *
     * @param string $chunk The incremental text fragment from the model.
     * @return void
     */
    public function streamChunk(string $chunk): void
    {
        // Invoked by the Livewire component for each streaming chunk.
        // Override to intercept or transform chunks before they are displayed.
    }
}
