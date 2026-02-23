<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @if ($entry->isStructured())
        <x-filament-ai-action::structured-output :data="$entry->getStructuredData()" />
    @else
        <div @class([
            'text-sm text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap',
            'prose dark:prose-invert max-w-none' => $entry->isMarkdown(),
        ])>
            @if ($entry->isMarkdown())
                {!! $entry->getTextValue() !!}
            @else
                {{ $entry->getTextValue() }}
            @endif
        </div>
    @endif
</x-dynamic-component>
