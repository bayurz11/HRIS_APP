<x-layouts::app :title="__('Audit Trail')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Audit trail') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Payroll activity trail') }}</h1>
                    <p class="mt-3 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">{{ __('Monitor payroll master changes, period processing, approvals, payslip publishing, and other critical activity.') }}</p>
                </div>

                <form method="GET" action="{{ route('audit-trail.index') }}" class="grid gap-3 sm:grid-cols-3">
                    <select name="module" class="rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">{{ __('All modules') }}</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module }}" @selected($selectedModule === $module)>{{ ucfirst($module) }}</option>
                        @endforeach
                    </select>
                    <select name="event" class="rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">{{ __('All events') }}</option>
                        @foreach ($events as $event)
                            <option value="{{ $event }}" @selected($selectedEvent === $event)>{{ str_replace('_', ' ', ucfirst($event)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-2xl bg-zinc-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ __('Apply filter') }}
                    </button>
                </form>
            </div>
        </section>

        <section class="space-y-4">
            @forelse ($logs as $log)
                <article class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ $log->module }} / {{ $log->event }}</p>
                            <h2 class="mt-2 text-lg font-semibold text-zinc-950 dark:text-white">{{ $log->description }}</h2>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $log->actor?->name ?? __('System') }} | {{ $log->created_at->format('d M Y H:i') }}
                            </p>
                        </div>
                    </div>

                    @if ($log->before_json || $log->after_json)
                        <div class="mt-5 grid gap-4 lg:grid-cols-2">
                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <p class="text-xs uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('Before') }}</p>
                                <pre class="mt-3 overflow-x-auto text-xs text-zinc-700 dark:text-zinc-200">{{ json_encode($log->before_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                <p class="text-xs uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('After') }}</p>
                                <pre class="mt-3 overflow-x-auto text-xs text-zinc-700 dark:text-zinc-200">{{ json_encode($log->after_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-zinc-300 px-4 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    {{ __('No audit trail data yet.') }}
                </div>
            @endforelse
        </section>

        <div>
            {{ $logs->links() }}
        </div>
    </div>
</x-layouts::app>
