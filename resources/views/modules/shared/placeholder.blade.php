<x-layouts::app :title="__($module['title'])">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __($module['title']) }}</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('This module is being prepared.') }}</h1>
            <p class="mt-3 max-w-3xl text-sm text-zinc-600 dark:text-zinc-300">{{ __($module['description']) }}</p>

            <div class="mt-5 rounded-2xl border border-amber-300/50 bg-amber-50 px-4 py-4 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
                <strong>{{ __('Good to know') }}:</strong>
                {{ __('This page is not the main place to work yet. The team is still preparing the flow so it is easier to use safely.') }}
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('What this module will handle') }}</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach ($module['focus'] as $focus)
                        <article class="rounded-2xl border border-zinc-200/70 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/60">
                            <p class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">{{ __('Planned focus') }}</p>
                            <p class="mt-3 text-lg font-semibold text-zinc-950 dark:text-white">{{ __($focus) }}</p>
                        </article>
                    @endforeach
                </div>
            </div>

            <aside class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Recommended next step') }}</h2>
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ __('For daily work, continue using the modules that are already active such as dashboard, payroll, employees, notifications, or audit trail.') }}</p>

                <div class="mt-5 space-y-3">
                    <a href="{{ route('dashboard') }}" class="block rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                        {{ __('Back to dashboard') }}
                    </a>
                    <a href="{{ route('payroll.index') }}" class="block rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                        {{ __('Open payroll') }}
                    </a>
                </div>
            </aside>
        </section>
    </div>
</x-layouts::app>
