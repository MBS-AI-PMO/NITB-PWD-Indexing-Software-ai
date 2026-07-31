<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PWD DMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px border rgba(255, 255, 255, 0.1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up {
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-6 overflow-hidden relative">
    <!-- Animated Blobs -->
    <div class="absolute top-0 -left-4 w-72 h-72 bg-blue-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
    <div class="absolute top-0 -right-4 w-72 h-72 bg-indigo-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-purple-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>

    <div class="w-full max-w-md z-10">
        <div class="text-center mb-10 animate-slide-up">
            <div class="inline-flex items-center justify-center p-4 bg-blue-600/20 rounded-2xl mb-4 border border-blue-500/30">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-2xl text-white">P</div>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">PWD <span class="text-blue-500 text-6xl" style="vertical-align: middle;">.</span> DMS</h1>
            <p class="text-slate-400 mt-2">Professional Document Management System</p>
        </div>

        <div class="glass-card p-8 rounded-3xl border border-white/10 shadow-2xl animate-slide-up animation-delay-200" style="opacity: 0; animation-delay: 0.2s;">
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.authenticate') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="name@company.com" class="w-full px-5 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all font-medium" required autofocus>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-slate-300">Password</label>

                    </div>
                    <input type="password" name="password" placeholder="••••••••" class="w-full px-5 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all" required>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-white/10 bg-white/5 text-blue-600 focus:ring-offset-slate-900 cursor-pointer">
                    <label for="remember" class="text-sm text-slate-400 cursor-pointer select-none">Remember me</label>
                </div>
                <button type="submit" class="w-full py-4 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-2xl shadow-lg shadow-blue-600/20 transform active:scale-95 transition-all">
                    Sign In
                </button>
            </form>
        </div>
        
        <p class="text-center text-slate-500 mt-8 text-sm flex items-center justify-center space-x-2 animate-slide-up" style="opacity: 0; animation-delay: 0.4s;">
            <span>&copy; 2026 PWD DMS.</span>
            <span class="w-1 h-1 bg-slate-700 rounded-full"></span>
            <span>All rights reserved.</span>
        </p>
    </div>
</body>
</html>
