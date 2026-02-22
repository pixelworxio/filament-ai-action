@props(['data' => []])

<dl class="space-y-3">
    @foreach ($data as $key => $value)
        <div class="grid grid-cols-3 gap-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 col-span-1">
                {{ ucwords(str_replace('_', ' ', $key)) }}
            </dt>
            <dd class="text-sm text-gray-800 dark:text-gray-200 col-span-2">
                @if (is_array($value))
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($value as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @elseif (is_int($value))
                    {{ $value }}
                @else
                    <p>{{ $value }}</p>
                @endif
            </dd>
        </div>
    @endforeach
</dl>
