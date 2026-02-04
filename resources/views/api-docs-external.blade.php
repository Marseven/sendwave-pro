<!DOCTYPE html>
<html lang="fr" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SendWave Pro - API Documentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef7ff',
                            100: '#d9edff',
                            200: '#bce0ff',
                            300: '#8ecdff',
                            400: '#59b0ff',
                            500: '#338dff',
                            600: '#1b6cf5',
                            700: '#1457e1',
                            800: '#1746b6',
                            900: '#193d8f',
                            950: '#142757',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        html { scroll-behavior: smooth; }
        .sidebar-link.active {
            background-color: rgba(59, 130, 246, 0.1);
            color: #338dff;
            border-left-color: #338dff;
        }
        .dark .sidebar-link.active {
            background-color: rgba(59, 130, 246, 0.15);
        }
        pre code { white-space: pre; }
        .copy-btn { transition: all 0.2s; }
        .copy-btn.copied { background-color: #22c55e !important; color: white !important; }
        .collapsible-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .collapsible-content.open { max-height: 2000px; }
        .tab-btn.active-tab {
            border-bottom-color: #338dff;
            color: #338dff;
        }
        .dark .tab-btn.active-tab {
            border-bottom-color: #59b0ff;
            color: #59b0ff;
        }
        .admin-only-section { display: none; }
        .admin-only-section.revealed { display: block !important; }
        a.admin-only-section { display: none; }
        a.admin-only-section.revealed { display: flex !important; }
        .admin-login-modal { display: none; position: fixed; inset: 0; z-index: 100; }
        .admin-login-modal.open { display: flex; }
        @media (max-width: 1023px) {
            .sidebar-overlay { display: none; }
            .sidebar-overlay.open { display: block; }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased">

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay fixed inset-0 bg-black/50 z-40 lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed top-0 left-0 z-50 w-72 h-full bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 overflow-y-auto">
        <div class="p-6 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-brand-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white">SendWave Pro</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">API Documentation</p>
                </div>
            </div>
        </div>
        <nav class="p-4 space-y-1">
            <a href="#introduction" class="sidebar-link flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                Introduction
            </a>
            <a href="#authentication" class="sidebar-link flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                Authentification
            </a>
            <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Endpoints</p>
            <a href="#send-otp" class="sidebar-link flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <span class="inline-flex items-center justify-center w-11 h-5 text-[10px] font-bold rounded bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">POST</span>
                Send OTP
            </a>
            <a href="#send-sms" class="sidebar-link flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <span class="inline-flex items-center justify-center w-11 h-5 text-[10px] font-bold rounded bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">POST</span>
                Send SMS
            </a>
            <a href="#contacts" class="sidebar-link flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <span class="inline-flex items-center justify-center w-11 h-5 text-[10px] font-bold rounded bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400">CRUD</span>
                Contacts
            </a>
            <a href="#analyze" class="sidebar-link admin-only-section flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <span class="inline-flex items-center justify-center w-11 h-5 text-[10px] font-bold rounded bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">POST</span>
                Analyze
            </a>
            <a href="#history" class="sidebar-link admin-only-section flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <span class="inline-flex items-center justify-center w-11 h-5 text-[10px] font-bold rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">GET</span>
                History
            </a>
            <a href="#stats" class="sidebar-link admin-only-section flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <span class="inline-flex items-center justify-center w-11 h-5 text-[10px] font-bold rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">GET</span>
                Stats
            </a>
            <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Reference</p>
            <a href="#error-codes" class="sidebar-link flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                Codes d'erreur
            </a>
            <a href="#rate-limits" class="sidebar-link flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                Rate Limits
            </a>
            <a href="#ip-whitelisting" class="sidebar-link admin-only-section flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                IP Whitelisting
            </a>
            <a href="#code-examples" class="sidebar-link admin-only-section flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" /></svg>
                Exemples de code
            </a>
            <a href="#permissions" class="sidebar-link admin-only-section flex items-center gap-3 px-3 py-2 text-sm rounded-lg border-l-2 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                Permissions
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="lg:ml-72">
        <!-- Top Header -->
        <header class="sticky top-0 z-30 bg-white/80 dark:bg-gray-900/80 backdrop-blur-lg border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16">
                <div class="flex items-center gap-3">
                    <!-- Hamburger -->
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">SendWave Pro <span class="hidden sm:inline">- API Documentation</span></h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 dark:bg-brand-900/40 text-brand-700 dark:text-brand-400">v3.1</span>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Admin Login/Logout -->
                    <button onclick="openAdminLogin()" id="adminLoginBtn" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition-colors">Se connecter</button>
                    <button onclick="adminLogout()" id="adminLogoutBtn" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors" style="display:none;">Deconnexion</button>
                    <!-- Dark Mode Toggle -->
                    <button onclick="toggleDarkMode()" id="darkModeBtn" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Basculer le mode sombre">
                        <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
                        <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

            <!-- Introduction -->
            <section id="introduction" class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Introduction</h2>
                <div class="prose dark:prose-invert max-w-none">
                    <p class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                        Bienvenue dans la documentation de l'API SendWave Pro. Cette API REST vous permet d'envoyer des SMS transactionnels (OTP, notifications) et promotionnels vers les numeros gabonais (Airtel et Moov).
                    </p>
                </div>
                <div class="mt-6 grid sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                        <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Format JSON</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Toutes les requetes et reponses utilisent le format JSON.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Operateurs</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Airtel (74, 76, 77) et Moov (60, 62, 65, 66) supportes.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                        <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Tarification</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">20 FCFA par SMS. Budget et credits controles automatiquement.</p>
                    </div>
                </div>
            </section>

            <!-- Authentication -->
            <section id="authentication" class="mb-16">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Authentification</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Toutes les requetes API doivent inclure votre cle API dans le header <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-sm font-mono text-brand-600 dark:text-brand-400">X-API-Key</code>. Vous pouvez obtenir votre cle API depuis le tableau de bord SendWave Pro, section <strong>Integrations API</strong>.
                </p>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Header d'authentification</span>
                        <button onclick="copyToClipboard('X-API-Key: YOUR_API_KEY', this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                    </div>
                    <div class="p-5 bg-gray-950">
                        <code class="text-sm text-green-400 font-mono">X-API-Key: YOUR_API_KEY</code>
                    </div>
                </div>

                <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                        <div>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Important</p>
                            <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">Ne partagez jamais votre cle API. Si elle est compromise, regenerez-la immediatement depuis le tableau de bord.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Base URL</h3>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">URL de base</span>
                            <button onclick="copyToClipboard('http://161.35.159.160/api', this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                        </div>
                        <div class="p-5 bg-gray-950">
                            <code class="text-sm text-green-400 font-mono">http://161.35.159.160/api</code>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Endpoint: Send OTP -->
            <section id="send-otp" class="mb-16">
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">POST</span>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">/api/messages/send-otp</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Envoyer un SMS OTP (One-Time Password) a un destinataire unique. Ideal pour la verification de numero, la confirmation de connexion ou la validation de transaction.
                </p>

                <!-- Request Body -->
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Corps de la requete</h3>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden mb-6">
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">application/json</span>
                        <button onclick="copyToClipboard(document.getElementById('send-otp-body').textContent, this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                    </div>
                    <pre class="p-5 bg-gray-950 overflow-x-auto"><code id="send-otp-body" class="text-sm text-gray-300 font-mono">{
  "recipient": "+24177123456",
  "message": "Votre code: 1234",
  "reference": "otp-login-12345"
}</code></pre>
                </div>

                <!-- Parameters Table -->
                <div class="overflow-x-auto mb-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Parametre</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Type</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Requis</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">recipient</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">string</td>
                                <td class="py-3 px-4"><span class="text-green-600 dark:text-green-400 font-medium">Oui</span></td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Numero au format E.164 (+241...)</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">message</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">string</td>
                                <td class="py-3 px-4"><span class="text-green-600 dark:text-green-400 font-medium">Oui</span></td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Contenu du SMS (max 160 caracteres)</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">reference</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">string</td>
                                <td class="py-3 px-4"><span class="text-gray-400">Non</span></td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Reference interne pour le suivi</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Success Response -->
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Reponse succes</h3>
                <div class="mb-6">
                    <button onclick="toggleCollapsible('send-otp-success')" class="w-full flex items-center justify-between px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">200</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">SMS envoye avec succes</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" id="send-otp-success-icon" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div id="send-otp-success" class="collapsible-content">
                        <div class="mt-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">application/json</span>
                                <button onclick="copyToClipboard(document.getElementById('send-otp-success-body').textContent, this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                            </div>
                            <pre class="p-5 bg-gray-950 overflow-x-auto"><code id="send-otp-success-body" class="text-sm text-gray-300 font-mono">{
  "success": true,
  "data": {
    "message_id": 42,
    "recipient": "+24177123456",
    "status": "sent",
    "provider": "airtel",
    "cost": 20,
    "reference": "otp-login-12345"
  }
}</code></pre>
                        </div>
                    </div>
                </div>

                <!-- Error Responses -->
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Reponses d'erreur</h3>
                <div class="space-y-2">
                    <!-- 400 BLACKLISTED -->
                    <button onclick="toggleCollapsible('otp-err-blacklisted')" class="w-full flex items-center justify-between px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">400</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">BLACKLISTED - Numero dans la liste noire</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div id="otp-err-blacklisted" class="collapsible-content">
                        <div class="mt-1 mb-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                            <pre class="p-5 bg-gray-950 overflow-x-auto"><code class="text-sm text-gray-300 font-mono">{
  "success": false,
  "error": "BLACKLISTED",
  "message": "Le numero +24177123456 est dans la liste noire."
}</code></pre>
                        </div>
                    </div>

                    <!-- 400 SEND_FAILED -->
                    <button onclick="toggleCollapsible('otp-err-sendfailed')" class="w-full flex items-center justify-between px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">400</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">SEND_FAILED - Echec d'envoi</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div id="otp-err-sendfailed" class="collapsible-content">
                        <div class="mt-1 mb-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                            <pre class="p-5 bg-gray-950 overflow-x-auto"><code class="text-sm text-gray-300 font-mono">{
  "success": false,
  "error": "SEND_FAILED",
  "message": "L'envoi du SMS a echoue. Veuillez reessayer."
}</code></pre>
                        </div>
                    </div>

                    <!-- 403 BUDGET_EXCEEDED -->
                    <button onclick="toggleCollapsible('otp-err-budget')" class="w-full flex items-center justify-between px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400">403</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">BUDGET_EXCEEDED - Budget mensuel depasse</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div id="otp-err-budget" class="collapsible-content">
                        <div class="mt-1 mb-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                            <pre class="p-5 bg-gray-950 overflow-x-auto"><code class="text-sm text-gray-300 font-mono">{
  "success": false,
  "error": "BUDGET_EXCEEDED",
  "message": "Le budget mensuel a ete depasse. Contactez votre administrateur."
}</code></pre>
                        </div>
                    </div>

                    <!-- 403 CREDITS_EXCEEDED -->
                    <button onclick="toggleCollapsible('otp-err-credits')" class="w-full flex items-center justify-between px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400">403</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">CREDITS_EXCEEDED - Credits insuffisants</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div id="otp-err-credits" class="collapsible-content">
                        <div class="mt-1 mb-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                            <pre class="p-5 bg-gray-950 overflow-x-auto"><code class="text-sm text-gray-300 font-mono">{
  "success": false,
  "error": "CREDITS_EXCEEDED",
  "message": "Credits SMS insuffisants. Rechargez votre compte."
}</code></pre>
                        </div>
                    </div>

                    <!-- 401 -->
                    <button onclick="toggleCollapsible('otp-err-401')" class="w-full flex items-center justify-between px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">401</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">UNAUTHORIZED - Cle API invalide</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div id="otp-err-401" class="collapsible-content">
                        <div class="mt-1 mb-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                            <pre class="p-5 bg-gray-950 overflow-x-auto"><code class="text-sm text-gray-300 font-mono">{
  "success": false,
  "error": "UNAUTHORIZED",
  "message": "Cle API invalide ou manquante."
}</code></pre>
                        </div>
                    </div>

                    <!-- 422 -->
                    <button onclick="toggleCollapsible('otp-err-422')" class="w-full flex items-center justify-between px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400">422</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">VALIDATION_ERROR - Donnees invalides</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div id="otp-err-422" class="collapsible-content">
                        <div class="mt-1 mb-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                            <pre class="p-5 bg-gray-950 overflow-x-auto"><code class="text-sm text-gray-300 font-mono">{
  "success": false,
  "error": "VALIDATION_ERROR",
  "message": "Les donnees fournies sont invalides.",
  "errors": {
    "recipient": ["Le champ recipient est obligatoire."],
    "message": ["Le champ message est obligatoire."]
  }
}</code></pre>
                        </div>
                    </div>

                    <!-- 500 -->
                    <button onclick="toggleCollapsible('otp-err-500')" class="w-full flex items-center justify-between px-5 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">500</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">SERVER_ERROR - Erreur interne</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div id="otp-err-500" class="collapsible-content">
                        <div class="mt-1 mb-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                            <pre class="p-5 bg-gray-950 overflow-x-auto"><code class="text-sm text-gray-300 font-mono">{
  "success": false,
  "error": "SERVER_ERROR",
  "message": "Une erreur interne est survenue. Veuillez reessayer plus tard."
}</code></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Endpoint: Send SMS -->
            <section id="send-sms" class="mb-16">
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">POST</span>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">/api/messages/send</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Envoyer un SMS a un ou plusieurs destinataires. Vous pouvez specifier les numeros directement, utiliser des contacts existants ou cibler des groupes entiers. Au moins un parametre parmi <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-sm font-mono">recipients</code>, <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-sm font-mono">contact_ids</code> ou <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-sm font-mono">group_ids</code> est requis.
                </p>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Corps de la requete</h3>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden mb-6">
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">application/json</span>
                        <button onclick="copyToClipboard(document.getElementById('send-sms-body').textContent, this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                    </div>
                    <pre class="p-5 bg-gray-950 overflow-x-auto"><code id="send-sms-body" class="text-sm text-gray-300 font-mono">{
  "recipients": ["+24177123456", "+24162987654"],
  "message": "Hello!",
  "contact_ids": [1, 2],
  "group_ids": [1]
}</code></pre>
                </div>

                <div class="overflow-x-auto mb-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Parametre</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Type</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Requis</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">recipients</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">array</td>
                                <td class="py-3 px-4"><span class="text-gray-400">Conditionnel</span></td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Liste de numeros au format E.164</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">message</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">string</td>
                                <td class="py-3 px-4"><span class="text-green-600 dark:text-green-400 font-medium">Oui</span></td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Contenu du SMS. Supporte les variables {nom}, {prenom}, {custom.*}</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">contact_ids</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">array</td>
                                <td class="py-3 px-4"><span class="text-gray-400">Conditionnel</span></td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Liste d'IDs de contacts existants</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">group_ids</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">array</td>
                                <td class="py-3 px-4"><span class="text-gray-400">Conditionnel</span></td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Liste d'IDs de groupes de contacts</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                        <div>
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Note</p>
                            <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">Au moins un des parametres <code class="font-mono">recipients</code>, <code class="font-mono">contact_ids</code> ou <code class="font-mono">group_ids</code> doit etre fourni. Les doublons sont automatiquement elimines et les numeros en liste noire sont filtres.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Endpoint: Contacts -->
            <section id="contacts" class="mb-16">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Contacts</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Gerer vos contacts via l'API : lister, creer, consulter, modifier et supprimer des contacts.
                </p>

                <!-- GET /api/contacts -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">GET</span>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white font-mono">/api/contacts</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Lister tous vos contacts avec pagination. Filtrable par nom, telephone ou groupe.
                    </p>
                    <div class="overflow-x-auto mb-4">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-800">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Parametre</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Type</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr>
                                    <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">page</td>
                                    <td class="py-3 px-4 text-gray-500 dark:text-gray-400">integer</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Numero de page (defaut: 1)</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">per_page</td>
                                    <td class="py-3 px-4 text-gray-500 dark:text-gray-400">integer</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Resultats par page (defaut: 20, max: 100)</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">search</td>
                                    <td class="py-3 px-4 text-gray-500 dark:text-gray-400">string</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Rechercher par nom, prenom ou telephone</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- POST /api/contacts -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">POST</span>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white font-mono">/api/contacts</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Creer un nouveau contact.
                    </p>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden mb-4">
                        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">application/json</span>
                            <button onclick="copyToClipboard(document.getElementById('contacts-create-body').textContent, this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                        </div>
                        <pre class="p-5 bg-gray-950 overflow-x-auto"><code id="contacts-create-body" class="text-sm text-gray-300 font-mono">{
  "first_name": "Jean",
  "last_name": "Dupont",
  "phone": "+24177123456",
  "email": "jean@example.com",
  "custom_fields": {
    "ville": "Libreville",
    "categorie": "VIP"
  }
}</code></pre>
                    </div>
                    <div class="overflow-x-auto mb-4">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-800">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Parametre</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Type</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Requis</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr>
                                    <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">first_name</td>
                                    <td class="py-3 px-4 text-gray-500 dark:text-gray-400">string</td>
                                    <td class="py-3 px-4"><span class="text-green-600 dark:text-green-400 font-medium">Oui</span></td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Prenom du contact</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">last_name</td>
                                    <td class="py-3 px-4 text-gray-500 dark:text-gray-400">string</td>
                                    <td class="py-3 px-4"><span class="text-green-600 dark:text-green-400 font-medium">Oui</span></td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Nom du contact</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">phone</td>
                                    <td class="py-3 px-4 text-gray-500 dark:text-gray-400">string</td>
                                    <td class="py-3 px-4"><span class="text-green-600 dark:text-green-400 font-medium">Oui</span></td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Numero au format E.164 (+241...)</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">email</td>
                                    <td class="py-3 px-4 text-gray-500 dark:text-gray-400">string</td>
                                    <td class="py-3 px-4"><span class="text-gray-400">Non</span></td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Adresse email</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">custom_fields</td>
                                    <td class="py-3 px-4 text-gray-500 dark:text-gray-400">object</td>
                                    <td class="py-3 px-4"><span class="text-gray-400">Non</span></td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Champs personnalises (cle/valeur)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- GET /api/contacts/{id} -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">GET</span>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white font-mono">/api/contacts/{id}</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Recuperer les details d'un contact par son ID.
                    </p>
                </div>

                <!-- PUT /api/contacts/{id} -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400">PUT</span>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white font-mono">/api/contacts/{id}</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Mettre a jour un contact existant. Memes parametres que la creation (tous optionnels).
                    </p>
                </div>

                <!-- DELETE /api/contacts/{id} -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">DELETE</span>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white font-mono">/api/contacts/{id}</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Supprimer un contact par son ID.
                    </p>
                </div>
            </section>

            <!-- Endpoint: Analyze -->
            <section id="analyze" class="mb-16 admin-only-section">
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">POST</span>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">/api/messages/analyze</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Analyser un ou plusieurs numeros de telephone pour detecter l'operateur (Airtel/Moov), le pays et verifier le format.
                </p>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Corps de la requete</h3>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden mb-6">
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">application/json</span>
                        <button onclick="copyToClipboard(document.getElementById('analyze-body').textContent, this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                    </div>
                    <pre class="p-5 bg-gray-950 overflow-x-auto"><code id="analyze-body" class="text-sm text-gray-300 font-mono">{
  "phone_numbers": ["77123456", "60123456"]
}</code></pre>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Parametre</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Type</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Requis</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">phone_numbers</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">array</td>
                                <td class="py-3 px-4"><span class="text-green-600 dark:text-green-400 font-medium">Oui</span></td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Liste de numeros a analyser</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Endpoint: History -->
            <section id="history" class="mb-16 admin-only-section">
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">GET</span>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">/api/messages/history</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Recuperer l'historique des messages envoyes avec pagination. Filtrable par statut et plage de dates.
                </p>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Parametres de requete</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Parametre</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Type</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Defaut</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-900 dark:text-white">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">page</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">integer</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">1</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Numero de page</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">per_page</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">integer</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">20</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Nombre de resultats par page (max 100)</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">status</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">string</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">-</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Filtrer par statut: sent, delivered, failed, pending</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">date_from</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">date</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">-</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Date de debut (format YYYY-MM-DD)</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-mono text-brand-600 dark:text-brand-400">date_to</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">date</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">-</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400">Date de fin (format YYYY-MM-DD)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-3">Exemple de requete</h3>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">URL</span>
                        <button onclick="copyToClipboard('GET /api/messages/history?page=1&per_page=20&status=sent&date_from=2026-01-01&date_to=2026-01-31', this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                    </div>
                    <div class="p-5 bg-gray-950 overflow-x-auto">
                        <code class="text-sm text-gray-300 font-mono"><span class="text-blue-400">GET</span> /api/messages/history?page=1&amp;per_page=20&amp;status=sent&amp;date_from=2026-01-01&amp;date_to=2026-01-31</code>
                    </div>
                </div>
            </section>

            <!-- Endpoint: Stats -->
            <section id="stats" class="mb-16 admin-only-section">
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">GET</span>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">/api/messages/stats</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Obtenir les statistiques globales de vos envois SMS : nombre total, taux de livraison, repartition par operateur et cout total.
                </p>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Exemple de reponse</h3>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                    <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">application/json</span>
                        <button onclick="copyToClipboard(document.getElementById('stats-body').textContent, this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                    </div>
                    <pre class="p-5 bg-gray-950 overflow-x-auto"><code id="stats-body" class="text-sm text-gray-300 font-mono">{
  "success": true,
  "data": {
    "total_sent": 1250,
    "total_delivered": 1180,
    "total_failed": 70,
    "delivery_rate": 94.4,
    "total_cost": 25000,
    "by_operator": {
      "airtel": 850,
      "moov": 400
    }
  }
}</code></pre>
                </div>
            </section>

            <!-- Error Codes -->
            <section id="error-codes" class="mb-16">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Codes d'erreur</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Toutes les reponses d'erreur suivent le meme format avec un champ <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-sm font-mono">success: false</code> et un code d'erreur explicite.
                </p>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                                    <th class="text-left py-3 px-5 font-semibold text-gray-900 dark:text-white">HTTP</th>
                                    <th class="text-left py-3 px-5 font-semibold text-gray-900 dark:text-white">Erreur</th>
                                    <th class="text-left py-3 px-5 font-semibold text-gray-900 dark:text-white">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr>
                                    <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">401</span></td>
                                    <td class="py-3 px-5 font-mono text-sm text-gray-900 dark:text-white">UNAUTHORIZED</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Cle API invalide ou manquante</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400">403</span></td>
                                    <td class="py-3 px-5 font-mono text-sm text-gray-900 dark:text-white">BUDGET_EXCEEDED</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Budget mensuel depasse</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400">403</span></td>
                                    <td class="py-3 px-5 font-mono text-sm text-gray-900 dark:text-white">CREDITS_INSUFFICIENT</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Credits SMS insuffisants</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400">403</span></td>
                                    <td class="py-3 px-5 font-mono text-sm text-gray-900 dark:text-white">ACCOUNT_BLOCKED</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Compte suspendu ou bloque</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400">403</span></td>
                                    <td class="py-3 px-5 font-mono text-sm text-gray-900 dark:text-white">IP_NOT_ALLOWED</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Adresse IP non autorisee</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">400</span></td>
                                    <td class="py-3 px-5 font-mono text-sm text-gray-900 dark:text-white">BLACKLISTED</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Numero dans la liste noire</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">400</span></td>
                                    <td class="py-3 px-5 font-mono text-sm text-gray-900 dark:text-white">SEND_FAILED</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Echec d'envoi du SMS</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400">422</span></td>
                                    <td class="py-3 px-5 font-mono text-sm text-gray-900 dark:text-white">VALIDATION_ERROR</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Donnees invalides</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400">429</span></td>
                                    <td class="py-3 px-5 font-mono text-sm text-gray-900 dark:text-white">RATE_LIMITED</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Trop de requetes</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400">500</span></td>
                                    <td class="py-3 px-5 font-mono text-sm text-gray-900 dark:text-white">SERVER_ERROR</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Erreur interne du serveur</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Rate Limits -->
            <section id="rate-limits" class="mb-16">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Rate Limits</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Chaque cle API dispose de sa propre limite de requetes. Par defaut, la limite est fixee a <strong>100 requetes par minute</strong>. Cette valeur est configurable par l'administrateur pour chaque cle API.
                </p>

                <div class="grid sm:grid-cols-2 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Headers de reponse</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Chaque reponse inclut les headers suivants :</p>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <code class="text-xs font-mono bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-brand-600 dark:text-brand-400">X-RateLimit-Limit</code>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Limite totale</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <code class="text-xs font-mono bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-brand-600 dark:text-brand-400">X-RateLimit-Remaining</code>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Requetes restantes</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">En cas de depassement</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Vous recevrez une reponse HTTP 429 :</p>
                        <div class="bg-gray-950 rounded-lg p-3">
                            <code class="text-xs text-gray-300 font-mono">
{<br>
&nbsp;&nbsp;"success": false,<br>
&nbsp;&nbsp;"error": "RATE_LIMITED",<br>
&nbsp;&nbsp;"message": "Trop de requetes. Reessayez dans 60 secondes."<br>
}
                            </code>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                        <div>
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Conseil</p>
                            <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">Si vous avez besoin d'une limite plus elevee pour vos besoins de production, contactez l'administrateur SendWave Pro pour ajuster la configuration de votre cle API.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- IP Whitelisting -->
            <section id="ip-whitelisting" class="mb-16 admin-only-section">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">IP Whitelisting</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Pour renforcer la securite, vous pouvez configurer une liste blanche d'adresses IP autorisees pour chaque cle API. Lorsque cette fonctionnalite est activee, seules les requetes provenant des adresses IP autorisees seront acceptees.
                </p>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 mb-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Fonctionnement</h3>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <span>La fonctionnalite est <strong>optionnelle</strong>. Sans configuration, toutes les adresses IP sont acceptees.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <span>Lorsqu'elle est activee, seules les IPs configurees peuvent utiliser la cle API.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <span>Une requete provenant d'une IP non autorisee recevra une erreur <code class="font-mono bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-xs">403 IP_NOT_ALLOWED</code>.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            <span>La configuration se fait depuis le tableau de bord, section <strong>Integrations API</strong>.</span>
                        </li>
                    </ul>
                </div>

                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                        <div>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Recommandation</p>
                            <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">Pour les environnements de production, nous recommandons fortement d'activer l'IP Whitelisting afin de limiter l'acces a vos serveurs connus.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Code Examples -->
            <section id="code-examples" class="mb-16 admin-only-section">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Exemples de code</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Exemples d'integration pour l'envoi d'un SMS OTP dans differents langages de programmation.
                </p>

                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-800 mb-0">
                    <div class="flex gap-0 -mb-px overflow-x-auto">
                        <button onclick="switchCodeTab('curl')" class="tab-btn active-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors whitespace-nowrap" data-tab="curl">cURL</button>
                        <button onclick="switchCodeTab('php')" class="tab-btn px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors whitespace-nowrap" data-tab="php">PHP</button>
                        <button onclick="switchCodeTab('nodejs')" class="tab-btn px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors whitespace-nowrap" data-tab="nodejs">Node.js</button>
                        <button onclick="switchCodeTab('python')" class="tab-btn px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors whitespace-nowrap" data-tab="python">Python</button>
                    </div>
                </div>

                <!-- cURL -->
                <div id="code-curl" class="code-panel">
                    <div class="bg-white dark:bg-gray-900 border border-t-0 border-gray-200 dark:border-gray-800 rounded-b-xl overflow-hidden">
                        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">bash</span>
                            <button onclick="copyToClipboard(document.getElementById('code-curl-content').textContent, this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                        </div>
                        <pre class="p-5 bg-gray-950 overflow-x-auto"><code id="code-curl-content" class="text-sm text-gray-300 font-mono">curl -X POST http://161.35.159.160/api/messages/send-otp \
  -H 'X-API-Key: sk_live_xxxxxxxxxxxxx' \
  -H 'Content-Type: application/json' \
  -d '{"recipient": "+24177123456", "message": "Votre code: 1234", "reference": "ref-001"}'</code></pre>
                    </div>
                </div>

                <!-- PHP -->
                <div id="code-php" class="code-panel hidden">
                    <div class="bg-white dark:bg-gray-900 border border-t-0 border-gray-200 dark:border-gray-800 rounded-b-xl overflow-hidden">
                        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">php</span>
                            <button onclick="copyToClipboard(document.getElementById('code-php-content').textContent, this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                        </div>
                        <pre class="p-5 bg-gray-950 overflow-x-auto"><code id="code-php-content" class="text-sm text-gray-300 font-mono">&lt;?php

