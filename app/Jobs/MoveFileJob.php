<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MoveFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $taskId;
    protected $malscore;
    protected $jsCount;
    protected $filename;

    public function __construct($taskId, $malscore, $jsCount, $filename)
    {
        $this->taskId = $taskId;
        $this->malscore = $malscore;
        $this->jsCount = $jsCount;
        $this->filename = $filename;
    }

    public function handle()
    {
        $tempDir = 'uploads/temp/';
        $finalDir = 'uploads/final/';
        $tempFilePath = $tempDir . $this->filename;
        $finalFilePath = $finalDir . $this->filename;
        $resultMessage = '';

        try {
            Storage::disk('public')->makeDirectory($finalDir);

            if ($this->malscore < 6.5 && $this->jsCount === 0) {
                if (Storage::disk('public')->move($tempFilePath, $finalFilePath)) {
                    $resultMessage = 'File moved to final folder - File is safe';
                    Log::info($resultMessage, ['filename' => $this->filename]);
                } else {
                    $resultMessage = 'Failed to move file to final folder';
                    Log::error($resultMessage, ['filename' => $this->filename]);
                }
            } elseif ($this->jsCount > 0) {
                if (Storage::disk('public')->exists($tempFilePath) && Storage::disk('public')->delete($tempFilePath)) {
                    $resultMessage = 'File deleted due to JavaScript detection';
                    Log::warning($resultMessage, ['filename' => $this->filename]);
                } else {
                    $resultMessage = 'Failed to delete file with JavaScript';
                    Log::error($resultMessage, ['filename' => $this->filename]);
                }
            } elseif ($this->malscore >= 6.5) {
                if (Storage::disk('public')->exists($tempFilePath) && Storage::disk('public')->delete($tempFilePath)) {
                    $resultMessage = 'File deleted due to high malscore';
                    Log::warning($resultMessage, ['filename' => $this->filename]);
                } else {
                    $resultMessage = 'Failed to delete file with high malscore';
                    Log::error($resultMessage, ['filename' => $this->filename]);
                }
            } else {
                $resultMessage = 'No action needed for this file';
                Log::info($resultMessage, ['filename' => $this->filename]);
            }

            return $resultMessage;
        } catch (\Exception $e) {
            Log::error('Error in MoveFileJob for task ID: ' . $this->taskId, [
                'filename' => $this->filename,
                'exception' => $e->getMessage(),
            ]);
            return 'Error processing file action';
        }
    }


}
