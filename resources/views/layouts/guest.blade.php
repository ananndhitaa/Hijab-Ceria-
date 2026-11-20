<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login - Hijab ERP' }}</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#8E1B5C',
                            dark:    '#68113F',
                            deep:    '#520C31',
                            medium:  '#701D49',
                            active:  '#7F345C',
                            light:   '#F7E6EE',
                        },
                        maroon: {
                            DEFAULT: '#8E1B5C',
                            dark:    '#68113F',
                            deep:    '#520C31',
                            medium:  '#701D49',
                            active:  '#7F345C',
                            light:   '#F7E6EE',
                        },
                        nayara: {
                            deep:            '#520C31',
                            burgundy:        '#68113F',
                            primary:         '#8E1B5C',
                            'primary-medium':'#701D49',
                            'sidebar-active':'#7F345C',
                            pink:            '#D96B9B',
                            blush:           '#F7E6EE',
                            border:          '#E1CFD9',
                            background:      '#FFF9FB',
                            white:           '#FFFFFF',
                        },
                        accent: {
                            DEFAULT: '#ED3500',
                            hover:   '#D62F00',
                            light:   '#F7E6EE',
                        },
                        'soft-pink': '#F7E6EE',
                        'page-bg':   '#FFF9FB',
                        'card':      '#FFFFFF',
                        'border-c':  '#E1CFD9',
                        'text-main': '#172B4D',
                        'text-sec':  '#64748B',
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-page-bg text-text-main antialiased min-h-screen overflow-x-hidden">
    {{ $slot }}
</body>
</html>
