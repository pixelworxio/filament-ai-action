<?php

declare(strict_types=1);

namespace Pixelworxio\FilamentAiAction;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Pixelworxio\FilamentAiAction\Livewire\AiResponseModal;

/**
 * Filament panel plugin that registers all filament-ai-action assets and components.
 *
 * Add this plugin to your panel provider using ->plugins([FilamentAiActionPlugin::make()])
 * to register the AiResponseModal Livewire component and the plugin CSS asset.
 * Component and asset registration is intentionally deferred to the plugin so
 * that they are only active on panels that have explicitly opted in.
 */
class FilamentAiActionPlugin implements Plugin
{
    /**
     * Return a new plugin instance resolved from the service container.
     *
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Return the unique plugin identifier.
     *
     * @return string
     */
    public function getId(): string
    {
        return 'filament-ai-action';
    }

    /**
     * Register Livewire components and CSS assets with the given panel.
     *
     * @param Panel $panel The Filament panel being registered.
     * @return void
     */
    public function register(Panel $panel): void
    {
        Livewire::component('filament-ai-action.ai-response-modal', AiResponseModal::class);

        FilamentAsset::register([
            Css::make('filament-ai-action', __DIR__ . '/../resources/css/filament-ai-action.css'),
        ]);
    }

    /**
     * Perform any boot-time setup for the plugin on the given panel.
     *
     * @param Panel $panel The Filament panel being booted.
     * @return void
     */
    public function boot(Panel $panel): void
    {
        // Reserved for future boot-time hooks.
    }
}
