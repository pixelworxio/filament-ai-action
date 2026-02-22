<?php

declare(strict_types=1);

namespace Pixelworxio\FilamentAiAction\Infolists;

use Filament\Infolists\Components\Entry;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * A Filament infolist entry that renders persisted AI agent results.
 *
 * Reads a model attribute that was previously written by AiAction's
 * persistResultTo() method. Intelligently detects whether the stored value is
 * a JSON-encoded structured result or plain text and renders it using the
 * appropriate sub-component. Optionally passes the value through
 * Str::markdown() and can embed a refresh AiAction button.
 */
class AgentResultEntry extends Entry
{
    protected string $view = 'filament-ai-action::infolists.agent-result-entry';

    protected bool $renderMarkdown = false;

    protected bool $showRefreshAction = false;

    /**
     * Render the stored value through Laravel's Str::markdown() converter.
     *
     * @param bool $condition When false, markdown rendering is skipped.
     * @return static
     */
    public function markdown(bool $condition = true): static
    {
        $this->renderMarkdown = $condition;

        return $this;
    }

    /**
     * Embed an AiAction refresh button below the rendered result.
     *
     * The embedded action re-runs the agent and overwrites the persisted column.
     *
     * @param bool $condition When false, no refresh button is shown.
     * @return static
     */
    public function withRefreshAction(bool $condition = true): static
    {
        $this->showRefreshAction = $condition;

        return $this;
    }

    /**
     * Indicate whether markdown rendering is enabled.
     *
     * @return bool
     */
    public function isMarkdown(): bool
    {
        return $this->renderMarkdown;
    }

    /**
     * Indicate whether the refresh action button should be shown.
     *
     * @return bool
     */
    public function hasRefreshAction(): bool
    {
        return $this->showRefreshAction;
    }

    /**
     * Resolve the display value for this entry.
     *
     * Returns the raw state without modification; rendering logic lives in the
     * Blade view so it can access all component properties.
     *
     * @return mixed
     */
    public function getStateUsing(): mixed
    {
        return $this->getState();
    }

    /**
     * Determine whether the stored value is a JSON-encoded structured result.
     *
     * @return bool
     */
    public function isStructured(): bool
    {
        $state = $this->getState();

        if (! is_string($state) || $state === '') {
            return false;
        }

        json_decode($state, associative: true);

        return json_last_error() === JSON_ERROR_NONE
            && str_starts_with(ltrim($state), '{');
    }

    /**
     * Decode the stored JSON value as an associative array.
     *
     * Returns an empty array when the value is not valid JSON.
     *
     * @return array<string, mixed>
     */
    public function getStructuredData(): array
    {
        $state = $this->getState();

        if (! is_string($state)) {
            return [];
        }

        return json_decode($state, associative: true) ?? [];
    }

    /**
     * Return the plain-text value, optionally converted to HTML via Str::markdown().
     *
     * @return string
     */
    public function getTextValue(): string
    {
        $state = (string) ($this->getState() ?? '');

        if ($this->renderMarkdown) {
            return Str::markdown($state);
        }

        return $state;
    }
}
