<?php
require __DIR__.'/app/config/config.php';
require __DIR__.'/app/models/User.php';
require __DIR__.'/app/helpers/session_helper.php';

// Check if user is already logged in
requireLogin();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP Selection Portal | Wealth Creation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    /*
         * Custom Tailwind Configuration from your existing file
         */
    tailwind.config= {
        theme: {
            extend: {
                fontFamily: {
                    'inter': ['Inter', 'sans-serif'],
                }

                ,
                colors: {
                    primary: {
                        50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc',
                            400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1',
                            800: '#075985', 900: '#0c4a6e',
                    }

                    ,
                    secondary: {
                        50: '#fdf4ff', 100: '#fae8ff', 200: '#f5d0fe', 300: '#f0abfc',
                            400: '#e879f9', 500: '#d946ef', 600: '#c026d3', 700: '#a21caf',
                            800: '#86198f', 900: '#701a75',
                    }
                }

                ,
                animation: {
                    'fade-in': 'fadeIn 0.6s ease-in-out',
                        'slide-down': 'slideDown 0.2s ease-out',
                }

                ,
                keyframes: {
                    fadeIn: {
                        '0%': {
                            opacity: '0', transform: 'translateY(20px)'
                        }

                        ,
                        '100%': {
                            opacity: '1', transform: 'translateY(0)'
                        }
                    }

                    ,
                    slideDown: {
                        '0%': {
                            opacity: '0', transform: 'translateY(-10px)'
                        }

                        ,
                        '100%': {
                            opacity: '1', transform: 'translateY(0)'
                        }
                    }
                }

                ,
            }
        }
    }

    /* Gradient and Card Styles */
    .woobs-gradient {
        background: linear-gradient(145deg, #0284c7, #0ea5e9);
    }

    .wc-gradient {
        background: linear-gradient(145deg, #a21caf, #d946ef);
    }

    .portal-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .portal-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 40px rgba(0, 0, 0, 0.2);
    }
    </style>
</head>

<body class="font-inter bg-gray-50 min-h-screen p-8">

    <div class="max-w-6xl mx-auto py-12 relative">

        <div class="absolute top-0 right-0 z-20">

            <div class="relative inline-block text-left">

                <button id="logout-button" type="button"
                    class="flex items-center space-x-2 px-4 py-2 bg-white text-secondary-600 border border-secondary-300 rounded-full font-semibold text-sm hover:bg-secondary-50 hover:text-secondary-700 transition duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-secondary-500 focus:ring-offset-2"
                    aria-expanded="false" aria-haspopup="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9"></path>
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 4H6a2 2 0 00-2 2v12a2 2 0 002 2h7"></path>
        </svg>
                    <span>Log Out</span>
                    <svg class="-mr-1 ml-2 h-5 w-5 transition-transform duration-200 transform" id="chevron-icon"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <div 
    id="logout-dropdown" 
    class="origin-top-right absolute right-0 mt-2 w-72 border border-gray-200 rounded-xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 hidden animate-slide-down p-5" 
    role="menu" 
    aria-orientation="vertical" 
    aria-labelledby="logout-button"
>
    <p class="text-sm font-medium text-gray-700 mb-3 text-center">Are you sure you want to log out?</p>
        
<div class="flex justify-center">
    <a 
        href="logout.php" 
        class="inline-flex items-center justify-center gap-2 w-auto px-6 py-3 text-base font-semibold text-white bg-red-600 rounded-lg shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200"
        role="menuitem"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9"></path>
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 4H6a2 2 0 00-2 2v12a2 2 0 002 2h7"></path>
        </svg>
        Yes, Log Out
    </a>
</div>

    
</div>
            </div>
        </div>

        <header class="text-center mb-16">
            <h1 class="text-5xl font-extrabold text-gray-900 mb-3">
                <span class="text-primary-600">ERP</span> Access Portal
            </h1>
            <p class="text-xl text-gray-600">Select the Enterprise Resource Planning system or quick action.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 animate-fade-in mb-20">

            <a href="#"
                class="portal-card flex flex-col p-10 rounded-3xl woobs-gradient text-white hover:ring-8 ring-primary-200/50 transition duration-300 transform">

                <div class="flex-grow flex items-center space-x-6">
                    <div class="flex-shrink-0 w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6">
                            </path>
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-4xl font-bold mb-1">Woobs ERP</h2>
                        <p class="text-primary-100 text-lg">
                            Core operations, administration, and resource management.
                        </p>
                    </div>
                </div>
                <p class="mt-6 text-sm text-right font-medium text-white/80">
                    Click to access →
                </p>
            </a>

            <a href="<?php echo (APP_URL . '/wealth-creation/index.php') ?>"
                class="portal-card flex flex-col p-10 rounded-3xl wc-gradient text-white hover:ring-8 ring-secondary-200/50 transition duration-300 transform">

                <div class="flex-grow flex items-center space-x-6">
                    <div class="flex-shrink-0 w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2M20 12h2m-2 0h-4m-7 0H2m18 0a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-4xl font-bold mb-1">Wealth Creation</h2>
                        <p class="text-secondary-100 text-lg">
                            Financial tracking, investment portfolio, and growth metrics.
                        </p>
                    </div>
                </div>
                <p class="mt-6 text-sm text-right font-medium text-white/80">
                    Click to access →
                </p>
            </a>
        </div>

        <h3 class="text-3xl font-extrabold text-gray-800 text-center mb-8">Staff Quick Links</h3>
        <div class="flex justify-center flex-wrap gap-6">

            <a href="#"
                class="flex flex-col items-center justify-center w-48 h-32 bg-primary-50 text-primary-700 border-2 border-primary-300 rounded-xl hover:bg-primary-100 transition duration-200 shadow-lg hover:shadow-xl transform hover:scale-[1.05] group">
                <svg class="w-8 h-8 mb-2 group-hover:text-primary-800 transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20h-2m2 0h2m0 0h-2m-2 0h-2m-2 0h-2m2 0h-2M12 11V3m0 0v8m0 0l-4 4m4-4l4 4">
                    </path>
                </svg>
                <span class="text-lg font-bold">HR</span>
                <span class="text-xs font-medium text-gray-500">Personnel & Benefits</span>
            </a>

            <a href="#"
                class="flex flex-col items-center justify-center w-48 h-32 bg-secondary-50 text-secondary-700 border-2 border-secondary-300 rounded-xl hover:bg-secondary-100 transition duration-200 shadow-lg hover:shadow-xl transform hover:scale-[1.05] group">
                <svg class="w-8 h-8 mb-2 group-hover:text-secondary-800 transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <span class="text-lg font-bold">Requisition</span>
                <span class="text-xs font-medium text-gray-500">Purchase & Forms</span>
            </a>

            <a href="#"
                class="flex flex-col items-center justify-center w-48 h-32 bg-emerald-50 text-emerald-700 border-2 border-emerald-300 rounded-xl hover:bg-emerald-100 transition duration-200 shadow-lg hover:shadow-xl transform hover:scale-[1.05] group">
                <svg class="w-8 h-8 mb-2 group-hover:text-emerald-800 transition-colors" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                <span class="text-lg font-bold">Leave Form</span>
                <span class="text-xs font-medium text-gray-500">Time-off Request</span>
            </a>
        </div>

        <footer class="text-center mt-20 text-gray-500 text-sm">
            © <?php echo date('Y'); ?> Wealth Creation ERP | Powered by Woobs Resources IT
        </footer>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const logoutButton = document.getElementById('logout-button');
        const logoutDropdown = document.getElementById('logout-dropdown');
        const chevronIcon = document.getElementById('chevron-icon');

        // Function to toggle the dropdown state
        const toggleDropdown = () => {
            const isHidden = logoutDropdown.classList.toggle('hidden');
            logoutButton.setAttribute('aria-expanded', !isHidden);

            // Toggle chevron rotation
            if (isHidden) {
                chevronIcon.classList.remove('rotate-180');
            } else {
                chevronIcon.classList.add('rotate-180');
            }
        };

        // 1. Toggle when the Log Out button is clicked
        logoutButton.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleDropdown();
        });

        // 2. Hide the dropdown when clicking anywhere else on the page
        document.addEventListener('click', (event) => {
            if (!logoutButton.contains(event.target) && !logoutDropdown.contains(event.target) && !
                logoutDropdown.classList.contains('hidden')) {
                toggleDropdown();
            }
        });
    });
    </script>
</body>

</html>