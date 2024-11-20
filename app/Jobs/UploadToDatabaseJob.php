<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UploadToDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filename;


    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    public function handle()
    {
        $finalDir = 'uploads/final/';  // Directory within the public disk
        $uploadDir = 'uploads/';       // Permanent upload directory within the public disk
        $finalFilePath = $finalDir . $this->filename;

        try {
            // Check if the file exists in the final folder
            if (Storage::disk('public')->exists($finalFilePath)) {
                $fileSize = Storage::disk('public')->size($finalFilePath);
                $fileExtension = strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));

                // Validate file size and type (similar checks as in the controller)
                $maxFileSize = 3 * 1024 * 1024; // 3MB
                $allowedFileTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'xlsm'];
                if ($fileSize > $maxFileSize || !in_array($fileExtension, $allowedFileTypes)) {
                    Log::warning("File exceeds size or is an invalid type: " . $this->filename);
                    return;
                }

                // Generate a unique hash for the file to avoid naming conflicts
                $hashedFileName = uniqid() . '.' . $fileExtension;
                $newUploadPath = $uploadDir . $hashedFileName;

                // Move the file from public/uploads/final/ to public/uploads/
                if (Storage::disk('public')->move($finalFilePath, $newUploadPath)) {
                    // Insert file details into the database
                    DB::table('files')->insert([
                        'name' => $this->filename,
                        'path' => $newUploadPath,
                        'size' => $fileSize,
                        'uploaded_at' => now(),
                    ]);

                    Log::info('File successfully uploaded and moved to uploads/ folder', ['filename' => $this->filename]);
                } else {
                    Log::error('Error moving file to uploads/ folder', ['filename' => $this->filename]);
                }
            } else {
                Log::error('File not found in final folder', ['filename' => $this->filename]);
            }
        } catch (\Exception $e) {
            Log::error('Error in UploadToDatabaseJob: ' . $e->getMessage(), ['filename' => $this->filename]);
        }
    }
}
