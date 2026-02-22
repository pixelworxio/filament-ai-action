<?php

declare(strict_types=1);

namespace Pixelworxio\FilamentAiAction;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for the filament-ai-action package.
 *
 * Registers the package's config file, Blade views, and translation strings via
 * the Spatie PackageServiceProvider base class. Livewire component and asset
 * registration is intentionally delegated to FilamentAiActionPlugin so that
 * those resources are only active on panels that have explicitly opted in.
 */
class FilamentAiActionServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package definition.
     *
     * Registers:
     * - Config file published via tag `filament-ai-action-config`
     * - Views published via tag `filament-ai-action-views`
     * - Translations reserved for future use
     *
     * @param Package $package The package builder instance.
     * @return void
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-ai-action')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations();
    }
}
