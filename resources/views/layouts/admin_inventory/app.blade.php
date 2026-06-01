<!DOCTYPE html>
<html class="scroll-smooth" lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Gentle Living')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-tab.png?v=1') }}">
    <meta property="og:image" content="{{ asset('images/logo-tab.png') }}">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons.min.css') }}">
    <!-- Public Sans Font (Sneat style) -->
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <!-- Vite CSS & JS (includes Tailwind) -->
    <script src="{{  asset('js/tailwind.js')  }}"></script>
    <style type="text/tailwindcss">
        @theme {
            --color-brand-50: #f0f9f9;
            --color-brand-100: #d9f2f1;
            --color-brand-200: #b7e6e4;
            --color-brand-300: #87d4d0;
            --color-brand-400: #56bbb6;
            --color-brand-500: #528b89;
            --color-brand-600: #446b6a;
            --color-brand-700: #3a5756;
            --color-brand-800: #324947;
            --color-brand-900: #2d3e3d;

            --font-public: "Public Sans", sans-serif;
        }
        body {
            font-family: 'Public Sans', sans-serif;
            background-color: #F5F5F9;
            color: #697a8d;
        }
        .sneat-shadow {
            box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
        }
        .sneat-card {
            background-color: #fff;
            background-clip: padding-box;
            border: 0 solid #d9dee3;
            border-radius: 0.5rem;
            box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
        }
    </style>

    <script src="{{ asset('js/carousel.js') }}"></script>
    
    <!-- jQuery & Select2 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            padding: 6px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
    </style>
</head>

<body>
    <div class="flex h-screen overflow-hidden">
        
        @include('layouts.admin_inventory.sidebar')

        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden w-full transition-all duration-300 bg-[#F5F5F9]" id="main-wrapper">
            
            <!-- Topbar (Sticky Full Width) -->
            <div class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200">
                @include('layouts.admin_inventory.topbar')
            </div>

            <!-- Content Area -->
            <main class="flex-grow p-6 w-full max-w-7xl mx-auto">
                <!-- Success/Error Messages -->
                @if (session('success'))
                    <div class="mb-4 bg-[#e8fadf] text-[#71dd37] p-4 rounded-lg flex items-start gap-3 sneat-shadow" id="successAlert">
                        <i class="bi bi-check-circle-fill text-xl"></i>
                        <div class="flex-1">
                            <p class="font-medium text-sm mt-0.5">{{ session('success') }}</p>
                        </div>
                    </div>
                    <script>
                        setTimeout(function() {
                            const alert = document.getElementById('successAlert');
                            if (alert) {
                                alert.style.opacity = '0';
                                alert.style.transform = 'translateY(-10px)';
                                alert.style.transition = 'all 0.3s ease';
                                setTimeout(() => alert.remove(), 300);
                            }
                        }, 4000);
                    </script>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Global Notification Container -->
    <div id="globalNotificationContainer" class="fixed top-20 right-4 z-[9999] space-y-3 max-w-md w-full"></div>

    <script>
        // Global Notification function
        window.showNotification = function(message, type = 'success') {
            const container = document.getElementById('globalNotificationContainer');
            if (!container) return;
            
            const notification = document.createElement('div');
            
            let bgColor = 'bg-white';
            let textColor = 'text-slate-600';
            let icon = '<i class="bi bi-info-circle text-xl text-blue-500"></i>';
            let borderColor = 'border-l-4 border-blue-500';
            
            if (type === 'success') {
                icon = '<i class="bi bi-check-circle-fill text-xl text-[#71dd37]"></i>';
                borderColor = 'border-l-4 border-[#71dd37]';
            } else if (type === 'error') {
                icon = '<i class="bi bi-x-circle-fill text-xl text-[#ff3e1d]"></i>';
                borderColor = 'border-l-4 border-[#ff3e1d]';
            } else if (type === 'warning') {
                icon = '<i class="bi bi-exclamation-triangle-fill text-xl text-[#ffab00]"></i>';
                borderColor = 'border-l-4 border-[#ffab00]';
            }
            
            notification.className = `${bgColor} ${textColor} ${borderColor} rounded-lg sneat-shadow p-4 flex items-start gap-3 transition-all duration-300`;
            
            notification.innerHTML = `
                ${icon}
                <div class="flex-1">
                    <p class="font-medium text-sm mt-0.5">${message}</p>
                </div>
            `;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-10px)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        };
    </script>

    {{-- Page-level modals (pushed from child views to ensure body-level rendering) --}}
    @stack('modals')
</body>
</html>