$ch = curl_init('http://161.35.159.160/api/messages/send-otp');
curl_setopt_array($ch, [
    CURLOPT_POST =&gt; true,
    CURLOPT_HTTPHEADER =&gt; [
        'X-API-Key: sk_live_xxxxxxxxxxxxx',
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS =&gt; json_encode([
        'recipient' =&gt; '+24177123456',
        'message' =&gt; 'Votre code: 1234',
        'reference' =&gt; 'ref-001',
    ]),
    CURLOPT_RETURNTRANSFER =&gt; true,
]);
$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

// Verifier le resultat
if ($data['success']) {
    echo "SMS envoye! ID: " . $data['data']['message_id'];
} else {
    echo "Erreur: " . $data['error'];
}</code></pre>
                    </div>
                </div>

                <!-- Node.js -->
                <div id="code-nodejs" class="code-panel hidden">
                    <div class="bg-white dark:bg-gray-900 border border-t-0 border-gray-200 dark:border-gray-800 rounded-b-xl overflow-hidden">
                        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">javascript</span>
                            <button onclick="copyToClipboard(document.getElementById('code-nodejs-content').textContent, this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                        </div>
                        <pre class="p-5 bg-gray-950 overflow-x-auto"><code id="code-nodejs-content" class="text-sm text-gray-300 font-mono">const response = await fetch('http://161.35.159.160/api/messages/send-otp', {
  method: 'POST',
  headers: {
    'X-API-Key': 'sk_live_xxxxxxxxxxxxx',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    recipient: '+24177123456',
    message: 'Votre code: 1234',
    reference: 'ref-001',
  }),
});
const data = await response.json();

