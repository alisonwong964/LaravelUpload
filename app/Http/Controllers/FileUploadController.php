<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileTask;
use App\Models\FileTaskReport;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as PhpWordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as PhpSpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html as PhpSpreadsheetHtmlWriter;

use App\Jobs\UploadFileToSorbJob;
use App\Jobs\CheckStatusJob;
use App\Jobs\GenerateReportJob;
use App\Jobs\MoveFileJob;
use App\Jobs\UploadToDatabaseJob;
use App\Jobs\SkipFileJob;

class FileUploadController extends Controller
{
    public function index()
    {
        // Home Page
        return view('uploads.home');
    }

    public function uploadToSorb(Request $request)
    {
        Log::info('File upload request:', $request->all());

        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return response()->json(['status' => 'error', 'message' => 'No valid file uploaded.'], 400);
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        try {
            $apiUrl = 'https://10.17.98.124/apineo/55b7a0f2d0aa77fd0aaaff47168db0b2/tasks/create/file/';
            $apiKey = config('services.sorb.api_key');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->attach('file', file_get_contents($file->getRealPath()), $fileName)
            ->withOptions(['verify' => false])
            ->post($apiUrl);

            Log::info('SORB API response:', ['response' => $response->body()]);

            if ($response->successful()) {
                $decodedResponse = $response->json();

                if (isset($decodedResponse['task_ids'][0])) {
                    $taskId = $decodedResponse['task_ids'][0];
                    Log::info("Task ID retrieved from SORB API:", ['task_id' => $taskId]);

                    // Save the file temporarily only after a valid task_id is returned
                    $tempFolder = 'uploads/temp/';
                    Storage::disk('public')->makeDirectory($tempFolder);
                    $filePath = $file->storeAs($tempFolder, $fileName, 'public');

                    // Dispatch the UploadFileToSorbJob here
                    UploadFileToSorbJob::dispatch($filePath);

                    // Save to file_tasks table
                    FileTask::create([
                        'task_id' => $taskId,
                        'filename' => $fileName,
                        'status' => 'queued',
                    ]);

                    // Dispatch CheckStatusJob
                    CheckStatusJob::dispatch($taskId, $fileName);

                    return response()->json([
                        'status' => 'queued',
                        'task_id' => $taskId,
                        'message' => 'File upload has been queued for processing. Check status periodically.',
                    ]);
                } else {
                    return response()->json(['status' => 'error', 'message' => 'No task IDs returned by the API.'], 500);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to upload file to SORB API.',
                    'response' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Failed to queue file upload job: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to queue file upload.'], 500);
        }
    }

    public function getFileTaskStatus(Request $request)
    {
        $taskId = $request->query('task_id');
        if (!$taskId) {
            return response()->json(['status' => 'error', 'message' => 'No task_id provided'], 400);
        }

        // Query the database for the task status based on the provided task_id
        $fileTask = FileTask::where('task_id', $taskId)->first();

        if ($fileTask) {
            // Return the task current processing status
            return response()->json([
                'status' => 'success',
                'task_id' => $fileTask->task_id,
                'processing_status' => $fileTask->status,
                'filename' => $fileTask->filename,
            ]);
        } else {
            // Error handling
            return response()->json([
                'status' => 'error',
                'message' => 'Task ID not found.'
            ], 404);
        }
    }

    public function checkStatus(Request $request)
    {
        $taskId = $request->query('task_id');
        if (!$taskId) {
            return response()->json(['error' => 'No task_id provided'], 400);
        }

        // Retrieve the filename from the database based on taskId
        $fileTask = FileTask::where('task_id', $taskId)->first();
        if (!$fileTask) {
            return response()->json(['status' => 'error', 'message' => 'Task ID not found.'], 404);
        }
        $filename = $fileTask->filename;

        try {
            // Dispatch CheckStatusJob with both taskId and filename
            CheckStatusJob::dispatch($taskId, $filename);

            return response()->json(['status' => 'queued', 'message' => 'Status check has been queued.']);
        } catch (\Exception $e) {
            Log::error('Failed to queue status check job: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to queue status check.'], 500);
        }
    }

    public function getFullReport(Request $request)
    {
        $taskId = $request->query('task_id');
        $filename = $request->query('filename', 'Unknown');
        if (!$taskId) {
            return response()->json(['error' => 'No task_id provided'], 400);
        }

        try {
            // Dispatch GenerateReportJob (Full Report)
            GenerateReportJob::dispatch($taskId, $filename);

            return response()->json(['status' => 'queued', 'message' => 'Report generation has been queued.']);
        } catch (\Exception $e) {
            Log::error('Failed to queue report generation job: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to queue report generation.'], 500);
        }
    }

