<?php

declare(strict_types=1);

namespace Pixelworxio\FilamentAiAction\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Pixelworxio\LaravelAiAction\LaravelAiActionServiceProvider;
use Pixelworxio\FilamentAiAction\FilamentAiActionServiceProvider;

/**
 * Base test case for all filament-ai-action tests.
 *
 * Registers both the laravel-ai-action and filament-ai-action service providers
 * and seeds the minimum config values required for the test suite to run without
 * making real AI provider calls.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Register the package service providers under test.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelAiActionServiceProvider::class,
            FilamentAiActionServiceProvider::class,
        ];
    }

    /**
     * Seed the minimum application config for the test environment.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('ai-action.provider', 'anthropic');
        $app['config']->set('ai-action.model', 'claude-sonnet-4-20250514');
        $app['config']->set('filament-ai-action.show_usage', false);
        $app['config']->set('filament-ai-action.allow_copy', true);
    }
}
