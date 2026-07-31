<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin Panel' }} - PWD DMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900" x-data="{ sidebarOpen: true }">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside 
            class="bg-slate-900 text-white w-64 flex-shrink-0 transition-all duration-300 ease-in-out z-20"
            :class="sidebarOpen ? 'ml-0' : '-ml-64'"
        >
            <div class="h-full flex flex-col">
                <!-- Brand -->
                <div class="px-6 py-8 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-xl">P</div>
                        <span class="text-xl font-bold tracking-tight">PWD <span class="text-blue-500">DMS</span></span>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 space-y-2 overflow-y-auto">
                    <a href="{{ route('admin.dashboard') }}" 
                        class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    
                    @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.files.index') }}?view=sub-companies" 
                                class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->get('view') === 'sub-companies' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                <span class="font-medium">All Wings</span>
                            </a>
                            @endif
                            @if(auth()->user()->role !== 'admin')
                    <!-- Documents Dropdown -->
                    <div x-data="{ open: {{ request()->routeIs('admin.files.index') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 text-slate-400 hover:bg-slate-800 hover:text-white group">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                <span class="font-medium">Documents</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        
                        <div x-show="open" x-cloak x-transition.opacity class="mt-1 ml-4 space-y-1 border-l-2 border-slate-800">
                           
                            <a href="{{ route('admin.files.index') }}?view=master-register" 
                                class="block px-6 py-2 rounded-lg text-sm font-medium transition-all {{ request()->get('view') === 'master-register' || !request()->has('view') ? 'text-blue-500' : 'text-slate-500 hover:text-white' }}">
                                Document List
                            </a>
                            <a href="{{ route('admin.files.index') }}?view=folder-manager" 
                                class="block px-6 py-2 rounded-lg text-sm font-medium transition-all {{ request()->get('view') === 'folder-manager' ? 'text-blue-500' : 'text-slate-500 hover:text-white' }}">
                                Folder Manager
                            </a>
                         
                            <a href="{{ route('admin.files.index') }}?view=category-index" 
                                class="block px-6 py-2 rounded-lg text-sm font-medium transition-all {{ request()->get('view') === 'category-index' ? 'text-blue-500' : 'text-slate-500 hover:text-white' }}">
                                Category Index
                            </a>
                          
                        </div>
                    </div>
                   
                            <!-- <a href="{{ route('admin.files.index') }}?view=sub-companies" 
                                class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 {{ request()->get('view') === 'sub-companies' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                <span class="font-medium">Users</span>
                            </a> -->
                            @endif
                </nav>

                <!-- Footer Sidebar -->
                <div class="p-8 border-t border-slate-800">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-sm font-bold shadow-lg shadow-blue-600/20">
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-red-500/10 hover:text-red-500 text-xs font-black uppercase tracking-widest transition-all border border-slate-700 hover:border-red-500/20">
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0 bg-slate-50 overflow-hidden relative">
            <!-- Header -->
            <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 py-4 px-8 flex items-center justify-between sticky top-0 z-10">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <input type="text" placeholder="Search documents..." class="pl-10 pr-4 py-2 bg-slate-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500 w-64 transition-all">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-8 animate-fade-in">
                {{ $slot }}
            </div>
        </main>
    </div>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    @if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '{{ session('success') }}',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    </script>
    @endif

    @if(session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{{ session('error') }}',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    </script>
    @endif

    @if($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Validation Error!',
            html: '<ul style="text-align: left; margin-top: 10px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
            confirmButtonText: 'OK'
        });
    </script>
    @endif
</body>
</html>