    public function getAdditionalReport(Request $request)
    {
        $taskId = $request->query('task_id');
        $filename = $request->query('filename', 'Unknown');
        if (!$taskId) {
            return response()->json(['error' => 'No task_id provided'], 400);
        }

        try {
            // Dispatch GenerateReportJob (Additional report)
            GenerateReportJob::dispatch($taskId, $filename);

            return response()->json(['status' => 'queued', 'message' => 'Additional report generation has been queued.']);
        } catch (\Exception $e) {
            Log::error('Failed to queue additional report generation job: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to queue additional report generation.'], 500);
        }
    }

    public function getReportData($taskId)
    {
        // Retrieve the report data from the file_task_reports table by task_id
        $report = FileTaskReport::where('task_id', $taskId)->first();

        if ($report) {
            return response()->json($report);
        } else {
            return response()->json(['error' => 'Report not found'], 404);
        }
    }


    public function moveFile(Request $request)
    {
        $taskId = $request->query('task_id');
        $malscore = $request->query('malscore');
        $jsCount = $request->query('js_count', 0);
        $filename = $request->query('filename');

        if (!$taskId || $malscore === null || !$filename) {
            return response()->json(['status' => 'error', 'message' => 'Missing required parameters.'], 400);
        }

        try {
            // Dispatch MoveFileJob and get the result message
            $moveFileJob = new MoveFileJob($taskId, $malscore, $jsCount, $filename);
            $resultMessage = dispatch_sync($moveFileJob);

            return response()->json(['status' => 'complete', 'message' => $resultMessage]);
        } catch (\Exception $e) {
            Log::error('Failed to queue move file job: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to queue file handling.'], 500);
        }
    }

    public function uploadToDatabase(Request $request)
    {
        $data = $request->validate([
            'filename' => 'required|string'
        ]);

        $filename = basename($data['filename']);

        try {
            // Dispatch the UploadFileToDatabaseJob to handle the file upload
            UploadToDatabaseJob::dispatch($filename);

            return response()->json(['status' => 'queued', 'message' => 'File upload to database has been queued.']);
        } catch (\Exception $e) {
            Log::error('Failed to queue upload file job: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to queue file upload to database.'], 500);
        }
    }

    public function skipFile(Request $request)
    {
        $data = $request->validate([
            'filename' => 'required|string'
        ]);

        $filename = basename($data['filename']);

        try {
            // Dispatch the SkipFileJob to delete the file
            SkipFileJob::dispatch($filename);

            return response()->json(['status' => 'queued', 'message' => 'File skip and delete has been queued.']);
        } catch (\Exception $e) {
            Log::error('Failed to queue skip file job: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to queue skip file.'], 500);
        }
    }


    public function list()
    {
        $files = File::all();
        return view('uploads.list', compact('files'));
    }



    public function view($name)
    {
        // Fetch the file record from the database using the original file name
        $file = DB::table('files')->where('name', $name)->first();

        if (!$file) {
            return view('uploads.view', [
                'error' => 'File not found in the database.'
            ]);
        }

        // Construct the path to the file stored in 'public/uploads/' (File name here is hashed)
        $filePath = $file->path;

        // Check if the file exists in the 'public' disk (which points to storage/app/public)
        if (!Storage::disk('public')->exists($filePath)) {
            return view('uploads.view', [
                'error' => 'File not found at path: ' . Storage::disk('public')->path($filePath)
            ]);
        }

        // Get the full path to the file in the 'storage/app/public' directory
        $fullPath = Storage::disk('public')->path($filePath);

        // Get the MIME type of the file
        $fileType = mime_content_type($fullPath);

        try {
            // Display PDF File
            if (strpos($fileType, 'application/pdf') !== false) {
                return response()->file($fullPath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $file->name . '"',
                ]);
            }
            // Display Image file
            elseif (strpos($fileType, 'image/') === 0) {
                return response()->file($fullPath, [
                    'Content-Type' => $fileType,
                    'Content-Disposition' => 'inline; filename="' . $file->name . '"',
                ]);
            }
            // Display Word file
            elseif (strpos($fileType, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') !== false || strpos($fileType, 'application/msword') !== false) {
                $phpWord = PhpWordIOFactory::load($fullPath);
                $htmlWriter = PhpWordIOFactory::createWriter($phpWord, 'HTML');

                ob_start();
                $htmlWriter->save("php://output");
                $content = ob_get_clean();

                return view('uploads.view', ['content' => $content]);
            }
            // Display Excel file
            elseif (strpos($fileType, 'application/vnd.ms-excel.sheet.macroEnabled.12') !== false || strpos($fileType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') !== false) {
                $spreadsheet = PhpSpreadsheetIOFactory::load($fullPath);
                $htmlWriter = new PhpSpreadsheetHtmlWriter($spreadsheet);

                ob_start();
                $htmlWriter->save("php://output");
                $content = ob_get_clean();

                return view('uploads.view', ['content' => $content]);
            }
            // Display Text file
            elseif (strpos($fileType, 'text/plain') !== false) {
                $content = "<pre>" . htmlspecialchars(file_get_contents($fullPath)) . "</pre>";
                return view('uploads.view', ['content' => $content]);
            } else {
                return view('uploads.view', ['error' => 'File type not supported for inline viewing.']);
            }
        } catch (\Exception $e) {
            return view('uploads.view', ['error' => 'Error processing file: ' . $e->getMessage()]);
        }
    }


    public function update(Request $request, $id)
    {
        // Validate the incoming request
        $request->validate([
            'file' => 'required|file|max:3072|mimes:jpg,jpeg,png,pdf,doc,docx,txt,xlsm',
        ]);

        // Fetch the current file details from the database
        $file = File::find($id);

        if (!$file) {
            return redirect()->route('uploads.list')->with('error', 'File not found.');
        }

        // Store the new file
        $originalFileName = $request->file('file')->getClientOriginalName();
        $fileExtension = $request->file('file')->extension();
        $hashedFileName = uniqid() . '.' . $fileExtension;
        $uploadDir = 'uploads/';
        $targetFilePath = $uploadDir . $hashedFileName;

        // Create uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Delete the old file from the server
        if (file_exists($file->path)) {
            unlink($file->path);
        }

        // Move the uploaded file
        if ($request->file('file')->move($uploadDir, $hashedFileName)) {
            // Update the file details in the database
            $file->name = $originalFileName;
            $file->path = $targetFilePath;
            $file->size = $request->file('file')->getSize();
            $file->uploaded_at = now();
            $file->save();

            return redirect()->route('uploads.list')->with('success', 'The file has been updated successfully.');
        } else {
            return redirect()->back()->with('error', 'Sorry, there was an error uploading the file.');
        }
    }


    public function destroy($id)
    {
        $file = File::findOrFail($id);
        Storage::delete($file->path);
        $file->delete();

        return redirect()->route('uploads.list')->with('success', 'File deleted successfully.');
    }

}
