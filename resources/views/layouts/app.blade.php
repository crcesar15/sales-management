@php
    use App\Models\Setting;

    $settings = Setting::all();
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- user ID -->
    <meta name="user" content="{{ Auth::user() }}">
    @foreach ($settings as $setting)
        <meta name="{{ $setting->key }}" content="{{ $setting->value }}">
    @endforeach

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link href="https://fonts.cdnfonts.com/css/lato" rel="stylesheet">

    <!-- CSS yield -->
    @yield('css')

    @routes

    <!-- Scripts -->
    @vite(['resources/sass/app.scss'])
    @inertiaHead
</head>

<body>
    @inertia
</body>

@vite(['resources/js/app.js'])

</html>
