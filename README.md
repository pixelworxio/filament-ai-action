![Livewire Workflows Banner](https://raw.githubusercontent.com/pixelworxio/filament-ai-action/main/art/filament-ai-action.png)

<p align="center">
  <a href="https://github.com/pixelworxio/filament-ai-action/actions/workflows/run-tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/pixelworxio/filament-ai-action/run-tests.yml?branch=main&label=tests&style=flat-square" alt="GitHub Tests Action Status"></a>
  <a href="https://github.com/pixelworxio/filament-ai-action"><img src="https://img.shields.io/github/stars/pixelworxio/filament-ai-action?style=flat-square" alt="GitHub Stars"></a>
</p>

AI-powered actions for Filament v5 — streaming modal, structured output, and intelligent table actions.

---

## Requirements

- PHP ^8.4
- Laravel ^12.0
- Filament ^5.0
- pixelworxio/laravel-ai-action ^1.0

---

## Installation

```bash
composer require pixelworxio/filament-ai-action
```

Then register the plugin in your panel provider:

```php
use Pixelworxio\FilamentAiAction\FilamentAiActionPlugin;

->plugins([
    FilamentAiActionPlugin::make(),
])
```

---

## AiAction

`AiAction` extends `Filament\Actions\Action` and exposes seven fluent configuration methods.

### agent()

Set the agent class to run:

```php
use Pixelworxio\FilamentAiAction\AiAction;

AiAction::make()
    ->agent(SummaryAgent::class)
```

### stream()

Enable incremental text rendering in the modal:

```php
AiAction::make()
    ->agent(SummaryAgent::class)
    ->stream()
```

### withUserInstruction()

Show a free-text input in the modal before running the agent. The instruction is attached to the `AgentContext` as metadata:

```php
AiAction::make()
    ->agent(SummaryAgent::class)
    ->withUserInstruction('What would you like to know about this record?')
```

### persistResultTo()

Write the agent result to an Eloquent model column after successful execution. Structured results are JSON-encoded automatically:

```php
AiAction::make()
    ->agent(SummaryAgent::class)
    ->persistResultTo('ai_summary')
```

### queued()

Dispatch the agent as a background job instead of running inline. Accepts an optional queue name:

```php
AiAction::make()
    ->agent(SummaryAgent::class)
    ->queued('high')
```

### usingProvider()

Override the AI provider and model for this action only, ignoring the package-level defaults:

```php
AiAction::make()
    ->agent(SummaryAgent::class)
    ->usingProvider('openai', 'gpt-4o')
```

### withContext()

Enrich the `AgentContext` before the agent runs. The callback receives the built context and the Eloquent record:

```php
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;

AiAction::make()
    ->agent(SummaryAgent::class)
    ->withContext(function (AgentContext $ctx, Model $record): AgentContext {
        return $ctx->withMeta('tenant_id', $record->tenant_id);
    })
```

---

## AiBulkAction

`AiBulkAction` extends `Filament\Tables\Actions\BulkAction` and uses the same fluent API. When queued, one `RunAgentActionJob` is dispatched per selected record.

```php
use Pixelworxio\FilamentAiAction\AiBulkAction;

AiBulkAction::make()
    ->agent(SummaryAgent::class)
    ->persistResultTo('ai_summary')
    ->queued()
    ->deselectRecordsAfterCompletion()
```

---

## AgentResultEntry

Render a persisted AI result inside a Filament infolist. Automatically detects whether the stored value is JSON (structured) or plain text:

```php
use Pixelworxio\FilamentAiAction\Infolists\AgentResultEntry;

AgentResultEntry::make('ai_summary')
    ->label('AI Summary')
    ->markdown()
    ->withRefreshAction()
```

- `->markdown()` — passes the stored value through `Str::markdown()` before rendering.
- `->withRefreshAction()` — embeds an `AiAction` button below the result to re-run and re-persist.

---

## AiActionWidget

Embed an AI agent result directly on a Filament dashboard panel without a modal:

```php
use Pixelworxio\FilamentAiAction\Widgets\AiActionWidget;

// In your panel provider's getWidgets():
protected function getWidgets(): array
{
    return [
        AiActionWidget::agent(DashboardInsightAgent::class)
            ->contextBuilder(fn (): AgentContext => AgentContext::fromRecords(
                Post::latest()->limit(10)->get()->all()
            )),
    ];
}
```

The widget respects Filament's `$columnSpan` property and renders inline using the same `streaming-text` or `structured-output` components.

---

## Streaming

When `->stream()` is enabled on `AiAction`, the `AgentResponseModal` Livewire component pushes response text to the browser in chunks using Livewire v4's `stream()` helper. A blinking cursor is shown while the response is incomplete. Use streaming for long responses where you want to show the user progress rather than waiting for a final result.

```php
AiAction::make()
    ->agent(LongFormWriterAgent::class)
    ->stream()
```

---

## Structured Output

When an agent implements `HasStructuredOutput` from `pixelworxio/laravel-ai-action`, the modal and infolist entry render the result as a formatted definition list rather than plain text. Keys are converted from `snake_case` to Title Case. Array values are rendered as bulleted lists; integers and strings are rendered inline.

To store a structured result, use `->persistResultTo()` — the value is JSON-encoded automatically. `AgentResultEntry` detects the JSON string and renders the structured view.

---

## Testing

Use `FakeAgentAction` from the core package to test Filament actions without making real AI provider calls:

```php
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;

beforeEach(fn () => FakeAgentAction::reset());

it('persists the agent result to the model column', function (): void {
    FakeAgentAction::fakeResponse(SummaryAgent::class, 'This is the summary.');

    $record = Post::factory()->create();

    $action = AiAction::make('ai')
        ->agent(SummaryAgent::class)
        ->persistResultTo('ai_summary');

    $action->record($record);

    $reflection = new ReflectionMethod($action, 'runAgent');
    $reflection->setAccessible(true);
    $reflection->invoke($action);

    expect($record->fresh()->ai_summary)->toBe('This is the summary.');
    FakeAgentAction::assertAgentCalled(SummaryAgent::class, 1);
});
```

---

## Config Reference

Publish the config file:

```bash
php artisan vendor:publish --tag=filament-ai-action-config
```

| Key | Env Variable | Default | Description |
|---|---|---|---|
| `default_label` | `FILAMENT_AI_ACTION_LABEL` | `'Ask AI'` | Default button label for `AiAction` and `AiBulkAction` |
| `show_usage` | `FILAMENT_AI_ACTION_SHOW_USAGE` | `false` | Show input/output token counts in the modal footer |
| `modal_size` | `FILAMENT_AI_ACTION_MODAL_SIZE` | `'xl'` | Filament modal width (`sm`, `md`, `lg`, `xl`, `2xl`, etc.) |
| `allow_copy` | `FILAMENT_AI_ACTION_ALLOW_COPY` | `true` | Show a copy-to-clipboard button when the response is complete |

---

## Publishing Assets

Publish the config file:

```bash
php artisan vendor:publish --tag=filament-ai-action-config
```

Publish the Blade views to customise the modal and components:

```bash
php artisan vendor:publish --tag=filament-ai-action-views
```
