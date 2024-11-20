<?php

namespace App\Jobs;

use App\Models\FileTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadFileToSorbJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $filename;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->filename = basename($filePath);
    }

    public function handle()
    {
        $apiUrl = 'https://10.17.98.124/apineo/55b7a0f2d0aa77fd0aaaff47168db0b2/tasks/create/file/';
        $apiKey = config('services.sorb.api_key');

        try {
            if (Storage::disk('public')->exists($this->filePath)) {
                $fileContent = Storage::disk('public')->get($this->filePath);

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->attach('file', $fileContent, $this->filename)
                  ->withOptions(['verify' => false])
                  ->post($apiUrl);

                Log::info('SORB API response:', ['response' => $response->body()]);

                if ($response->successful()) {
                    $decodedResponse = $response->json();

                    if (isset($decodedResponse['task_ids'][0])) {
                        $taskId = $decodedResponse['task_ids'][0];
                        Log::info("Task ID retrieved from SORB API:", ['task_id' => $taskId]);

                        // Update the existing FileTask entry with the 'uploaded' status if necessary
                        FileTask::where('filename', $this->filename)->update(['status' => 'uploaded']);

                        // Dispatch CheckStatusJob
                        CheckStatusJob::dispatch($taskId, $this->filename);
                    } else {
                        Log::error('No task IDs returned by the SORB API');
                    }
                } else {
                    Log::error('Failed to upload file to SORB API', [
                        'status_code' => $response->status(),
                        'response' => $response->body(),
                    ]);

                    // Update the file task status to 'failed' if the API request fails
                    FileTask::where('filename', $this->filename)->update(['status' => 'failed']);
                }
            } else {
                Log::error('File not found in public disk: ' . $this->filePath);
            }
        } catch (\Exception $e) {
            Log::error('File upload to SORB failed: ' . $e->getMessage());

            // Update the file task status to 'error' in case of an exception
            FileTask::where('filename', $this->filename)->update(['status' => 'error']);
        } finally {
            // Storage::disk('public')->delete($this->filePath);
        }
    }
}
