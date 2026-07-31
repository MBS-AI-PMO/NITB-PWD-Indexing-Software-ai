<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EasyOCRService
{
    protected $pythonPath;
    protected $scriptPath;

    public function __construct()
    {
        // Try to find Python executable
        $this->pythonPath = $this->findPythonBinary();
        $this->scriptPath = base_path('scripts/easyocr_ocr.py');
    }

    /**
     * Check if EasyOCR is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->pythonPath) && file_exists($this->scriptPath);
    }

    /**
     * OCR an image using EasyOCR (better for handwritten text).
     *
     * @param string $imagePath Absolute path to image file
     * @param int $timeoutSeconds Maximum execution time in seconds (0 = no timeout)
     * @return string Extracted text
     */
    public function ocrImage(string $imagePath, int $timeoutSeconds = 0): string
    {
        if (!$this->isAvailable()) {
            Log::warning('❌ EasyOCR: Not available', [
                'python_path' => $this->pythonPath ?: 'Not found',
                'script_path' => $this->scriptPath,
                'script_exists' => file_exists($this->scriptPath),
            ]);
            return '';
        }

        Log::info('🔄 EasyOCR: Starting OCR process', [
            'image_path' => basename($imagePath),
            'python_path' => $this->pythonPath,
            'script_path' => $this->scriptPath,
        ]);

        try {
            $startTime = microtime(true);
            
            // Call Python script with image path
            $command = escapeshellarg($this->pythonPath) . ' ' . 
                       escapeshellarg($this->scriptPath) . ' ' . 
                       escapeshellarg($imagePath) . ' 2>&1';
            
            Log::debug('EasyOCR: Executing command', [
                'command' => $command,
                'timeout_seconds' => $timeoutSeconds,
            ]);
            
            // Use timeout if specified
            // Note: Windows timeout command doesn't work the same way as Linux
            // For Windows, we'll use proc_open with timeout, or skip timeout wrapper
            if ($timeoutSeconds > 0) {
                if (PHP_OS_FAMILY === 'Windows') {
                    // Windows: Use PowerShell with timeout or proc_open
                    // For simplicity, we'll use proc_open with timeout handling
                    $output = $this->executeWithTimeout($command, $timeoutSeconds);
                } else {
                    // Linux: Use timeout command
                    $command = 'timeout ' . $timeoutSeconds . ' ' . $command;
                    $output = @shell_exec($command);
                }
            } else {
                $output = @shell_exec($command);
            }
            $executionTime = round(microtime(true) - $startTime, 2);
            
            Log::info('EasyOCR: Command executed', [
                'execution_time_seconds' => $executionTime,
                'output_length' => strlen($output ?? ''),
            ]);
            
            if (is_string($output) && trim($output) !== '') {
                // Python script returns JSON with 'text' field
                $result = json_decode(trim($output), true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('❌ EasyOCR: Invalid JSON response', [
                        'image_path' => basename($imagePath),
                        'json_error' => json_last_error_msg(),
                        'raw_output' => substr($output, 0, 500),
                    ]);
                    return '';
                }
                
                if (isset($result['text'])) {
                    $text = trim($result['text']);
                    $textLength = strlen($text);
                    
                    // Check for handwritten patterns
                    $containsNumbers = preg_match('/\d{3,}/', $text);
                    $contains1798 = stripos($text, '1798') !== false;
                    
                    Log::info('✅ EasyOCR: Text extracted successfully', [
                        'image_path' => basename($imagePath),
                        'text_length' => $textLength,
                        'execution_time_seconds' => $executionTime,
                        'contains_numbers' => (bool)$containsNumbers,
                        'contains_1798' => $contains1798,
                        'text_preview' => mb_substr($text, 0, 300) . ($textLength > 300 ? '...' : ''),
                    ]);
                    
                    return $text;
                } elseif (isset($result['error'])) {
                    Log::error('❌ EasyOCR: Python script error', [
                        'image_path' => basename($imagePath),
                        'error' => $result['error'],
                        'execution_time_seconds' => $executionTime,
                    ]);
                } else {
                    Log::warning('⚠️ EasyOCR: Unexpected response format', [
                        'image_path' => basename($imagePath),
                        'response_keys' => array_keys($result ?? []),
                        'execution_time_seconds' => $executionTime,
                    ]);
                }
            } else {
                Log::warning('⚠️ EasyOCR: Empty output from Python script', [
                    'image_path' => basename($imagePath),
                    'execution_time_seconds' => $executionTime,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('❌ EasyOCR: Exception occurred', [
                'image_path' => basename($imagePath),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
        }

        return '';
    }

    /**
     * Find Python binary in system PATH.
     *
     * @return string|null
     */
    private function findPythonBinary(): ?string
    {
        // Common Python paths (Windows first since user is on Windows)
        $commonPaths = PHP_OS_FAMILY === 'Windows' 
            ? ['py', 'python', 'python3']  // Windows: py launcher first
            : ['python3', 'python'];

        foreach ($commonPaths as $python) {
            $cmd = PHP_OS_FAMILY === 'Windows' 
                ? 'where ' . escapeshellarg($python) . ' 2>&1'
                : 'which ' . escapeshellarg($python) . ' 2>&1';
            
            $output = @shell_exec($cmd);
            
            if ($output && !str_contains($output, 'not found') && !str_contains($output, 'not recognized')) {
                $path = trim(explode("\n", $output)[0]);
                
                // For Windows 'py' launcher, we need to use it directly
                if ($python === 'py' && PHP_OS_FAMILY === 'Windows') {
                    // py launcher works, verify it can run Python
                    $versionCmd = 'py --version 2>&1';
                    $versionOutput = @shell_exec($versionCmd);
                    if ($versionOutput && preg_match('/Python\s+3\./', $versionOutput)) {
                        Log::info('Python found via py launcher', [
                            'version_output' => trim($versionOutput),
                        ]);
                        return 'py'; // Return 'py' directly for Windows
                    }
                } elseif (file_exists($path)) {
                    // Verify Python version
                    $versionCmd = escapeshellarg($path) . ' --version 2>&1';
                    $versionOutput = @shell_exec($versionCmd);
                    if ($versionOutput && preg_match('/Python\s+3\./', $versionOutput)) {
                        Log::info('Python binary found', [
                            'path' => $path,
                            'version_output' => trim($versionOutput),
                        ]);
                        return $path;
                    }
                }
            }
        }

        Log::warning('Python binary not found in system PATH', [
            'checked_paths' => $commonPaths,
            'os_family' => PHP_OS_FAMILY,
        ]);

        return null;
    }

    /**
     * Execute command with timeout on Windows using proc_open.
     *
     * @param string $command Command to execute
     * @param int $timeoutSeconds Timeout in seconds
     * @return string Command output
     */
    private function executeWithTimeout(string $command, int $timeoutSeconds): string
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $process = @proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            Log::warning('EasyOCR: Failed to start process with timeout', [
                'command' => $command,
            ]);
            return '';
        }

        // Set pipes to non-blocking
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = '';
        $error = '';
        $startTime = time();
        $timeout = $timeoutSeconds;

        // Close stdin
        fclose($pipes[0]);

        // Read output until timeout or process completes
        while (true) {
            $status = proc_get_status($process);

            // Read stdout
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;

            if (stream_select($read, $write, $except, 1) > 0) {
                foreach ($read as $stream) {
                    if ($stream === $pipes[1]) {
                        $output .= stream_get_contents($pipes[1]);
                    } elseif ($stream === $pipes[2]) {
                        $error .= stream_get_contents($pipes[2]);
                    }
                }
            }

            // Check if process finished
            if (!$status['running']) {
                // Read remaining output
                $output .= stream_get_contents($pipes[1]);
                $error .= stream_get_contents($pipes[2]);
                break;
            }

            // Check timeout
            if ((time() - $startTime) >= $timeout) {
                // Kill process on timeout
                proc_terminate($process, 9); // SIGKILL
                Log::warning('EasyOCR: Process timeout, terminated', [
                    'timeout_seconds' => $timeoutSeconds,
                ]);
                break;
            }
        }

        // Close pipes
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        // Log errors if any
        if (!empty($error)) {
            Log::debug('EasyOCR: Process stderr', ['error' => substr($error, 0, 200)]);
        }

        return $output;
    }
}
