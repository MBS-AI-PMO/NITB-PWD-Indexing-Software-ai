<x-admin-layout>
    <x-slot name="title">Files & Documents</x-slot>

    <div class="space-y-6" x-data="fileManager()">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800"
                    x-text="
                        view === 'sub-companies'
                            ? 'All Wings'
                            : (view === 'master-register'
                                ? 'Documents List'
                                : view.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
                              )
                    ">
                </h2>
                <!-- Breadcrumbs (Visible in Register and Folder views) -->
                <nav class="flex items-center space-x-2 text-sm text-slate-500 mt-1" x-show="view === 'master-register' || view === 'folder-manager'">
                    <div class="flex items-center space-x-2">
                        <button @click="goBack(null)" class="hover:text-blue-600 transition-colors font-medium flex items-center {{ !request('folder_id') ? 'text-slate-900 font-bold' : '' }}">
                            <span class="mr-1">🏠</span>
                            <span>Home</span>
                        </button>
                        @if(count($breadcrumbs) > 0)
                            <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        @endif
                    </div>
                    @foreach($breadcrumbs as $index => $crumb)
                        <div class="flex items-center space-x-2">
                            <button @click="goBack('{{ $crumb['id'] }}')" 
                                class="hover:text-blue-600 transition-colors font-medium flex items-center {{ $index === count($breadcrumbs) - 1 ? 'text-slate-900 font-bold' : '' }}">
                                <span>{{ $crumb['name'] }}</span>
                            </button>
                            @if($index < count($breadcrumbs) - 1)
                                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            @endif
                        </div>
                    @endforeach
                </nav>
            </div>
            
            <div class="flex items-center space-x-3">
                <!-- New Folder: Only in Folder Manager -->
                <button x-show="view === 'folder-manager'" @click="folderModal = true" class="px-6 py-3 bg-white border border-slate-200 text-slate-700 text-sm font-bold rounded-2xl hover:bg-slate-50 transition-all shadow-sm flex items-center space-x-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>New Folder</span>
                </button>
                
                <!-- Upload Document: In Register and Folder views -->
                <button x-show="view === 'master-register' || view === 'folder-manager'" @click="uploadModal = true" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-2xl transition-all shadow-lg shadow-blue-600/20 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    <span>Upload Document</span>
                </button>

                <!-- Add Category: Only in Category Index -->
                <button x-show="view === 'category-index'" @click="editCategoryId = null; categoryModal = true;" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-2xl transition-all shadow-lg shadow-blue-600/20 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Add Category</span>
                </button>
            </div>
        </div>

        <!-- Filters & Search Bar (Only for Master Register) -->
        <div x-show="view === 'master-register'" class="bg-white px-6 py-5 rounded-3xl border border-slate-200 shadow-sm">
            <!-- Header row -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.18em]">Filters</p>
                    <p class="text-xs text-slate-500 font-medium">Refine records by heading, category, dates and pages</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button @click="window.location.href = '{{ route('admin.files.index') }}?search=' + search + '&heading=' + filterHeading + '&category=' + filterCategory + '&nature_of_record=' + filterNature + '&classification=' + filterClassification + '&file_no=' + filterFileNo + '&date_from=' + filterDateFrom + '&date_to=' + filterDateTo + '&title=' + filterTitle + '&note_pages=' + filterNotePages + '&corresp_pages=' + filterCorrespPages + '&view=master-register'" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h4l2 3h8a1 1 0 011 1v2M4 7h16v11a1 1 0 01-1 1H5a1 1 0 01-1-1V7z"/></svg>
                        Apply
                    </button>
                    <button @click="window.location.href = '/admin/files?view=master-register'" 
                        class="inline-flex items-center px-3 py-2 bg-slate-50 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-100 border border-slate-200 transition-all">
                        Clear
                    </button>
                </div>
            </div>

            <!-- First row: 5 filters (Search, Heading, Category, Nature, Classification) -->
       
            <div class="mt-3 grid grid-cols-1 md:grid-cols-5 lg:grid-cols-6 gap-3">
                <!-- Name Search -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Search Keywords</label>
                    <div class="relative">
                        <input type="text" x-model="search" placeholder="Search keywords or content..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                <!-- Main Heading Filter -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Main Heading</label>
                    <select x-model="filterHeading" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none appearance-none">
                        <option value="">All Headings</option>
                        <option value="General">General</option>
                        <option value="Recruitment">Recruitment</option>
                        <option value="Transfer / Posting">Transfer / Posting</option>
                        <option value="Deputation">Deputation</option>
                        <option value="Q-Loan">Q-Loan</option>
                        <option value="Personal Files">Personal Files</option>
                        <option value="Pension">Pension</option>
                        <option value="Court Files">Court Files</option>
                        <option value="Project Files">Project Files</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Category</label>
                    <select x-model="filterCategory" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none appearance-none">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->code }}">{{ $category->code }} - {{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Nature of Record Filter -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Nature of Record</label>
                    <select x-model="filterNature" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none appearance-none">
                        <option value="">All Nature</option>
                        <option value="Current">Current</option>
                        <option value="Non-current">Non-current</option>
                    </select>
                </div>

                <!-- Classification Filter -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Classification</label>
                    <select x-model="filterClassification" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none appearance-none">
                        <option value="">All Classification</option>
                        <option value="General">General</option>
                        <option value="Confidential">Confidential</option>
                    </select>
                </div>
            </div>

            <!-- Second row: 6 filters -->
            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- File No Search -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">File / Diary No.</label>
                    <input type="text" x-model="filterFileNo" placeholder="PWD/2025/..." class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none">
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Date From</label>
                    <input type="date" x-model="filterDateFrom" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-[11px] font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Date To</label>
                    <input type="date" x-model="filterDateTo" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-[11px] font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none">
                </div>

                <!-- Title Filter -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Title / Subject</label>
                    <input type="text" x-model="filterTitle" placeholder="Enter file subject or title..." class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none">
                </div>

                <!-- Note Pages (Min) -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Min Note Pages</label>
                    <input type="number" x-model="filterNotePages" min="0" placeholder="0" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none">
                </div>

                <!-- Corresp Pages (Min) -->
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Min Corresp Pages</label>
                    <input type="number" x-model="filterCorrespPages" min="0" placeholder="0" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none">
                </div>
            </div>

            <!-- Selection Actions -->
            <div class="flex items-center space-x-2 mt-4" x-show="selectedFiles.length > 0">
                <span class="text-[10px] font-black text-blue-600 bg-blue-50 px-2 py-1 rounded-lg uppercase tracking-tighter"><span x-text="selectedFiles.length"></span> Item</span>
                <button class="p-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
        </div>

        <!-- Header Actions -->
        <div x-show="view === 'sub-companies'" class="flex justify-end mb-4">
            <button @click="editCompanyId = null; companyModal = true;" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-2xl transition-all shadow-lg shadow-blue-600/20 flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>New Wing</span>
            </button>
        </div>

        <!-- Sub-Companies View -->
        <div x-show="view === 'sub-companies'" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

<table class="w-full text-sm text-left">
    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
        <tr>
            <th class="px-6 py-4">Wing</th>
            <th class="px-6 py-4">Used Storage</th>
            <th class="px-6 py-4">Folders</th>
            <th class="px-6 py-4">Documents</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
        </tr>
    </thead>

    <tbody class="divide-y divide-slate-100">
        @forelse($companies as $company)
            <tr class="hover:bg-slate-50 transition">
                
                <!-- Company Name -->
                <td class="px-6 py-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center">
                        🏢
                    </div>
                    <span class="font-semibold text-slate-700">
                        {{ $company->name }}
                    </span>
                </td>

                <!-- Used Storage -->
                <td class="px-6 py-4 text-slate-500 font-medium">
                    {{ $company->storage_formatted ?? '0 B' }}
                </td>
                <!-- Folder Count -->
                <td class="px-6 py-4 text-slate-500 font-medium">
                    {{ $company->folders_count }}
                </td>

                <!-- Document Count -->
                <td class="px-6 py-4 text-slate-500 font-medium">
                    {{ $company->documents_count }}
                </td>

                <!-- Status -->
                <td class="px-6 py-4">
                    @if($company->is_active)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                            <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                            <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            Inactive
                        </span>
                    @endif
                </td>

                <!-- Actions -->
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end items-center gap-2">
                        
                        <!-- Active/Inactive Toggle -->
                        <form action="{{ route('admin.companies.toggle-status', $company->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" 
                                class="p-2 rounded-lg transition-colors {{ $company->is_active ? 'bg-green-100 text-green-600 hover:bg-green-200' : 'bg-slate-100 text-slate-400 hover:bg-slate-200' }}"
                                title="{{ $company->is_active ? 'Active' : 'Inactive' }}">
                                @if($company->is_active)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @endif
                            </button>
                        </form>

                        <!-- Login As -->
                        @if($company->adminUser)
                            <form action="{{ route('admin.companies.login-as', $company->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                    class="p-2 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors"
                                    title="Login as {{ $company->name }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                </button>
                            </form>
                        @endif

                        <!-- Edit -->
                        <button @click="openEditCompany({{ $company->id }}, '{{ addslashes($company->name) }}', '{{ addslashes($company->adminUser->email ?? '') }}', '{{ addslashes($company->description ?? '') }}')" 
                            class="p-2 rounded-lg bg-amber-100 text-amber-600 hover:bg-amber-200 transition-colors"
                            title="Edit Company">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>

                        <!-- Delete -->
                        <form action="{{ route('admin.companies.destroy', $company->id) }}" method="POST" id="delete-company-{{ $company->id }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="button" 
                                onclick="confirmDelete('delete-company-{{ $company->id }}', 'Delete this company?')" 
                                class="p-2 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors"
                                title="Delete Company">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>

                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center py-10 text-slate-400 font-semibold">
                    No Wings found. Create one to get started.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>


        <!-- Folder Manager View -->
        <div x-show="view === 'folder-manager'" class="space-y-8">
            <!-- Folders Section -->
            <div>
                <div class="flex items-center justify-between mb-4 px-2">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Directories & Sub-folders</h4>
                    <button @click="folderModal = true" class="text-xs font-bold text-blue-600 hover:underline">Quick Create</button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                    @foreach($folders as $folder)
                    <div @click="openFolder('{{ $folder->id }}')" class="group cursor-pointer p-6 rounded-3xl bg-white border border-slate-100 hover:border-amber-200 hover:shadow-xl hover:shadow-amber-600/5 transition-all text-center">
                        <div class="w-20 h-20 mx-auto bg-amber-50 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform relative">
                            <svg class="w-10 h-10 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
                            <div class="absolute -right-1 -top-1 w-6 h-6 bg-amber-400 text-white rounded-full flex items-center justify-center text-[10px] font-black border-2 border-white">{{ $folder->children_count }}</div>
                        </div>
                        <span class="text-sm font-bold text-slate-700 block truncate">{{ $folder->name }}</span>
                        <span class="text-[10px] text-slate-400 font-extrabold uppercase tracking-tight mt-1">Directory</span>
                    </div>
                    @endforeach

                    @if($folders->isEmpty())
                        <div class="col-span-full py-10 text-center text-slate-400 font-bold border-2 border-dashed border-slate-100 rounded-3xl">
                            No folders found.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Documents inside current folder -->
            <div>
                <div class="flex items-center justify-between mb-4 px-2">
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Documents in Folder</h4>
                    <button @click="uploadModal = true" class="text-xs font-bold text-blue-600 hover:underline">Upload Here</button>
                </div>
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($documents as $doc)
                        <div class="flex items-center p-4 rounded-3xl border border-slate-50 hover:border-blue-100 hover:bg-blue-50/20 transition-all group cursor-pointer">
                            <div class="w-12 h-12 bg-blue-100/50 rounded-2xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-800 truncate">{{ $doc->subject_title }}</p>
                                <p class="text-[10px] text-slate-400 font-extrabold uppercase mt-0.5">{{ $doc->file_no }} • {{ $doc->date_of_opening }}</p>
                            </div>
                            <div class="flex items-center space-x-1">
                                @if($doc->file_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($doc->file_path) }}" target="_blank" class="p-2 text-slate-300 hover:text-blue-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                        @endforeach

                        @if($documents->isEmpty())
                            <div class="col-span-full py-10 text-center text-slate-400 font-bold border-2 border-dashed border-slate-100 rounded-3xl">
                                No documents in this view.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Index View -->
        <div x-show="view === 'category-index'" class="space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Category</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Retention Period</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($categories as $category)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="w-auto min-w-[4rem] h-8 px-3 inline-flex items-center justify-center bg-blue-50 text-blue-600 rounded-lg text-xs font-black mr-3">Cat {{ $category->code }}</span>
                                <span class="text-xs font-bold text-slate-700">{{ $category->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs font-bold text-slate-500">{{ $category->retention_description }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="openEditCategory({{ $category->id }}, '{{ addslashes($category->code) }}', '{{ addslashes($category->name) }}', '{{ addslashes($category->retention_description) }}')" 
                                        class="p-2 rounded-lg bg-amber-100 text-amber-600 hover:bg-amber-200 transition-colors"
                                        title="Edit Category">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" id="delete-category-{{ $category->id }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="button" 
                                            onclick="confirmDelete('delete-category-{{ $category->id }}', 'Delete category?')" 
                                            class="p-2 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition-colors"
                                            title="Delete Category">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach

                        @if($categories->isEmpty())
                            <tr><td colspan="3" class="px-6 py-10 text-center text-slate-400 font-bold">No categories defined.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            
        </div>

        <!-- Files & Folders List (Master File Register View) -->
        <div x-show="view === 'master-register'" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-x-auto min-h-[500px]">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/80 border-b border-slate-200">
                    <tr class="divide-x divide-slate-100">
                        <th rowspan="2" class="px-4 py-4 w-16 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Sr.<br>No.</th>
                        <th rowspan="2" class="px-4 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nature of<br>Record</th>
                        <th rowspan="2" class="px-4 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Category</th>
                        <th rowspan="2" class="px-4 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Main<br>Heading</th>
                        <th rowspan="2" class="px-4 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Classifi-<br>cation</th>
                        <th rowspan="2" class="px-4 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date of<br>Opening</th>
                        <th rowspan="2" class="px-4 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">File No./<br>Diary No.</th>
                        <th rowspan="2" class="px-4 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Subject /<br>Title</th>
                        <th colspan="2" class="px-4 py-2 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">No. of Pages</th>
                        <th rowspan="2" class="px-4 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Remarks</th>
                        <th rowspan="2" class="px-4 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                    <tr class="divide-x divide-slate-100">
                        <th class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">Note<br>Portion</th>
                        <th class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">Corresp.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($documents as $index => $doc)
                    <tr class="hover:bg-slate-50/50 transition-colors divide-x divide-slate-50">
                        <td class="px-4 py-4 text-center text-[11px] font-black text-slate-400">{{ $documents->firstItem() + $index }}</td>
                        <td class="px-4 py-4">
                            <span class="px-2 py-1 {{ $doc->nature_of_record === 'Current' ? 'bg-green-50 text-green-600' : 'bg-amber-50 text-amber-600' }} rounded-lg text-[9px] font-black uppercase">{{ $doc->nature_of_record }}</span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="w-6 h-6 inline-flex items-center justify-center bg-blue-50 text-blue-600 rounded-md text-[10px] font-black">{{ $doc->category->code ?? 'N/A' }}</span>
                        </td>
                        <td class="px-4 py-4 text-[11px] font-bold text-slate-700">{{ $doc->main_heading }}</td>
                        <td class="px-4 py-4">
                            <span class="px-2 py-1 {{ $doc->classification === 'General' ? 'bg-slate-50 text-slate-600' : 'bg-red-50 text-red-600' }} rounded-lg text-[9px] font-black uppercase">{{ $doc->classification }}</span>
                        </td>
                        <td class="px-4 py-4 text-[11px] font-bold text-slate-500 whitespace-nowrap">{{ $doc->date_of_opening ? \Carbon\Carbon::parse($doc->date_of_opening)->format('d-m-Y') : '-' }}</td>
                        <td class="px-4 py-4 text-[11px] font-black text-blue-600 whitespace-nowrap">{{ $doc->file_no }}</td>
                        <td class="px-4 py-4">
                            @php
                                $folderChain = [];
                                if ($doc->folder) {
                                    $temp = $doc->folder;
                                    $maxDepth = 10; // Prevent infinite loops
                                    $depth = 0;
                                    while ($temp && $depth < $maxDepth) {
                                        // Store full folder model so we have both id & name
                                        array_unshift($folderChain, $temp);
                                        $temp = $temp->parent;
                                        $depth++;
                                    }
                                }
                            @endphp
                            @if(count($folderChain) > 0)
                                <div class="flex items-center space-x-1 text-[10px] text-slate-500 mb-1">
                                    @foreach($folderChain as $index => $folder)
                                        <a href="{{ route('admin.files.index', ['folder_id' => $folder->id, 'view' => 'folder-manager']) }}"
                                           class="font-medium hover:text-blue-600 transition-colors {{ $index < count($folderChain) - 1 ? 'opacity-80' : 'opacity-100' }}">
                                            {{ $folder->name }}
                                        </a>
                                        @if($index < count($folderChain) - 1)
                                            <span class="text-slate-300">/</span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            @if($doc->file_path)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($doc->file_path) }}"
                                   target="_blank"
                                   class="text-[11px] font-black text-slate-800 leading-tight hover:text-blue-600 transition-colors">
                                    {{ $doc->subject_title }}
                                </a>
                            @else
                                <p class="text-[11px] font-black text-slate-800 leading-tight">{{ $doc->subject_title }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center text-[11px] font-bold text-slate-500">{{ $doc->note_pages }}</td>
                        <td class="px-4 py-4 text-center text-[11px] font-bold text-slate-500">{{ $doc->corresp_pages }}</td>
                        <td class="px-4 py-4">
                            <p class="text-[10px] text-slate-400 italic line-clamp-1">{{ $doc->remarks }}</p>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                @if($doc->file_path)
                                    <!-- View -->
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($doc->file_path) }}" target="_blank" class="p-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" title="View File">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                @endif

                                <!-- Edit -->
                                <button type="button"
                                        @click="openEditDocument(
                                            {{ $doc->id }},
                                            {{ $doc->company_id ?? 'null' }},
                                            {{ $doc->folder_id ?? 'null' }},
                                            {{ $doc->category_id ?? 'null' }},
                                            '{{ $doc->nature_of_record }}',
                                            '{{ addslashes($doc->main_heading ?? '') }}',
                                            '{{ $doc->classification }}',
                                            '{{ $doc->date_of_opening ? \Carbon\Carbon::parse($doc->date_of_opening)->format('Y-m-d') : '' }}',
                                            '{{ addslashes($doc->file_no ?? '') }}',
                                            '{{ addslashes($doc->subject_title ?? '') }}',
                                            {{ $doc->note_pages ?? 0 }},
                                            {{ $doc->corresp_pages ?? 0 }},
                                            '{{ addslashes($doc->remarks ?? '') }}'
                                        )"
                                        class="p-1.5 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors"
                                        title="Edit Entry">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>

                                <!-- Delete -->
                                <form action="{{ route('admin.files.destroy', $doc->id) }}" method="POST" id="delete-document-{{ $doc->id }}">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="confirmDelete('delete-document-{{ $doc->id }}', 'Delete this record?')" class="p-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" title="Delete Entry">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div x-show="view === 'master-register'" class="mt-8">
            {{ $documents->links() }}
        </div>



    <!-- Category Definition Modal -->
    <div x-show="categoryModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-6 sm:p-0">
        <div @click="closeCategoryModal()" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl z-10 overflow-hidden transform transition-all animate-slide-up" x-show="categoryModal" x-transition>
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-black text-slate-800 tracking-tight" x-text="$data.editCategoryId !== null ? 'Edit Category Rule' : 'Add Category Rule'"></h3>
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1" x-text="$data.editCategoryId !== null ? 'Update retention policy' : 'Create new retention policy'"></p>
                </div>
                <button @click="closeCategoryModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-8">
                <form id="categoryForm" :action="$data.editCategoryId !== null ? '{{ url('admin/categories') }}/' + $data.editCategoryId : '{{ route('admin.categories.store') }}'" method="POST">
                    @csrf
                    <template x-if="$data.editCategoryId !== null">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Category Code</label>
                            <select name="code" id="categoryCode" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 appearance-none">
                                <option value="A">Category A</option>
                                <option value="B">Category B</option>
                                <option value="C">Category C</option>
                                <option value="D">Category D</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Category Name</label>
                            <input type="text" name="name" id="categoryName" placeholder="e.g. Permanent Records" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        </div>
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Retention Period Description</label>
                            <textarea name="retention_description" id="categoryRetention" rows="3" placeholder="Enter rule description..." class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 outline-none resize-none"></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="p-6 bg-slate-50/50 flex space-x-3">
                <button @click="closeCategoryModal()" class="flex-1 py-4 bg-white border border-slate-200 rounded-2xl text-sm font-black text-slate-600 hover:bg-slate-100 transition-colors">Cancel</button>
                <button onclick="document.getElementById('categoryForm').submit()" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl text-sm font-black hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20" x-text="$data.editCategoryId !== null ? 'Update Rule' : 'Save Rule'"></button>
            </div>
        </div>
    </div>

        <!-- Create Folder Modal -->
        <div x-show="folderModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-6 sm:p-0">
            <div @click="folderModal = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl z-10 overflow-hidden transform transition-all animate-slide-up" x-show="folderModal" x-transition>
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-slate-800 tracking-tight">Create New Folder</h3>
                    <button @click="folderModal = false" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>
                <div class="p-8">
                    <form id="folderForm" action="{{ route('admin.folders.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ request('folder_id') }}">
                        @if($companies->isNotEmpty())
                            <input type="hidden" name="company_id" value="{{ request('company_id') ?? $companies->first()->id }}">
                        @endif
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Folder Name</label>
                            <input type="text" name="name" placeholder="e.g. Project Documents" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        </div>
                    </form>
                </div>
                <div class="p-6 bg-slate-50/50 flex space-x-3">
                    <button @click="folderModal = false" class="flex-1 py-4 bg-white border border-slate-200 rounded-2xl text-sm font-bold hover:bg-slate-100 transition-colors text-slate-600">Cancel</button>
                    <button onclick="document.getElementById('folderForm').submit()" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl text-sm font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20">Create Folder</button>
                </div>
            </div>
        </div>

        <!-- Upload Modal (Integrated with Register Fields) -->
        <div x-show="uploadModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-6 sm:p-0">
            <div @click="closeUploadModal()" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl z-10 overflow-hidden transform transition-all animate-slide-up" x-show="uploadModal" x-transition>
                <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-white">
                    <h3 class="text-xl font-black text-slate-800 tracking-tight text-center w-full ml-6" x-text="$data.editDocumentId !== null ? 'Edit Document & Register Entry' : 'Upload Document & Register Entry'"></h3>
                    <button @click="closeUploadModal()" class="text-slate-400 hover:text-slate-600 transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>
                
                <div class="p-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
                    <form id="uploadForm" :action="$data.editDocumentId !== null ? '{{ url('admin/files') }}/' + $data.editDocumentId : '{{ route('admin.files.upload') }}'" method="POST" enctype="multipart/form-data">
                        @csrf
                        <template x-if="$data.editDocumentId !== null">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <input type="hidden" name="folder_id" value="{{ request('folder_id') }}">
                        <!-- File Upload Zone -->
                        <div class="mb-8">
                            <label class="group border-3 border-dashed border-slate-100 rounded-3xl p-8 flex flex-col items-center justify-center text-center space-y-3 hover:border-blue-400 hover:bg-blue-50/50 transition-all cursor-pointer relative">
                                <input type="file" name="document" class="hidden" @change="validateFiles($event)" accept=".pdf,image/*">
                                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-3xl group-hover:scale-110 transition-transform duration-300">☁️</div>
                                <div>
                                    <p class="text-base font-extrabold text-slate-800">Select Document to Upload</p>
                                    <p class="text-[10px] text-slate-500 mt-1 font-medium tracking-wide">PDF, IMAGE (Max 50MB)</p>
                                </div>
                            </label>
                            <template x-if="fileError">
                                <p class="text-[10px] text-red-600 font-bold mt-2 text-center" x-text="fileError"></p>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Company -->
                             <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Company</label>
                                <select name="company_id" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 appearance-none">
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                             </div>
                            <!-- Nature of Record -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Nature of Record</label>
                                <select name="nature_of_record" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 appearance-none">
                                    <option value="Current">Current</option>
                                    <option value="Non-current">Non-current</option>
                                </select>
                            </div>
                            <!-- Category -->
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Category Type</label>
                                <select name="category_id" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 appearance-none">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">Category {{ $category->code }}: {{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Classification -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Classification</label>
                                <select name="classification" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 appearance-none">
                                    <option value="General">General</option>
                                    <option value="Confidential">Confidential</option>
                                </select>
                            </div>
                            <!-- Main Heading -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Main Heading</label>
                                <select name="main_heading" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 appearance-none">
                                    <option value="General">General</option>
                                    <option value="Recruitment">Recruitment</option>
                                    <option value="Transfer / Posting">Transfer / Posting</option>
                                    <option value="Deputation">Deputation</option>
                                    <option value="Q-Loan">Q-Loan</option>
                                    <option value="Personal Files">Personal Files</option>
                                    <option value="Pension">Pension</option>
                                    <option value="Court Files">Court Files</option>
                                    <option value="Project Files">Project Files</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <!-- Date -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Date of Opening</label>
                                <input type="date" name="date_of_opening" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                            </div>
                            <!-- File No -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">File No. / Diary No.</label>
                                <input type="text" name="file_no" placeholder="PWD/2025/..." class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                            </div>
                        </div>

                        <!-- Subject/Title -->
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Subject / Title</label>
                            <textarea name="subject_title" rows="2" placeholder="Enter file subject or project title..." class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-3xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-6 text-center">
                             <!-- Note Portion -->
                             <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Note Pages</label>
                                <input type="number" name="note_pages" placeholder="0" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-center">
                            </div>
                            <!-- Correspondence -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Corresp. Pages</label>
                                <input type="number" name="corresp_pages" placeholder="0" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 text-center">
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div>
                            <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Remarks</label>
                            <textarea name="remarks" rows="2" placeholder="Any important facts or directives..." class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-3xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 resize-none"></textarea>
                        </div>
                    </form>
                </div>

                <div class="p-6 bg-slate-50/50 border-t border-slate-100 flex space-x-3">
                    <button @click="closeUploadModal()" :disabled="uploading" class="flex-1 py-4 bg-white border border-slate-200 rounded-2xl text-sm font-black text-slate-600 hover:bg-slate-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">Cancel</button>
                    <button @click="submitUploadForm()" :disabled="uploading" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl text-sm font-black hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2" x-text="uploading ? 'Uploading...' : ($data.editDocumentId !== null ? 'Update Entry' : 'Start Upload')">
                        <template x-if="uploading">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                    </button>
                </div>
            </div>
        </div>

        <!-- New/Edit Company Modal -->
        <div x-show="companyModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-6 sm:p-0">
            <div @click="closeCompanyModal()" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl z-10 overflow-hidden transform transition-all animate-slide-up" x-show="companyModal" x-transition>
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-slate-800 tracking-tight" x-text="$data.editCompanyId !== null ? 'Edit Wing' : 'Register New Wing'"></h3>
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1" x-text="$data.editCompanyId !== null ? 'Update Wing details' : 'Add new Wing to system'"></p>
                    </div>
                    <button @click="closeCompanyModal()" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>
                <div class="p-8">
                    <form id="companyForm" :action="$data.editCompanyId !== null ? '{{ url('admin/companies') }}/' + $data.editCompanyId : '{{ route('admin.companies.store') }}'" method="POST">
                        @csrf
                        <template x-if="$data.editCompanyId !== null">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <input type="hidden" name="role" value="company">
                        <div class="space-y-6">
                            <!-- Wing Name -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Wing Name</label>
                                <input type="text" name="name" placeholder="e.g. PWD Accounts Wing" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50" required>
                            </div>
                            <!-- Email -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Email Address</label>
                                <input type="email" name="email" placeholder="contact@company.com" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                            </div>
                            <!-- Password -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Password <span class="text-xs text-slate-400" x-show="$data.editCompanyId !== null">(Leave blank to keep current password)</span><span class="text-red-500" x-show="$data.editCompanyId === null">*</span></label>
                                <input type="password" name="password" placeholder="Enter password" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50" :required="$data.editCompanyId === null">
                            </div>
                            <!-- Description -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-widest mb-2">Description</label>
                                <textarea name="description" rows="3" placeholder="Company mission or notes..." class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500/50"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="p-6 bg-slate-50/50 flex space-x-3">
                    <button @click="closeCompanyModal()" class="flex-1 py-4 bg-white border border-slate-200 rounded-2xl text-sm font-black text-slate-600 hover:bg-slate-100 transition-colors">Cancel</button>
                    <button onclick="document.getElementById('companyForm').submit()" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl text-sm font-black hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20" x-text="$data.editCompanyId !== null ? 'Update Wing' : 'Register Wing'"></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fileManager() {
            return {
                uploadModal: false, 
                folderModal: false,
                categoryModal: false,
                companyModal: false,
                editCompanyId: null,
                editCategoryId: null,
                editDocumentId: null,
                search: '{{ request('search') }}',
                filterHeading: '{{ request('heading') }}',
                filterCategory: '{{ request('category') }}',
                filterNature: '{{ request('nature_of_record') }}',
                filterClassification: '{{ request('classification') }}',
                filterFileNo: '{{ request('file_no') }}',
                filterTitle: '{{ request('title') }}',
                filterNotePages: '{{ request('note_pages') }}',
                filterCorrespPages: '{{ request('corresp_pages') }}',
                filterDateFrom: '{{ request('date_from') }}',
                filterDateTo: '{{ request('date_to') }}',
                selectedFiles: [],
                currentPath: ['Home'],
                view: '{{ request('view', 'master-register') }}',
                fileError: '',
                uploading: false,
                openEditCompany(id, name, email, description) {
                    this.editCompanyId = id;
                    this.companyModal = true;
                    setTimeout(() => {
                        const form = document.getElementById('companyForm');
                        if (form) {
                            const nameInput = form.querySelector('input[name="name"]');
                            const emailInput = form.querySelector('input[name="email"]');
                            const descTextarea = form.querySelector('textarea[name="description"]');
                            
                            if (nameInput) nameInput.value = name || '';
                            if (emailInput) emailInput.value = email || '';
                            if (descTextarea) descTextarea.value = description || '';
                        }
                    }, 100);
                },
                closeCompanyModal() {
                    this.companyModal = false;
                    this.editCompanyId = null;
                    const form = document.getElementById('companyForm');
                    if (form) {
                        form.reset();
                    }
                },
                openEditCategory(id, code, name, retention) {
                    this.editCategoryId = id;
                    this.categoryModal = true;
                    setTimeout(() => {
                        const codeSelect = document.getElementById('categoryCode');
                        const nameInput = document.getElementById('categoryName');
                        const retentionTextarea = document.getElementById('categoryRetention');
                        
                        if (codeSelect) codeSelect.value = code || '';
                        if (nameInput) nameInput.value = name || '';
                        if (retentionTextarea) retentionTextarea.value = retention || '';
                    }, 100);
                },
                closeCategoryModal() {
                    this.categoryModal = false;
                    this.editCategoryId = null;
                    const form = document.getElementById('categoryForm');
                    if (form) {
                        form.reset();
                    }
                },
                openEditDocument(id, companyId, folderId, categoryId, nature, mainHeading, classification, dateOpening, fileNo, subjectTitle, notePages, correspPages, remarks) {
                    this.editDocumentId = id;
                    this.uploadModal = true;
                    setTimeout(() => {
                        const form = document.getElementById('uploadForm');
                        if (!form) return;

                        const companySelect = form.querySelector('select[name="company_id"]');
                        const folderInput = form.querySelector('input[name="folder_id"]');
                        const categorySelect = form.querySelector('select[name="category_id"]');
                        const natureSelect = form.querySelector('select[name="nature_of_record"]');
                        const mainHeadingSelect = form.querySelector('select[name="main_heading"]');
                        const classificationSelect = form.querySelector('select[name="classification"]');
                        const dateInput = form.querySelector('input[name="date_of_opening"]');
                        const fileNoInput = form.querySelector('input[name="file_no"]');
                        const subjectTextarea = form.querySelector('textarea[name="subject_title"]');
                        const notePagesInput = form.querySelector('input[name="note_pages"]');
                        const correspPagesInput = form.querySelector('input[name="corresp_pages"]');
                        const remarksTextarea = form.querySelector('textarea[name="remarks"]');

                        if (companySelect) companySelect.value = companyId || '';
                        if (folderInput) folderInput.value = folderId || '';
                        if (categorySelect) categorySelect.value = categoryId || '';
                        if (natureSelect) natureSelect.value = nature || 'Current';
                        if (mainHeadingSelect) mainHeadingSelect.value = mainHeading || 'General';
                        if (classificationSelect) classificationSelect.value = classification || 'General';
                        if (dateInput) dateInput.value = dateOpening || '';
                        if (fileNoInput) fileNoInput.value = fileNo || '';
                        if (subjectTextarea) subjectTextarea.value = subjectTitle || '';
                        if (notePagesInput) notePagesInput.value = notePages || 0;
                        if (correspPagesInput) correspPagesInput.value = correspPages || 0;
                        if (remarksTextarea) remarksTextarea.value = remarks || '';
                    }, 100);
                },
                closeUploadModal() {
                    this.uploadModal = false;
                    this.editDocumentId = null;
                    this.uploading = false;
                    const form = document.getElementById('uploadForm');
                    if (form) {
                        form.reset();
                    }
                },
                submitUploadForm() {
                    const form = document.getElementById('uploadForm');
                    if (!form) return;
                    
                    // Show loading state immediately
                    this.uploading = true;
                    console.log('📤 Form submission started at:', new Date().toISOString());
                    
                    // Submit form
                    form.submit();
                },
                validateFiles(event) {
                    const files = event.target.files;
                    const allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
                    this.fileError = '';
                    for (let i = 0; i < files.length; i++) {
                        if (!allowed.includes(files[i].type)) {
                            this.fileError = 'Only PDF and Images (JPG, PNG, WEBP) are allowed.';
                            event.target.value = '';
                            return;
                        }
                    }
                },
                openFolder(id) {
                    window.location.href = '?folder_id=' + id + '&view=folder-manager';
                },
                goBack(id) {
                    if (id) {
                        window.location.href = '?folder_id=' + id + '&view=folder-manager';
                    } else {
                        window.location.href = '?view=folder-manager';
                    }
                }
            }
        }

        function confirmDelete(formId, message) {
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }
    </script>
</x-admin-layout>
