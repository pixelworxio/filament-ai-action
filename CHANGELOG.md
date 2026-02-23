# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.1 - 2026-02-23

**Full Changelog**: https://github.com/pixelworxio/filament-ai-action/compare/1.0.0...1.0.1

## 1.0.0 - 2026-02-23

Initial release

**Full Changelog**: https://github.com/pixelworxio/filament-ai-action/commits/1.0.0

## [Unreleased]

## [1.0.0] - unreleased

### Added

- `AiAction` extending Filament Action with full fluent API (7 methods)
- `AiBulkAction` extending Filament BulkAction with same fluent API
- `HasAgentConfiguration` trait with `runAgent()` and `persistResult()` logic
- `HasStreamingModal` trait for Livewire streaming state management
- `AgentResponseModal` Livewire v4 streaming modal component
- `AgentResultEntry` Filament infolist entry for rendering persisted AI results
- `AiActionWidget` embeddable Filament dashboard widget
- `FilamentAiActionPlugin` panel plugin registration
- Streaming modal with loading skeleton, incremental text, copy button, token usage footer
- Structured output rendering in modal and infolist
- `persistResultTo()` support for both text and JSON columns
- `queued()` support dispatching one job per record for bulk actions
- Config file with `default_label`, `show_usage`, `modal_size`, `allow_copy`
- View publishing via `filament-ai-action-views` tag
- Config publishing via `filament-ai-action-config` tag
