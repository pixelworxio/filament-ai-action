<?php

declare(strict_types=1);

use Pixelworxio\FilamentAiAction\AiBulkAction;
use Pixelworxio\LaravelAiAction\Enums\ActionMode;

it('stores the agent class name via agent()', function (): void {
    $action = AiBulkAction::make('ai-bulk')->agent('App\\AiActions\\SummaryAgent');

    expect($action->agentClass)->toBe('App\\AiActions\\SummaryAgent');
});

it('sets the streaming flag via stream()', function (): void {
    $action = AiBulkAction::make('ai-bulk')->stream();

    expect($action->streaming)->toBeTrue();
});

it('disables streaming when stream(false) is called', function (): void {
    $action = AiBulkAction::make('ai-bulk')->stream(false);

    expect($action->streaming)->toBeFalse();
});

it('sets the user instruction flag and placeholder via withUserInstruction()', function (): void {
    $action = AiBulkAction::make('ai-bulk')->withUserInstruction('Describe the action');

    expect($action->showUserInstruction)->toBeTrue();
    expect($action->userInstructionPlaceholder)->toBe('Describe the action');
});

it('stores the column name via persistResultTo()', function (): void {
    $action = AiBulkAction::make('ai-bulk')->persistResultTo('ai_result');

    expect($action->persistColumn)->toBe('ai_result');
});

it('sets the queued mode and queue name via queued()', function (): void {
    $action = AiBulkAction::make('ai-bulk')->queued('low');

    expect($action->mode)->toBe(ActionMode::Queued);
    expect($action->queue)->toBe('low');
});

it('uses the default queue name when queued() is called without arguments', function (): void {
    $action = AiBulkAction::make('ai-bulk')->queued();

    expect($action->mode)->toBe(ActionMode::Queued);
    expect($action->queue)->toBe('default');
});

it('stores provider and model overrides via usingProvider()', function (): void {
    $action = AiBulkAction::make('ai-bulk')->usingProvider('anthropic', 'claude-opus-4-6');

    expect($action->providerOverride)->toBe('anthropic');
    expect($action->modelOverride)->toBe('claude-opus-4-6');
});

it('stores the context callback via withContext()', function (): void {
    $callback = fn ($ctx, $record) => $ctx;

    $action = AiBulkAction::make('ai-bulk')->withContext($callback);

    expect($action->contextCallback)->toBe($callback);
});
