<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PdfProcessingService;
use App\Services\SearchablePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;


class DocumentController extends Controller
{
    protected $pdfService;
    protected $searchablePdfService;

    public function __construct(PdfProcessingService $pdfService, SearchablePdfService $searchablePdfService)
    {
        $this->pdfService = $pdfService;
        $this->searchablePdfService = $searchablePdfService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companiesQuery = \App\Models\Company::query();
        $categories = \App\Models\Category::all();
        
        if ($user->role === 'admin') {
            // Cache expensive companies + storage data for admin in Redis
            $companies = Cache::store('redis')->remember('files:companies_with_storage', now()->addMinutes(5), function () {
                $companies = \App\Models\Company::with(['adminUser'])->withCount(['documents', 'folders'])->get();

                foreach ($companies as $company) {
                    $totalSize = 0;
                    $documents = \App\Models\Document::where('company_id', $company->id)->get();
                    
                    foreach ($documents as $doc) {
                        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                            $totalSize += Storage::disk('public')->size($doc->file_path);
                        }
                    }
                    
                    $company->storage_bytes = $totalSize;
                    $company->storage_formatted = $this->formatBytes($totalSize);
                }

                return $companies;
            });
        } else {
            // Restrict companies for non-admins (no heavy caching needed)
            $companiesQuery->where('id', $user->company_id);
            $companies = $companiesQuery->with(['adminUser'])
                ->withCount(['documents', 'folders'])
                ->get();

            foreach ($companies as $company) {
                $totalSize = 0;
                $documents = \App\Models\Document::where('company_id', $company->id)->get();
                
                foreach ($documents as $doc) {
                    if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                        $totalSize += Storage::disk('public')->size($doc->file_path);
                    }
                }
                
                $company->storage_bytes = $totalSize;
                $company->storage_formatted = $this->formatBytes($totalSize);
            }
        }
        
        $parentId = $request->get('folder_id');
        $folderQuery = \App\Models\Folder::where('parent_id', $parentId);
        
        // Restrict folders for non-admins
        if ($user->role !== 'admin') {
            $folderQuery->where('company_id', $user->company_id);
        }
        $folders = $folderQuery->withCount('children')->get();
        
        $currentFolder = null;
        $breadcrumbs = [];
        if ($parentId) {
            $currentFolder = \App\Models\Folder::with('parent')->find($parentId);
            // Security check: ensure folder belongs to company
            if ($user->role !== 'admin' && $currentFolder && $currentFolder->company_id !== $user->company_id) {
                return redirect()->route('admin.files.index')->with('error', 'Unauthorized access to folder.');
            }

            if ($currentFolder) {
                $temp = $currentFolder;
                while ($temp) {
                    array_unshift($breadcrumbs, ['id' => $temp->id, 'name' => $temp->name]);
                    $temp = $temp->parent;
                }
            }
        }

        $query = \App\Models\Document::with(['company', 'category', 'folder', 'uploader']);

        // Filter by company_id if provided, otherwise restrict for non-admins
        if ($request->has('company_id') && $request->company_id != '') {
            $query->where('company_id', $request->company_id);
        } elseif ($user->role !== 'admin') {
            // Restrict documents for non-admins if no company_id filter
            $query->where('company_id', $user->company_id);
        }

        if ($request->has('search') && $request->search != '') {
            $search = trim($request->search);
            $searchLower = mb_strtolower($search);

            $query->where(function($q) use ($search, $searchLower) {
                // Case-insensitive search in subject_title and file_no
                $q->whereRaw('LOWER(subject_title) LIKE ?', ['%' . $searchLower . '%'])
                  ->orWhereRaw('LOWER(file_no) LIKE ?', ['%' . $searchLower . '%'])
                  // Search inside extracted_data JSON text (case-insensitive)
                  ->orWhereRaw('LOWER(JSON_EXTRACT(extracted_data, "$.text")) LIKE ?', ['%' . $searchLower . '%'])
                  // Also try direct JSON path search (MySQL 5.7+)
                  ->orWhereRaw('LOWER(extracted_data->>"$.text") LIKE ?', ['%' . $searchLower . '%']);
            });
        }

        if ($request->has('category') && $request->category != '') {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('code', $request->category);
            });
        }

        if ($request->has('heading') && $request->heading != '') {
            $query->where('main_heading', $request->heading);
        }

        if ($request->has('nature_of_record') && $request->nature_of_record != '') {
            $query->where('nature_of_record', $request->nature_of_record);
        }

        if ($request->has('classification') && $request->classification != '') {
            $query->where('classification', $request->classification);
        }

        if ($request->has('folder_id') && $request->folder_id != '') {
            $query->where('folder_id', $request->folder_id);
        }

        if ($request->has('file_no') && $request->file_no != '') {
            $query->where('file_no', 'like', '%' . $request->file_no . '%');
        }

        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('date_of_opening', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('date_of_opening', '<=', $request->date_to);
        }

        if ($request->has('title') && $request->title != '') {
            $query->where('subject_title', 'like', '%' . $request->title . '%');
        }

        if ($request->has('note_pages') && $request->note_pages != '') {
            $query->where('note_pages', '>=', (int) $request->note_pages);
        }

        if ($request->has('corresp_pages') && $request->corresp_pages != '') {
            $query->where('corresp_pages', '>=', (int) $request->corresp_pages);
        }

        $documents = $query->latest()->paginate(25);

        return view('admin.files.index', compact('companies', 'categories', 'folders', 'documents', 'breadcrumbs'));
    }

    public function store(Request $request)
    {
        $startTime = microtime(true);
        \Log::info('📤 UPLOAD START: Document upload request received', [
            'timestamp' => now()->toDateTimeString(),
            'file_size' => $request->hasFile('document') ? $request->file('document')->getSize() : 0,
            'file_name' => $request->hasFile('document') ? $request->file('document')->getClientOriginalName() : null,
        ]);

        // Step 1: Validation
        $validationStart = microtime(true);
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'folder_id' => 'nullable|exists:folders,id',
            'category_id' => 'required|exists:categories,id',
            'nature_of_record' => 'required|in:Current,Non-current',
            'main_heading' => 'nullable|string|max:255',
            'classification' => 'required|in:General,Confidential',
            'date_of_opening' => 'nullable|date',
            'file_no' => 'nullable|string|max:100|unique:documents,file_no,NULL,id,company_id,' . $request->company_id,
            'subject_title' => 'required|string|max:255',
            'note_pages' => 'integer|min:0',
            'corresp_pages' => 'integer|min:0',
            'remarks' => 'nullable|string',
            'document' => 'required|file|mimes:pdf,jpg,png,webp|max:51200', // 50MB
        ]);
        $validationTime = round((microtime(true) - $validationStart) * 1000, 2);
        \Log::info('⏱️ STEP 1: Validation completed', [
            'time_ms' => $validationTime,
            'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);

        // Step 2: File Storage
        $fileStorageStart = microtime(true);
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('documents', 'public');
            $validated['file_path'] = $path;
            // Don't extract text or convert PDF immediately - do it in background for fast response
            $validated['extracted_data'] = null;
        }
        $fileStorageTime = round((microtime(true) - $fileStorageStart) * 1000, 2);
        \Log::info('⏱️ STEP 2: File storage completed', [
            'time_ms' => $fileStorageTime,
            'file_path' => $validated['file_path'] ?? null,
            'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);

        // Step 3: Database Save
        $dbSaveStart = microtime(true);
        $validated['uploaded_by'] = auth()->id();
        $document = \App\Models\Document::create($validated);
        $dbSaveTime = round((microtime(true) - $dbSaveStart) * 1000, 2);
        \Log::info('⏱️ STEP 3: Database save completed', [
            'time_ms' => $dbSaveTime,
            'document_id' => $document->id,
            'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);

        // Step 4: Dispatch Background Job
        $jobDispatchTime = 0;
        $jobDispatchStart = microtime(true);
        if ($request->hasFile('document')) {
            try {
                \App\Jobs\ExtractDocumentTextJob::dispatchAfterResponse($document->id);
                $jobDispatchTime = round((microtime(true) - $jobDispatchStart) * 1000, 2);
                \Log::info('⏱️ STEP 4: Background job dispatched', [
                    'time_ms' => $jobDispatchTime,
                    'document_id' => $document->id,
                    'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ]);
            } catch (\Exception $e) {
                $jobDispatchTime = round((microtime(true) - $jobDispatchStart) * 1000, 2);
                \Log::warning('⚠️ STEP 4: Failed to dispatch extraction job', [
                    'time_ms' => $jobDispatchTime,
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                    'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ]);
                // Document is saved, extraction can be done via re-extract button
            }
        }

        // Step 5: Response
        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        \Log::info('✅ UPLOAD COMPLETE: Response sent to user', [
            'total_time_ms' => $totalTime,
            'breakdown' => [
                'validation_ms' => $validationTime,
                'file_storage_ms' => $fileStorageTime,
                'database_save_ms' => $dbSaveTime,
                'job_dispatch_ms' => $jobDispatchTime,
            ],
            'document_id' => $document->id,
        ]);

        // IMMEDIATE response - no waiting for processing
        return redirect()->back()->with('success', 'Document uploaded successfully! PDF conversion and text extraction are processing in the background.');
    }

    public function update(Request $request, string $id)
    {
        $startTime = microtime(true);
        \Log::info('📤 UPDATE START: Document update request received', [
            'timestamp' => now()->toDateTimeString(),
            'document_id' => $id,
            'has_new_file' => $request->hasFile('document'),
            'file_size' => $request->hasFile('document') ? $request->file('document')->getSize() : 0,
            'file_name' => $request->hasFile('document') ? $request->file('document')->getClientOriginalName() : null,
        ]);

        $document = \App\Models\Document::findOrFail($id);

        // Step 1: Validation
        $validationStart = microtime(true);
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'folder_id' => 'nullable|exists:folders,id',
            'category_id' => 'required|exists:categories,id',
            'nature_of_record' => 'required|in:Current,Non-current',
            'main_heading' => 'nullable|string|max:255',
            'classification' => 'required|in:General,Confidential',
            'date_of_opening' => 'nullable|date',
            'file_no' => 'nullable|string|max:100',
            'subject_title' => 'required|string|max:255',
            'note_pages' => 'integer|min:0',
            'corresp_pages' => 'integer|min:0',
            'remarks' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,jpg,png,webp|max:51200', // 50MB
        ]);
        $validationTime = round((microtime(true) - $validationStart) * 1000, 2);
        \Log::info('⏱️ UPDATE STEP 1: Validation completed', [
            'time_ms' => $validationTime,
            'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);

        // Step 2: Handle file replacement if new file is uploaded
        $fileStorageStart = microtime(true);
        $needsExtraction = false;
        if ($request->hasFile('document')) {
            // Delete old file
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }
            // Store new file
            $path = $request->file('document')->store('documents', 'public');
            $validated['file_path'] = $path;
            // Don't extract text immediately - do it in background for fast response
            $validated['extracted_data'] = null;
            $needsExtraction = true;
        }
        $fileStorageTime = round((microtime(true) - $fileStorageStart) * 1000, 2);
        \Log::info('⏱️ UPDATE STEP 2: File storage completed', [
            'time_ms' => $fileStorageTime,
            'file_path' => $validated['file_path'] ?? $document->file_path,
            'needs_extraction' => $needsExtraction,
            'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);

        // Step 3: Database Update
        $dbUpdateStart = microtime(true);
        $document->update($validated);
        $dbUpdateTime = round((microtime(true) - $dbUpdateStart) * 1000, 2);
        \Log::info('⏱️ UPDATE STEP 3: Database update completed', [
            'time_ms' => $dbUpdateTime,
            'document_id' => $document->id,
            'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);

        // Step 4: Dispatch Background Job for text extraction (only if new file uploaded)
        $jobDispatchTime = 0;
        if ($needsExtraction) {
            $jobDispatchStart = microtime(true);
            try {
                \App\Jobs\ExtractDocumentTextJob::dispatchAfterResponse($document->id);
                $jobDispatchTime = round((microtime(true) - $jobDispatchStart) * 1000, 2);
                \Log::info('⏱️ UPDATE STEP 4: Background job dispatched', [
                    'time_ms' => $jobDispatchTime,
                    'document_id' => $document->id,
                    'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ]);
            } catch (\Exception $e) {
                $jobDispatchTime = round((microtime(true) - $jobDispatchStart) * 1000, 2);
                \Log::warning('⚠️ UPDATE STEP 4: Failed to dispatch extraction job', [
                    'time_ms' => $jobDispatchTime,
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                    'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ]);
                // Document is updated, extraction can be done via re-extract button
            }
        }

        // Step 5: Response
        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        \Log::info('✅ UPDATE COMPLETE: Response sent to user', [
            'total_time_ms' => $totalTime,
            'breakdown' => [
                'validation_ms' => $validationTime,
                'file_storage_ms' => $fileStorageTime,
                'database_update_ms' => $dbUpdateTime,
                'job_dispatch_ms' => $jobDispatchTime,
            ],
            'document_id' => $document->id,
            'needs_extraction' => $needsExtraction,
        ]);

        // IMMEDIATE response - no waiting for processing
        $message = $needsExtraction 
            ? 'Document updated successfully! PDF conversion and text extraction are processing in the background.'
            : 'Document updated successfully';
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Fast extraction method - Tesseract only, no EasyOCR (for immediate response).
     * Used when queue is not available or for quick processing.
     */
    private function extractDocumentDataFast(string $filePath): ?array
    {
        try {
            $absolutePath = Storage::disk('public')->path($filePath);
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

            if ($extension === 'pdf') {
                // Fast path: Tesseract only, no EasyOCR
                $extractedText = $this->pdfService->processFast($absolutePath);
                if ($extractedText !== '') {
                    $extractedText = $this->cleanExtractedText($extractedText);
                    return [
                        'type' => 'pdf',
                        'text' => $extractedText,
                    ];
                }
            }

            // Images: direct Tesseract OCR
            $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($extension, $imageExtensions, true)) {
                $tesseractBinary = $this->findTesseractBinary();
                if ($tesseractBinary) {
                    $cmd = escapeshellarg($tesseractBinary) . ' ' . escapeshellarg($absolutePath) . ' stdout -l eng --psm 6 2>&1';
                    $output = @shell_exec($cmd);
                    if (is_string($output) && trim($output) !== '' && !str_contains($output, 'Error')) {
                        $output = $this->cleanExtractedText(trim($output));
                        return [
                            'type' => 'image',
                            'text' => $output,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Fast extraction failed', [
                'path' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }
        return null;
    }

    /**
     * Extract text from uploaded document (full extraction with EasyOCR).
     *
     * - PDF:
     *   1) Smalot\PdfParser se selectable text (text-based PDFs)
     *   2) Agar text empty ho (scanned PDF), to:
     *      a) Poppler (pdftoppm) se PDF pages ko images mein convert karo (150 DPI)
     *      b) Har page image ko individually Tesseract OCR karo
     *      c) Temp images ko immediately delete karo (memory efficient)
     *      d) Sab pages ka text combine karo
     *   3) Dono ko combine karke store karte hain
     * - Images: direct Tesseract OCR (CLI) (jpg, jpeg, png, webp)
     *
     * Kisi bhi failure pe: warning log + null return (upload flow break nahi hota).
     */
    private function extractDocumentData(string $filePath): ?array
    {
        try {
            $absolutePath = Storage::disk('public')->path($filePath);
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

            // --- 1) Handle PDF files: Use PdfProcessingService ---
            if ($extension === 'pdf') {
                $extractedText = $this->pdfService->process($absolutePath);

                if ($extractedText !== '') {
                    $extractedText = $this->cleanExtractedText($extractedText);
                    \Log::info('Document text extracted (PDF)', [
                        'path' => $filePath,
                        'extension' => $extension,
                        'length' => strlen($extractedText),
                    ]);
                    return [
                        'type' => 'pdf',
                        'text' => $extractedText,
                    ];
                }

                \Log::info('Document text extraction returned null for PDF (no parser/OCR text)', [
                    'path' => $filePath,
                    'extension' => $extension,
                ]);
                return null;
            }

            // --- 2) Image files (direct OCR) ---
            $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($extension, $imageExtensions, true)) {
                $tesseractBinary = $this->findTesseractBinary();
                
                if ($tesseractBinary) {
                    $cmd = escapeshellarg($tesseractBinary) . ' ' . escapeshellarg($absolutePath) . ' stdout 2>&1';
                    $output = @shell_exec($cmd);

                    if (is_string($output) && trim($output) !== '') {
                        // Filter out error messages
                        if (!str_contains($output, 'Error') && 
                            !str_contains($output, 'not recognized') &&
                            !str_contains($output, 'command not found')) {
                            
                            $output = $this->cleanExtractedText(trim($output));
                            \Log::info('Document text extracted (image - OCR)', [
                                'path' => $filePath,
                                'extension' => $extension,
                                'length' => strlen($output),
                            ]);
                            return [
                                'type' => 'image',
                                'text' => $output,
                            ];
                        }
                    }
                } else {
                    \Log::warning('Tesseract OCR binary not available or not in PATH', [
                        'path' => $filePath,
                        'extension' => $extension,
                    ]);
                }

                \Log::info('Document OCR returned empty output for image', [
                    'path' => $filePath,
                    'extension' => $extension,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Document text extraction failed', [
                'path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        \Log::info('Document text extraction final result is null', [
            'path' => $filePath,
            'reason' => 'no text from parser/OCR',
        ]);

        return null;
    }

    /**
     * Find Tesseract binary in system PATH (for image OCR).
     * Returns binary path or null if not found.
     *
     * @return string|null
     */
    private function findTesseractBinary(): ?string
    {
        // Common Windows paths
        $commonPaths = [
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try system PATH
        $binaries = ['tesseract', 'tesseract.exe'];
        foreach ($binaries as $binary) {
            $cmd = PHP_OS_FAMILY === 'Windows' 
                ? 'where ' . escapeshellarg($binary) . ' 2>&1'
                : 'which ' . escapeshellarg($binary) . ' 2>&1';
            
            $output = @shell_exec($cmd);
            
            if ($output && !str_contains($output, 'not found') && !str_contains($output, 'not recognized')) {
                $path = trim(explode("\n", $output)[0]);
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Clean extracted text so it is safe to store as JSON (valid UTF-8, no binary garbage).
     */
    private function cleanExtractedText(string $text): string
    {
        // Normalize line endings and strip null bytes
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace("\0", '', $text);

        // Ensure valid UTF-8, strip invalid sequences
        if (!mb_check_encoding($text, 'UTF-8')) {
            try {
                $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
                if (is_string($converted)) {
                    $text = $converted;
                }
            } catch (\Throwable $e) {
                // If iconv fails, last resort: remove non-printable characters
                $text = preg_replace('/[^\P{C}\n]+/u', '', $text) ?? '';
            }
        }

        return $text;
    }

    /**
     * Re-extract text from existing document and convert to searchable PDF (useful if OCR improved or data missing).
     */
    public function reExtract(string $id)
    {
        $document = \App\Models\Document::findOrFail($id);
        
        if (!$document->file_path) {
            return redirect()->back()->with('error', 'Document file not found.');
        }

        try {
            $absolutePath = Storage::disk('public')->path($document->file_path);
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

            // Convert PDF to searchable if it's a PDF
            if ($extension === 'pdf' && $this->searchablePdfService->isAvailable()) {
                \Log::info('Re-extract: Converting PDF to searchable', [
                    'document_id' => $id,
                ]);

                $searchablePdfPath = $this->searchablePdfService->convertToSearchable($absolutePath);

                if ($searchablePdfPath && $searchablePdfPath !== $absolutePath) {
                    // Replace original file with searchable PDF
                    $storagePath = Storage::disk('public')->path('');
                    $relativePath = str_replace($storagePath, '', $searchablePdfPath);
                    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

                    // Delete original file
                    Storage::disk('public')->delete($document->file_path);

                    // Update document with new searchable PDF path
                    $document->file_path = $relativePath;
                    $document->save();

                    // Update absolute path for text extraction
                    $absolutePath = $searchablePdfPath;

                    \Log::info('Re-extract: Original PDF replaced with searchable PDF', [
                        'document_id' => $id,
                        'new_path' => $relativePath,
                    ]);
                }
            }

            // Extract text
            $extractedData = $this->extractDocumentData($document->file_path);
            $document->extracted_data = $extractedData;
            $document->save();

            // Log extracted text snippet for debugging
            $textSnippet = '';
            if ($extractedData && isset($extractedData['text'])) {
                $text = $extractedData['text'];
                $textSnippet = mb_substr($text, 0, 500) . '...';
                \Log::info('Document re-extracted', [
                    'document_id' => $id,
                    'text_length' => strlen($text),
                    'text_snippet' => $textSnippet,
                    'contains_1798' => str_contains($text, '1798'),
                    'is_searchable_pdf' => $extension === 'pdf' && $this->searchablePdfService->isAvailable(),
                ]);
            }

            return redirect()->back()->with('success', 'Document re-extracted successfully. PDF converted to searchable format and text extracted.');
        } catch (\Throwable $e) {
            \Log::error('Re-extraction failed', [
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', 'Failed to re-extract text: ' . $e->getMessage());
        }
    }

    /**
     * Debug: Show extracted text for a document.
     */
    public function debugExtractedText(string $id)
    {
        $document = \App\Models\Document::findOrFail($id);
        
        $extractedText = '';
        if ($document->extracted_data && isset($document->extracted_data['text'])) {
            $extractedText = $document->extracted_data['text'];
        }

        return response()->json([
            'document_id' => $id,
            'file_path' => $document->file_path,
            'extracted_text_length' => strlen($extractedText),
            'extracted_text' => $extractedText,
            'contains_1798' => str_contains($extractedText, '1798'),
            'search_results' => [
                '1798' => str_contains($extractedText, '1798'),
                '1798 (case-insensitive)' => stripos($extractedText, '1798') !== false,
            ],
        ]);
    }

    public function destroy(string $id)
    {
        $document = \App\Models\Document::findOrFail($id);
        if ($document->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->file_path);
        }
        $document->delete();

        return redirect()->back()->with('success', 'Document deleted successfully');
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
