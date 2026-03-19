<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $settings = \App\Models\SystemSetting::all()->pluck('value', 'key');
            $appName = $settings['app_name'] ?? config('app.name', 'Comedor System');
            $favicon = $settings['favicon'] ?? 'favicon.ico';
            $faviconUrl = (str_contains($favicon, '.') && !str_contains($favicon, '/')) ? asset($favicon) : asset('storage/' . $favicon);
        @endphp

        <title inertia>{{ $appName }}</title>
        <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Dynamic Branding -->
        @php
            $settings = \App\Models\SystemSetting::all()->pluck('value', 'key');
            $primaryLight = $settings['color_primary_light'] ?? '#4f46e5';
            $primaryDark = $settings['color_primary_dark'] ?? '#818cf8';
        @endphp
        <style>
            :root {
                --brand-primary: {{ $primaryLight }};
            }
            .dark {
                --brand-primary: {{ $primaryDark }};
            }
            /* Override Tailwind Indigo with Brand Primary if needed, but let's keep it subtle for now */
        </style>

        <!-- Scripts -->
        <script>
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
