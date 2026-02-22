<div class="space-y-4">
    @if ($loading)
        <div class="animate-pulse space-y-3">
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-4/5"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/5"></div>
        </div>
    @elseif ($isStructured)
        <x-filament-ai-action::structured-output :data="$structuredData" />
    @else
        <x-filament-ai-action::streaming-text :response="$response" :complete="$complete" />
    @endif

    @if ($complete && config('filament-ai-action.allow_copy', true))
        <div class="flex justify-end">
            <button
                type="button"
                class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition"
                x-data="{ copied: false }"
                x-on:click="
                    navigator.clipboard.writeText({{ Js::from($response) }});
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                "
            >
                <template x-if="!copied">
                    <span>Copy</span>
                </template>
                <template x-if="copied">
                    <span>Copied!</span>
                </template>
            </button>
        </div>
    @endif

    @if ($complete && config('filament-ai-action.show_usage', false))
        <div class="text-xs text-gray-400 dark:text-gray-500 border-t border-gray-100 dark:border-gray-800 pt-2">
            Input: {{ $inputTokens }} tokens &middot; Output: {{ $outputTokens }} tokens
        </div>
    @endif
</div>
