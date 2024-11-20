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

class CheckStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $taskId;
    protected $filename;

    public $tries = 10;
    public $retryAfter = 15;

    public function __construct($taskId, $filename)
    {
        $this->taskId = $taskId;
        $this->filename = $filename;
    }

    public function handle()
    {
        $apiUrl = 'https://10.17.98.124/apineo/55b7a0f2d0aa77fd0aaaff47168db0b2/tasks/status/' . urlencode($this->taskId);
        $apiKey = config('services.sorb.api_key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->withOptions(['verify' => false])
              ->get($apiUrl);

            Log::info("Checking status for Task ID: {$this->taskId}, Filename: {$this->filename}");

            if ($response->successful()) {
                $statusResponse = $response->json();

                // Update status in the database if processing is complete
                if (isset($statusResponse['data']) && $statusResponse['data'] === 'reported') {
                    Log::info("File processing complete for Task ID: {$this->taskId}");
                    FileTask::where('task_id', $this->taskId)->update(['status' => 'complete']);

                    // Dispatch GenerateReportJob once the status is confirmed as "complete"
                    GenerateReportJob::dispatch($this->taskId, $this->filename);
                    Log::info("Dispatched GenerateReportJob for Task ID: {$this->taskId}");

                } else {
                    Log::info("File is still processing for Task ID: {$this->taskId}");
                    FileTask::where('task_id', $this->taskId)->update(['status' => 'processing']);

                    // Re-dispatch the job with a delay if still processing
                    $this->release(30);
                }
            } else {
                Log::error("Failed to fetch status for Task ID: {$this->taskId}", [
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
                throw new \Exception("Failed to fetch status. HTTP Code: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("Error checking status for Task ID: {$this->taskId} - " . $e->getMessage());
            $this->release(15); // Retry with delay in case of an exception
        }
    }
}
