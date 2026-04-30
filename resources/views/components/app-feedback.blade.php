@php
    $validationCount = count($errors->keys());

    $toasts = collect([
        session('status') ? [
            'type' => 'success',
            'title' => __('Success'),
            'message' => session('status'),
        ] : null,
        session('error') ? [
            'type' => 'error',
            'title' => __('Attention needed'),
            'message' => session('error'),
        ] : null,
        session('warning') ? [
            'type' => 'warning',
            'title' => __('Warning'),
            'message' => session('warning'),
        ] : null,
        session('info') ? [
            'type' => 'info',
            'title' => __('Information'),
            'message' => session('info'),
        ] : null,
    ])->filter()->values();

    if ($errors->any()) {
        $toasts->prepend([
            'type' => 'error',
            'title' => __('Please review the highlighted fields before saving.'),
            'message' => __('There are :count fields that need attention.', ['count' => $validationCount]),
        ]);
    }

    $errorMessages = collect($errors->messages())
        ->map(fn (array $messages) => $messages[0] ?? null)
        ->filter()
        ->all();

    $payload = [
        'toasts' => $toasts,
        'errors' => $errorMessages,
        'summary' => $errors->any() ? [
            'title' => __('Please review the highlighted fields before saving.'),
            'message' => __('There are :count fields that need attention.', ['count' => $validationCount]),
        ] : null,
    ];
@endphp

<div
    data-app-feedback
    data-payload='@json($payload)'
    class="hidden"
    aria-hidden="true"
></div>

<div
    data-app-toast-viewport
    class="pointer-events-none fixed inset-x-0 top-4 z-[120] flex flex-col items-center gap-3 px-4 sm:items-end sm:px-6"
    aria-live="polite"
    aria-atomic="true"
></div>
