<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'HARIS') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-950 text-white antialiased">
        <main class="relative isolate overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.28),_transparent_28%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.20),_transparent_32%),linear-gradient(180deg,_#020617,_#111827_45%,_#030712)]"></div>
            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col justify-between px-6 py-10 lg:px-10">
                <header class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-emerald-300">HARIS</p>
                        <p class="mt-2 text-sm text-slate-300">{{ __('Human resource information system with a modular architecture ready for payroll.') }}</p>
                    </div>

                    <nav class="flex items-center gap-3 text-sm">
                        <x-language-switcher class="hidden sm:flex" />

                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-full bg-white px-4 py-2 font-medium text-zinc-950 transition hover:bg-emerald-200">{{ __('Dashboard') }}</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-full border border-white/20 px-4 py-2 font-medium text-white transition hover:border-white/60">{{ __('Log in') }}</a>
                        @endauth
                    </nav>
                </header>

                <section class="grid gap-10 py-12 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                    <div class="max-w-3xl">
                        <div class="mb-4 sm:hidden">
                            <x-language-switcher />
                        </div>

                        <p class="text-sm uppercase tracking-[0.32em] text-cyan-300">Laravel 13 + Livewire</p>
                        <h1 class="mt-5 text-5xl font-semibold tracking-tight text-white sm:text-6xl">
                            {{ __('Enterprise HRIS blueprint built as an operational foundation.') }}
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg">
                            {{ __('This project already provides modular routes, main domain pages, a payroll snapshot schema, and payroll period API endpoints aligned with the README.') }}
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}" class="rounded-full bg-emerald-400 px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300">{{ __('Open dashboard') }}</a>
                            @else
                                <a href="{{ route('register') }}" class="rounded-full bg-emerald-400 px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300">{{ __('Create account') }}</a>
                                <a href="{{ route('login') }}" class="rounded-full border border-white/20 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/60">{{ __('Log in') }}</a>
                            @endauth
                        </div>
                    </div>

                    <div class="grid gap-4">
                        <article class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">{{ __('Architecture') }}</p>
                            <p class="mt-3 text-2xl font-semibold">{{ __('Modular monolith') }}</p>
                            <p class="mt-2 text-sm text-slate-300">{{ __('Domain code is split into `modules/` with focused routes, queries, actions, services, and models.') }}</p>
                        </article>
                        <article class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Payroll</p>
                            <p class="mt-3 text-2xl font-semibold">{{ __('Snapshot safe') }}</p>
                            <p class="mt-2 text-sm text-slate-300">{{ __('Payroll, BPJS, tax, run item, and payslip tables are designed to preserve history safely.') }}</p>
                        </article>
                        <article class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">API</p>
                            <p class="mt-3 text-2xl font-semibold">{{ __('Versioned') }}</p>
                            <p class="mt-2 text-sm text-slate-300">{{ __('The initial endpoint is available at `GET /api/v1/payroll-periods` with a consistent response envelope.') }}</p>
                        </article>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