// Verifier le resultat
if (data.success) {
  console.log(`SMS envoye! ID: ${data.data.message_id}`);
} else {
  console.error(`Erreur: ${data.error}`);
}</code></pre>
                    </div>
                </div>

                <!-- Python -->
                <div id="code-python" class="code-panel hidden">
                    <div class="bg-white dark:bg-gray-900 border border-t-0 border-gray-200 dark:border-gray-800 rounded-b-xl overflow-hidden">
                        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">python</span>
                            <button onclick="copyToClipboard(document.getElementById('code-python-content').textContent, this)" class="copy-btn px-3 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">Copier</button>
                        </div>
                        <pre class="p-5 bg-gray-950 overflow-x-auto"><code id="code-python-content" class="text-sm text-gray-300 font-mono">import requests

response = requests.post(
    'http://161.35.159.160/api/messages/send-otp',
    headers={
        'X-API-Key': 'sk_live_xxxxxxxxxxxxx',
        'Content-Type': 'application/json',
    },
    json={
        'recipient': '+24177123456',
        'message': 'Votre code: 1234',
        'reference': 'ref-001',
    },
)
data = response.json()

# Verifier le resultat
if data['success']:
    print(f"SMS envoye! ID: {data['data']['message_id']}")
else:
    print(f"Erreur: {data['error']}")</code></pre>
                    </div>
                </div>
            </section>

            <!-- Permissions -->
            <section id="permissions" class="mb-16 admin-only-section">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Permissions des cles API</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Chaque cle API est associee a un sous-compte avec des permissions specifiques. Les permissions determinent les endpoints accessibles.
                </p>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                                    <th class="text-left py-3 px-5 font-semibold text-gray-900 dark:text-white">Permission</th>
                                    <th class="text-left py-3 px-5 font-semibold text-gray-900 dark:text-white">Description</th>
                                    <th class="text-left py-3 px-5 font-semibold text-gray-900 dark:text-white">Endpoints</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr>
                                    <td class="py-3 px-5 font-mono text-brand-600 dark:text-brand-400">send_sms</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Envoyer des SMS et OTP</td>
                                    <td class="py-3 px-5 text-gray-500 dark:text-gray-400 text-xs font-mono">POST /messages/send, /messages/send-otp</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5 font-mono text-brand-600 dark:text-brand-400">view_history</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Consulter l'historique des messages</td>
                                    <td class="py-3 px-5 text-gray-500 dark:text-gray-400 text-xs font-mono">GET /messages/history, /messages/stats</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5 font-mono text-brand-600 dark:text-brand-400">manage_contacts</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Gerer les contacts</td>
                                    <td class="py-3 px-5 text-gray-500 dark:text-gray-400 text-xs font-mono">GET/POST/PUT/DELETE /contacts</td>
                                </tr>
                                <tr>
                                    <td class="py-3 px-5 font-mono text-brand-600 dark:text-brand-400">view_balance</td>
                                    <td class="py-3 px-5 text-gray-600 dark:text-gray-400">Consulter le solde et budget</td>
                                    <td class="py-3 px-5 text-gray-500 dark:text-gray-400 text-xs font-mono">GET /budgets/status</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Support -->
            <section class="mb-16">
                <div class="bg-gradient-to-br from-brand-600 to-brand-800 rounded-2xl p-8 text-white">
                    <h2 class="text-2xl font-bold mb-3">Besoin d'aide ?</h2>
                    <p class="text-brand-100 mb-6">Notre equipe technique est disponible pour vous accompagner dans l'integration de l'API SendWave Pro.</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="mailto:support@sendwave-pro.com" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-brand-700 rounded-lg font-medium text-sm hover:bg-brand-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                            support@sendwave-pro.com
                        </a>
                    </div>
                </div>
            </section>

        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    SendWave Pro &copy; 2026 - Contact: <a href="mailto:support@sendwave-pro.com" class="text-brand-600 dark:text-brand-400 hover:underline">support@sendwave-pro.com</a>
                </p>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                        API Operationnelle
                    </span>
                </div>
            </div>
        </footer>
    </div>

    <!-- Admin Login Modal -->
    <div id="adminLoginModal" class="admin-login-modal items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-800 w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Connexion Super-Admin</h3>
                <button onclick="closeAdminLogin()" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form id="adminLoginForm" onsubmit="handleAdminLogin(event)" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" id="adminEmail" required class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none" placeholder="votre@email.com" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mot de passe</label>
                    <input type="password" id="adminPassword" required class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none" placeholder="Mot de passe" />
                </div>
                <p id="adminLoginError" class="text-sm text-red-600 dark:text-red-400 hidden"></p>
                <button type="submit" id="adminLoginSubmit" class="w-full px-4 py-2.5 bg-brand-600 text-white rounded-lg font-medium text-sm hover:bg-brand-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">Se connecter</button>
            </form>
        </div>
    </div>

    <script>
        // Dark mode toggle
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        }

        // Load dark mode preference
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }

        // Sidebar toggle (mobile)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('open');
        }

        // Close sidebar on link click (mobile)
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    toggleSidebar();
                }
            });
        });

        // Active sidebar link on scroll
        const sections = document.querySelectorAll('section[id]');
        const sidebarLinks = document.querySelectorAll('.sidebar-link');

        function updateActiveLink() {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                if (window.scrollY >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });

            sidebarLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', updateActiveLink);
        updateActiveLink();

        // Copy to clipboard
        function copyToClipboard(text, btn) {
            navigator.clipboard.writeText(text.trim()).then(() => {
                const originalText = btn.textContent;
                btn.textContent = 'Copie !';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('copied');
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text.trim();
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    const originalText = btn.textContent;
                    btn.textContent = 'Copie !';
                    btn.classList.add('copied');
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.classList.remove('copied');
                    }, 2000);
                } catch (e) {
                    // Silent fail
                }
                document.body.removeChild(textarea);
            });
        }

        // Collapsible sections
        function toggleCollapsible(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById(id + '-icon');
            el.classList.toggle('open');
            if (icon) {
                icon.style.transform = el.classList.contains('open') ? 'rotate(180deg)' : '';
            }
        }

        // Code tabs
        function switchCodeTab(tab) {
            // Hide all panels
            document.querySelectorAll('.code-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            // Show selected panel
            document.getElementById('code-' + tab).classList.remove('hidden');

            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active-tab');
                if (btn.getAttribute('data-tab') === tab) {
                    btn.classList.add('active-tab');
                }
            });
        }

        // Admin auth
        function openAdminLogin() {
            document.getElementById('adminLoginModal').classList.add('open');
            document.getElementById('adminEmail').focus();
        }

        function closeAdminLogin() {
            document.getElementById('adminLoginModal').classList.remove('open');
            document.getElementById('adminLoginError').classList.add('hidden');
            document.getElementById('adminEmail').value = '';
            document.getElementById('adminPassword').value = '';
        }

        async function handleAdminLogin(e) {
            e.preventDefault();
            const email = document.getElementById('adminEmail').value;
            const password = document.getElementById('adminPassword').value;
            const errorEl = document.getElementById('adminLoginError');
            const submitBtn = document.getElementById('adminLoginSubmit');

            errorEl.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Connexion...';

            try {
                const res = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Identifiants incorrects');
                }

                const user = data.data?.user || data.user;
                if (!user || user.role !== 'super_admin') {
                    throw new Error('Acces reserve aux Super-Administrateurs');
                }

                revealAllSections();
                closeAdminLogin();
                document.getElementById('adminLoginBtn').style.display = 'none';
                document.getElementById('adminLogoutBtn').style.display = 'inline-flex';
            } catch (err) {
                errorEl.textContent = err.message;
                errorEl.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Se connecter';
            }
        }

        function revealAllSections() {
            document.querySelectorAll('.admin-only-section').forEach(el => {
                el.classList.add('revealed');
            });
        }

        function hideAllSections() {
            document.querySelectorAll('.admin-only-section').forEach(el => {
                el.classList.remove('revealed');
            });
        }

        function adminLogout() {
            hideAllSections();
            document.getElementById('adminLoginBtn').style.display = 'inline-flex';
            document.getElementById('adminLogoutBtn').style.display = 'none';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeAdminLogin();
        });

        // Close modal on backdrop click
        document.getElementById('adminLoginModal').addEventListener('click', function(e) {
            if (e.target === this) closeAdminLogin();
        });
    </script>
</body>
</html>
