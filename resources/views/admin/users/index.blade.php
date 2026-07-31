<x-admin-layout>
    <x-slot name="title">User Management</x-slot>

    <div class="space-y-6" x-data="{ addUserModal: false }">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">User Management</h2>
                <p class="text-sm text-slate-500 mt-1">Manage system access and permissions for your team.</p>
            </div>
            <button @click="addUserModal = true" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20 flex items-center space-x-2 translate-y-0 active:translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Add New User</span>
            </button>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden min-h-[500px]">
            <table class="w-full text-left">
                <thead class="bg-slate-50/80 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">User Details</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Last Sync</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                        @php
                            $users = [
                                ['name' => 'John Admin', 'email' => 'john@pwd.dms', 'role' => 'Super Admin', 'status' => 'Active', 'last' => '2 mins ago', 'color' => 'blue'],
                                ['name' => 'Sarah Manager', 'email' => 'sarah@pwd.dms', 'role' => 'Manager', 'status' => 'Active', 'last' => '45 mins ago', 'color' => 'indigo'],
                                ['name' => 'Robert Editor', 'email' => 'robert@pwd.dms', 'role' => 'Editor', 'status' => 'Pending', 'last' => '1 day ago', 'color' => 'amber'],
                                ['name' => 'Emma Viewer', 'email' => 'emma@pwd.dms', 'role' => 'Viewer', 'status' => 'Inactive', 'last' => '3 days ago', 'color' => 'slate'],
                            ];
                        @endphp
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50/50 transition-all group duration-200">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-full bg-{{ $user['color'] }}-100 text-{{ $user['color'] }}-600 flex items-center justify-center font-bold text-sm shadow-sm">
                                    {{ substr($user['name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-800">{{ $user['name'] }}</div>
                                    <div class="text-[11px] font-medium text-slate-400">{{ $user['email'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-slate-600">{{ $user['role'] }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 rounded-full @if($user['status'] == 'Active') bg-green-500 @elseif($user['status'] == 'Pending') bg-amber-500 @else bg-slate-300 @endif"></div>
                                <span class="text-sm font-bold @if($user['status'] == 'Active') text-green-600 @elseif($user['status'] == 'Pending') text-amber-600 @else text-slate-500 @endif">{{ $user['status'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500 font-medium italic">{{ $user['last'] }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-all duration-200">
                                <button class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button>
                                <button class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- Pagination -->
            <div class="p-6 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between">
                <p class="text-sm text-slate-500 font-medium">Showing 1 to 4 of 24 users</p>
                <div class="flex space-x-2">
                    <button class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-400 cursor-not-allowed">Previous</button>
                    <button class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors">Next</button>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div x-show="addUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-6 sm:p-0">
            <div @click="addUserModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl z-10 overflow-hidden transform transition-all animate-slide-up" x-show="addUserModal" x-transition>
                <div class="p-8 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800">Add New User</h3>
                        <p class="text-sm text-slate-400 mt-1 font-medium">Create a new account for a team member.</p>
                    </div>
                    <button @click="addUserModal = false" class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-100 rounded-full transition-all"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>
                
                <form onsubmit="event.preventDefault();" class="p-8 space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-extrabold text-slate-700 ml-1">Full Name</label>
                            <input type="text" placeholder="e.g. John Doe" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all text-sm font-medium">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-extrabold text-slate-700 ml-1">Email Address</label>
                            <input type="email" placeholder="john@example.com" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all text-sm font-medium">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-extrabold text-slate-700 ml-1">Role</label>
                            <select class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all text-sm font-bold text-slate-700 appearance-none cursor-pointer">
                                <option>Super Admin</option>
                                <option>Manager</option>
                                <option>Editor</option>
                                <option selected>Viewer</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-extrabold text-slate-700 ml-1">Initial Password</label>
                            <input type="password" placeholder="••••••••" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all text-sm font-medium">
                        </div>
                    </div>

                    <div class="bg-blue-50/50 p-6 rounded-2xl border border-blue-100">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" checked class="w-5 h-5 rounded border-blue-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-bold text-blue-900">Send welcome email with login details</span>
                        </label>
                    </div>

                    <div class="flex space-x-4 pt-4 border-t border-slate-100">
                        <button type="button" @click="addUserModal = false" class="flex-1 py-4 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all">Cancel</button>
                        <button type="submit" @click="addUserModal = false" class="flex-2 py-4 bg-blue-600 text-white rounded-2xl text-sm font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20">Create User Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
