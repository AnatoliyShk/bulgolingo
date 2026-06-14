<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=pt-serif:400,700|pt-sans:400,700|old-standard-tt:400,400i,700|caveat:400,600&subset=latin,cyrillic&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <script>
        (function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.classList.add(t);})();
    </script>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
