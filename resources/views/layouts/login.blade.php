<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link href="https://fonts.cdnfonts.com/css/lato" rel="stylesheet">

    @yield('css')

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/login/index.js'])
</head>

<body>
    <div id="app">
        <main class="py-4 md:mx-4 lg:mx-4 mx-0">
            @yield('content')
        </main>
    </div>
</body>

</html>
