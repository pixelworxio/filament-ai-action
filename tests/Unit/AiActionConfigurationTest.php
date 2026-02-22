<?php

declare(strict_types=1);

use Pixelworxio\FilamentAiAction\AiAction;
use Pixelworxio\LaravelAiAction\Enums\ActionMode;

it('stores the agent class name via agent()', function (): void {
    $action = AiAction::make('ai')->agent('App\\AiActions\\SummaryAgent');

    expect($action->agentClass)->toBe('App\\AiActions\\SummaryAgent');
});

it('sets the streaming flag via stream()', function (): void {
    $action = AiAction::make('ai')->stream();

    expect($action->streaming)->toBeTrue();
});

it('disables streaming when stream(false) is called', function (): void {
    $action = AiAction::make('ai')->stream(false);

    expect($action->streaming)->toBeFalse();
});

it('sets the user instruction flag and placeholder via withUserInstruction()', function (): void {
    $action = AiAction::make('ai')->withUserInstruction('What would you like to know?');

    expect($action->showUserInstruction)->toBeTrue();
    expect($action->userInstructionPlaceholder)->toBe('What would you like to know?');
});

it('stores the column name via persistResultTo()', function (): void {
    $action = AiAction::make('ai')->persistResultTo('ai_summary');

    expect($action->persistColumn)->toBe('ai_summary');
});

it('sets the queued mode and queue name via queued()', function (): void {
    $action = AiAction::make('ai')->queued('high');

    expect($action->mode)->toBe(ActionMode::Queued);
    expect($action->queue)->toBe('high');
});

it('uses the default queue name when queued() is called without arguments', function (): void {
    $action = AiAction::make('ai')->queued();

    expect($action->mode)->toBe(ActionMode::Queued);
    expect($action->queue)->toBe('default');
});

it('stores provider and model overrides via usingProvider()', function (): void {
    $action = AiAction::make('ai')->usingProvider('openai', 'gpt-4o');

    expect($action->providerOverride)->toBe('openai');
    expect($action->modelOverride)->toBe('gpt-4o');
});

it('stores the context callback via withContext()', function (): void {
    $callback = fn ($ctx, $record) => $ctx;

    $action = AiAction::make('ai')->withContext($callback);

    expect($action->contextCallback)->toBe($callback);
});
