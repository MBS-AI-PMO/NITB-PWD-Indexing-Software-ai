<x-admin-layout>
    <x-slot name="title">Dashboard</x-slot>

    <div class="space-y-8">
        <!-- Back to Admin Banner -->
        @if(session('original_admin_id'))
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-amber-900">You are logged in as a company user</p>
                    <p class="text-xs text-amber-700">Switch back to your admin account to access all features</p>
                </div>
            </div>
            <form action="{{ route('admin.back-to-admin') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Back to Admin</span>
                </button>
            </form>
        </div>
        @endif

        @if(auth()->user()->role === 'admin')
            <!-- Admin Dashboard - Comprehensive View -->
            <div class="space-y-8">
                <!-- Welcome Header -->
                <div class="flex items-end justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-800">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}!</h2>
                        <p class="text-slate-500 mt-1">System overview and management dashboard</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('admin.files.index') }}?view=sub-companies" class="px-4 py-2 bg-blue-600 border border-blue-600 rounded-xl text-sm font-medium text-white hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/20 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span>Manage Wings</span>
                        </a>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <p class="text-slate-500 text-sm font-medium">Total Companies</p>
                        <div class="flex items-baseline space-x-2 mt-1">
                            <h3 class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_companies']) }}</h3>
                            @if(isset($stats['active_companies']))
                            <span class="text-xs text-slate-400">({{ $stats['active_companies'] }} active)</span>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <p class="text-slate-500 text-sm font-medium">Total Users</p>
                        <div class="flex items-baseline space-x-2 mt-1">
                            <h3 class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_users']) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <p class="text-slate-500 text-sm font-medium">Total Documents</p>
                        <div class="flex items-baseline space-x-2 mt-1">
                            <h3 class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_documents']) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                            </svg>
                        </div>
                        <p class="text-slate-500 text-sm font-medium">Storage Used</p>
                        <div class="flex items-baseline space-x-2 mt-1">
                            <h3 class="text-2xl font-bold text-slate-800">{{ $stats['storage_used'] }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Recent Activity -->
                    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="font-bold text-slate-800">Recent Activity</h3>
                            <a href="{{ route('admin.files.index') }}" class="text-blue-600 text-sm font-semibold hover:underline">View all</a>
                        </div>
                        <div class="divide-y divide-slate-50">
                            @if(isset($stats['recent_activity']) && count($stats['recent_activity']) > 0)
                                @foreach($stats['recent_activity'] as $activity)
                                <div class="px-6 py-4 flex items-center space-x-4 hover:bg-slate-50 transition-colors">
                                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 font-bold flex-shrink-0">
                                        {{ strtoupper(substr($activity['user'], 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-800 truncate">
                                            <span class="font-bold">{{ $activity['user'] }}</span> 
                                            <span class="text-slate-500">{{ $activity['action'] }}</span> 
                                            @if(isset($activity['file_url']) && $activity['file_url'])
                                                <a href="{{ $activity['file_url'] }}" target="_blank" class="text-blue-600 font-semibold hover:underline">{{ $activity['file'] }}</a>
                                            @else
                                                <span class="text-blue-600 font-semibold">{{ $activity['file'] }}</span>
                                            @endif
                                        </p>
                                        <div class="flex items-center space-x-2 mt-0.5">
                                            <p class="text-xs text-slate-400">{{ $activity['time'] }}</p>
                                            @if(isset($activity['company']))
                                            <span class="text-xs text-slate-400">•</span>
                                            <p class="text-xs text-slate-500 font-medium">{{ $activity['company'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="px-6 py-8 text-center">
                                    <p class="text-slate-400 text-sm">No recent activity</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Stats & Company Storage -->
                    <div class="space-y-6">
                        <!-- Quick Stats -->
                      

                        <!-- Company Storage Breakdown -->
                        @if(isset($companyStorage) && count($companyStorage) > 0)
                        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-slate-800"> Storage Breakdown</h3>
                                <a href="{{ route('admin.files.index') }}?view=sub-companies" class="text-blue-600 text-xs font-semibold hover:underline">View all</a>
                            </div>
                            <div class="space-y-4 max-h-96 overflow-y-auto">
                                @php
                                    $totalStorageBytes = array_sum(array_column($companyStorage, 'storage_bytes'));
                                @endphp
                                @foreach(array_slice($companyStorage, 0, 8) as $company)
                                    @php
                                        $percentage = $totalStorageBytes > 0 ? ($company['storage_bytes'] / $totalStorageBytes) * 100 : 0;
                                    @endphp
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-slate-800 truncate">{{ $company['name'] }}</p>
                                                <div class="flex items-center space-x-3 mt-0.5">
                                                    <span class="text-xs text-slate-500">{{ number_format($company['documents_count']) }} docs</span>
                                                    <span class="text-xs text-slate-400">•</span>
                                                    <span class="text-xs text-slate-500">{{ number_format($company['users_count'] ?? 0) }} users</span>
                                                </div>
                                            </div>
                                            <span class="text-sm font-bold text-slate-800 ml-3">{{ $company['storage_formatted'] }}</span>
                                        </div>
                                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full transition-all" style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                        <p class="text-xs text-slate-400">{{ number_format($percentage, 1) }}% of total storage</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Quick Actions -->
                        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                            <h3 class="font-bold text-slate-800 mb-4">Quick Actions</h3>
                            <div class="space-y-2">
                                <a href="{{ route('admin.files.index') }}?view=sub-companies" class="block w-full px-4 py-2.5 bg-blue-50 hover:bg-blue-100 rounded-xl text-sm font-medium text-blue-700 transition-colors flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span>Manage Wings</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

             
            </div>
            
        @else
            <!-- Company Dashboard - Detailed View -->
            <div class="space-y-8">
                <!-- Welcome Header -->
                <div class="flex items-end justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-800">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}!</h2>
                        <p class="text-slate-500 mt-1">Here's what's happening with your documents today.</p>
                    </div>
                    <div class="flex space-x-3">
                       
                        <a href="{{ route('admin.files.index') }}" class="px-4 py-2 bg-blue-600 border border-blue-600 rounded-xl text-sm font-medium text-white hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/20">+ Upload File</a>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 mb-4 text-xl">
                            📁
                        </div>
                        <p class="text-slate-500 text-sm font-medium">Total Documents</p>
                        <div class="flex items-baseline space-x-2 mt-1">
                            <h3 class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_documents']) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600 mb-4 text-xl">
                            👥
                        </div>
                        <p class="text-slate-500 text-sm font-medium">Wings Users</p>
                        <div class="flex items-baseline space-x-2 mt-1">
                            <h3 class="text-2xl font-bold text-slate-800">{{ number_format($stats['total_users']) }}</h3>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center text-purple-600 mb-4 text-xl">
                            ⚡
                        </div>
                        <p class="text-slate-500 text-sm font-medium">Storage Used</p>
                        <div class="flex items-baseline space-x-2 mt-1">
                            <h3 class="text-2xl font-bold text-slate-800">{{ $stats['storage_used'] }}</h3>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center text-green-600 mb-4 text-xl">
                            📊
                        </div>
                        <p class="text-slate-500 text-sm font-medium">Active Folders</p>
                        <div class="flex items-baseline space-x-2 mt-1">
                            <h3 class="text-2xl font-bold text-slate-800">{{ number_format($stats['active_folders'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Tables/Charts Area -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Recent Activity -->
                    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="font-bold text-slate-800">Recent Activity</h3>
                            <a href="{{ route('admin.files.index') }}" class="text-blue-600 text-sm font-semibold hover:underline">View all</a>
                        </div>
                        <div class="divide-y divide-slate-50">
                            @if(isset($stats['recent_activity']) && count($stats['recent_activity']) > 0)
                                @foreach($stats['recent_activity'] as $activity)
                                <div class="px-6 py-4 flex items-center space-x-4 hover:bg-slate-50 transition-colors">
                                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                        {{ strtoupper(substr($activity['user'], 0, 1)) }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-slate-800">
                                            <span class="font-bold">{{ $activity['user'] }}</span> 
                                            <span class="text-slate-500">{{ $activity['action'] }}</span> 
                                            @if(isset($activity['file_url']) && $activity['file_url'])
                                                <a href="{{ $activity['file_url'] }}" target="_blank" class="text-blue-600 font-semibold hover:underline">{{ $activity['file'] }}</a>
                                            @else
                                                <span class="text-blue-600 font-semibold">{{ $activity['file'] }}</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ $activity['time'] }}</p>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="px-6 py-8 text-center">
                                    <p class="text-slate-400 text-sm">No recent activity</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Storage Breakdown -->
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                        <h3 class="font-bold text-slate-800 mb-6">Storage Breakdown</h3>
                        <div class="space-y-6">
                            @if(isset($stats['storage_breakdown']) && count($stats['storage_breakdown']) > 0)
                                @php
                                    $colors = [
                                        ['bg' => 'bg-blue-500', 'text' => 'text-blue-600'],
                                        ['bg' => 'bg-indigo-500', 'text' => 'text-indigo-600'],
                                        ['bg' => 'bg-purple-500', 'text' => 'text-purple-600'],
                                        ['bg' => 'bg-green-500', 'text' => 'text-green-600'],
                                        ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-600'],
                                        ['bg' => 'bg-pink-500', 'text' => 'text-pink-600'],
                                    ];
                                    $colorIndex = 0;
                                @endphp
                                @foreach($stats['storage_breakdown'] as $breakdown)
                                    @php
                                        $color = $colors[$colorIndex % count($colors)];
                                        $colorIndex++;
                                    @endphp
                                    <div>
                                        <div class="flex items-center justify-between text-sm mb-2">
                                            <span class="text-slate-500 font-medium">{{ $breakdown['type'] }}</span>
                                            <span class="text-slate-800 font-bold">{{ $breakdown['formatted'] }}</span>
                                        </div>
                                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full {{ $color['bg'] }} rounded-full" style="width: {{ $breakdown['percentage'] }}%"></div>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1">{{ number_format($breakdown['count']) }} file(s)</p>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-8">
                                    <p class="text-slate-400 text-sm">No storage data available</p>
                                </div>
                            @endif
                        </div>
                        
                        @if(isset($stats['storage_used']) && $stats['storage_used'] !== '0 B')
                        <div class="mt-6 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-slate-600 font-medium">Total Storage Used</span>
                                <span class="text-lg font-bold text-slate-800">{{ $stats['storage_used'] }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
