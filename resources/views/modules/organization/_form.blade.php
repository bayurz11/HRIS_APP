@php
    $isEditing = $organization->exists;
@endphp

<form method="POST" action="{{ $isEditing ? route('organization.update', $organization) : route('organization.store') }}" class="space-y-6">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="code" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Code') }}</label>
            <input id="code" name="code" type="text" value="{{ old('code', $organization->code) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
        </div>

        <div>
            <label for="type" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Type') }}</label>
            <input id="type" name="type" type="text" value="{{ old('type', $organization->type) }}" placeholder="{{ __('Headquarter, Branch, Division') }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        </div>
    </div>

    <div>
        <label for="name" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Name') }}</label>
        <input id="name" name="name" type="text" value="{{ old('name', $organization->name) }}" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" required>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="parent_id" class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Parent organization') }}</label>
            <select id="parent_id" name="parent_id" class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <option value="">{{ __('No parent') }}</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $organization->parent_id) == $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $organization->is_active)) class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500">
            <label for="is_active" class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Active organization') }}</label>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-full bg-zinc-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
            {{ $isEditing ? __('Update organization') : __('Create organization') }}
        </button>
        <a href="{{ route('organization.index') }}" class="rounded-full border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-800 transition hover:border-zinc-950 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white">
            {{ __('Cancel') }}
        </a>
    </div>
</form>
