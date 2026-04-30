<x-layouts::app :title="__('Notifications')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600 dark:text-cyan-300">{{ __('Notifications') }}</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ __('Application activity notifications') }}</h1>
                    <p class="mt-3 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">{{ __('See payroll approval notices, period processing events, and newly published payslips.') }}</p>
                </div>

                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="rounded-full border border-zinc-300 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                        {{ __('Mark all as read (:count)', ['count' => number_format($unreadCount)]) }}
                    </button>
                </form>
            </div>
        </section>

        <section class="space-y-4">
            @forelse ($notifications as $notification)
                <article class="rounded-3xl border {{ $notification->read_at ? 'border-zinc-200/70' : 'border-emerald-300/60 bg-emerald-50/60 dark:border-emerald-500/20 dark:bg-emerald-500/10' }} bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-lg font-semibold text-zinc-950 dark:text-white">{{ $notification->data['title'] ?? __('Notification') }}</p>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $notification->data['message'] ?? '-' }}</p>
                            <p class="mt-2 text-xs uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ $notification->created_at->format('d M Y H:i') }}</p>
                        </div>
                        @if (! empty($notification->data['url']))
                            <a href="{{ $notification->data['url'] }}" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
                                {{ __('Open') }}
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-zinc-300 px-4 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    {{ __('No notifications yet.') }}
                </div>
            @endforelse
        </section>

        <div>
            {{ $notifications->links() }}
        </div>
    </div>
</x-layouts::app>
