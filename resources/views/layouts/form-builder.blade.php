<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="save-form-url" content="{{ $saveFormUrl ?? route('forms.store') }}">
    <meta name="save-form-method" content="{{ $saveFormMethod ?? 'POST' }}">
    <meta name="form-mode" content="{{ $formMode ?? 'create' }}">
    <meta name="form-id" content="{{ $formId ?? '' }}">
    <meta name="dashboard-url" content="{{ route('dashboard') }}">
    <meta name="rule-preset-url" content="{{ route('form-rule-presets.store') }}">
    <title>@yield('title', 'hb-ku')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @vite('resources/js/form-builder.js')
</head>

<body class="bg-gray-50">
    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>