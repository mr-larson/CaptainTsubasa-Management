<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @if (($page['component'] ?? null) === 'Welcome')
            {{-- Rendu serveur pour les robots qui n'exécutent pas le JS ; Inertia reprend la main via les head-key. --}}
            <title inertia>Jeu de gestion de football au tour par tour - Golden Eleven</title>
            <meta inertia="description" name="description" content="Golden Eleven, jeu de gestion de football au tour par tour sur navigateur, inspiré de Captain Tsubasa : recrute, entraîne ton équipe et choisis chaque action en match. Gratuit.">
            <link inertia="canonical" rel="canonical" href="{{ url('/') }}">
        @else
            <title inertia>{{ config('app.name', 'Golden Eleven') }}</title>
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="icon" href="/favicon.svg" type="image/svg+xml"/>
        <link rel="alternate icon" type="image/png" href="/images/logo.png"/>
        <link rel="apple-touch-icon" href="/images/logo.png"/>


        <!-- Scripts -->
        @routes(auth()->user()?->is_admin ? null : 'player')
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-700">
        @inertia
    </body>
</html>
