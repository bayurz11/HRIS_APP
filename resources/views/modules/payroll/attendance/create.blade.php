<x-layouts::app :title="__('Create Attendance')">
    <div class="mx-auto w-full max-w-5xl">
        <div class="rounded-3xl border border-zinc-200/70 bg-white p-6 shadow-sm shadow-zinc-950/5 dark:border-zinc-700 dark:bg-zinc-900">
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Create attendance record') }}</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Enter daily attendance and approved overtime.') }}</p>
            <div class="mt-6">
                @include('modules.payroll.attendance._form')
            </div>
        </div>
    </div>
</x-layouts::app>
