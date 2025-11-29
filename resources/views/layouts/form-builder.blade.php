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
    <meta name="form-rules-save-url" content="{{ !empty($formId) ? route('forms.rules.store', $formId) : '' }}">
    <meta name="dashboard-url" content="{{ route('dashboard') }}">
    <title>@yield('title', 'hb-ku')</title>
    <!-- Google Fonts API -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600;700&family=Poppins:wght@400;500;600;700&family=Lato:wght@400;700&family=Montserrat:wght@400;600;700&family=Raleway:wght@400;600;700&family=Ubuntu:wght@400;500;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
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