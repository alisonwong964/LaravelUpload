<?php

namespace App\Jobs;

use App\Models\FileTaskReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $taskId;
    protected $filename;

    public function __construct($taskId, $filename)
    {
        $this->taskId = $taskId;
        $this->filename = $filename;
    }

    public function handle()
    {
        $apiKey = config('services.sorb.api_key');

        // Fetch Full Report
        $reportData = $this->fetchReport($apiKey);

        if ($reportData) {
            $malscore = $reportData['malscore'] ?? 0;
            $jsExists = (
                (isset($reportData['static']['pdf']['Keywords']['/JS']) && $reportData['static']['pdf']['Keywords']['/JS'] > 0) ||
                (isset($reportData['static']['pdf']['Keywords']['/JavaScript']) && $reportData['static']['pdf']['Keywords']['/JavaScript'] > 0)
            );
            $jsCount = ($reportData['static']['pdf']['Keywords']['/JS'] ?? 0) + ($reportData['static']['pdf']['Keywords']['/JavaScript'] ?? 0);

            Log::info('Full Report Response for Task ID: ' . $this->taskId, [
                'malscore' => $malscore,
                'js_exists' => $jsExists,
                'js_count' => $jsCount,
            ]);

            // Fetch Additional Report for URLs and malfamily
            $additionalReportData = $this->fetchAdditionalReport($apiKey);

            if ($additionalReportData) {
                $urls = $additionalReportData['urls'] ?? [];
                $malfamily = $additionalReportData['malfamily'] ?? 'None';

                Log::info('Additional Report Data for Task ID: ' . $this->taskId, [
                    'urls' => $urls,
                    'malscore' => $additionalReportData['malscore'] ?? 0,
                    'malfamily' => $malfamily,
                    'filename' => $this->filename,
                ]);

                // Save data to the `file_task_reports` table
                FileTaskReport::updateOrCreate(
                    ['task_id' => $this->taskId],
                    [
                        'malscore' => $malscore,
                        'js_count' => $jsCount,
                        'urls' => json_encode($urls),
                        'malfamily' => $malfamily,
                        'filename' => $this->filename,
                    ]
                );

                // Dispatch MoveFileJob with all data required
                MoveFileJob::dispatch($this->taskId, $malscore, $jsCount, $this->filename);
            } else {
                Log::error("Failed to fetch additional report for Task ID: {$this->taskId}");
            }
        } else {
            Log::error("Failed to fetch full report for Task ID: {$this->taskId}");
        }
    }

    protected function fetchReport($apiKey)
    {
        $apiUrl = 'https://10.17.98.124/apineo/55b7a0f2d0aa77fd0aaaff47168db0b2/tasks/get/report/' . urlencode($this->taskId);

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->withOptions(['verify' => false])
                ->get($apiUrl);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Failed to fetch report for task ID: ' . $this->taskId, [
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error fetching report for Task ID: {$this->taskId} - " . $e->getMessage());
        }
        return null;
    }

    protected function fetchAdditionalReport($apiKey)
    {
        $apiUrl = 'https://10.17.98.124/apineo/55b7a0f2d0aa77fd0aaaff47168db0b2/tasks/get/simple_report/' . urlencode($this->taskId);

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
                ->withOptions(['verify' => false])
                ->get($apiUrl);

            if ($response->successful()) {
                $decodedResponse = $response->json();
                return [
                    'task_id' => $decodedResponse['task_id'] ?? 'Unknown',
                    'malscore' => $decodedResponse['malscore'] ?? 0,
                    'malfamily' => $decodedResponse['malfamily'] ?? 'None',
                    'urls' => $decodedResponse['urls'] ?? [],
                ];
            } else {
                Log::error("Failed to fetch additional report for Task ID: {$this->taskId}", [
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error fetching additional report for Task ID: {$this->taskId} - " . $e->getMessage());
        }
        return null;
    }
}
