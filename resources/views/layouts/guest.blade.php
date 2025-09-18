<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AERGAS System') }}</title>
    <link rel="icon" href="{{ asset('assets/AERGAS_PNG.png') }}" type="image/png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'aergas-navy': '#1e3a5f',
                        'aergas-orange': '#ff6b35',
                        'aergas-light-blue': '#f0f4f8',
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 50%, #1a202c 100%);
        }
    </style>
</head>
<body class="font-sans text-gray-900 antialiased min-h-screen">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <!-- Header Logo Section -->
        <div class="mb-8">
            <div class="flex items-center justify-center gap-4">
                <img src="{{ asset('assets/CGP.png') }}"
                     alt="CGP Logo"
                     class="w-16 h-auto filter drop-shadow-lg bg-white bg-opacity-10 rounded-xl p-3 backdrop-blur-sm border border-gray-300 border-opacity-40">
                <img src="{{ asset('assets/AERGAS_PNG.png') }}"
                     alt="AERGAS Logo"
                     class="w-32 h-auto filter drop-shadow-lg bg-white bg-opacity-90 rounded-xl p-3 backdrop-blur-sm">
            </div>
        </div>

        <!-- Content Container -->
        <div class="w-full sm:max-w-md">
            {{ $slot }}
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-300">
                &copy; {{ date('Y') }} AERGAS System. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
