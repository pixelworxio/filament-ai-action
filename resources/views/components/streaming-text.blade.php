@props(['response' => '', 'complete' => false])

<div class="overflow-y-auto max-h-96 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">{{ $response }}@if (!$complete)<span class="ai-cursor">|</span>@endif</div>
