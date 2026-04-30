@php
    $locales = config('haris.locales', []);
    $currentLocale = app()->getLocale();
@endphp

<div {{ $attributes->class(['flex items-center gap-2']) }}>
    <span class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">
        {{ __('Language') }}
    </span>

    <div class="inline-flex rounded-full border border-zinc-200 bg-white p-1 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        @foreach ($locales as $locale => $meta)
            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $locale }}">
                <button
                    type="submit"
                    class="{{ $currentLocale === $locale
                        ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950'
                        : 'text-zinc-600 hover:text-zinc-950 dark:text-zinc-300 dark:hover:text-white' }} rounded-full px-3 py-1.5 text-xs font-semibold transition"
                    aria-pressed="{{ $currentLocale === $locale ? 'true' : 'false' }}"
                    title="{{ $meta['label'] }}"
                >
                    {{ $meta['short_label'] }}
                </button>
            </form>
        @endforeach
    </div>
</div>
