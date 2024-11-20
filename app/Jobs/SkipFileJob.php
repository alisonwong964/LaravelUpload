<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SkipFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filename;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    public function handle()
    {
        $finalDir = 'uploads/final/';
        $finalFilePath = $finalDir . $this->filename;

        try {
            // Ensure the final folder exists
            if (!Storage::disk('public')->exists($finalDir)) {
                Storage::disk('public')->makeDirectory($finalDir);
            }

            // Check if the file exists and attempt deletion
            if (Storage::disk('public')->exists($finalFilePath)) {
                if (Storage::disk('public')->delete($finalFilePath)) {
                    Log::info('File successfully skipped and deleted from final folder', ['filename' => $this->filename]);
                } else {
                    Log::error('Error deleting file from final folder', ['filename' => $this->filename]);
                }
            } else {
                Log::error('File not found in final folder for deletion', ['filename' => $this->filename]);
            }
        } catch (\Exception $e) {
            Log::error('Error in SkipFileJob: ' . $e->getMessage(), ['filename' => $this->filename]);
        }
    }
}
