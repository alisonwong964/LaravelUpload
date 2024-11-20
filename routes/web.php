<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\FileUploadController;

//Route::get('/home', [FileUploadController::class, 'index'])->name('uploads.home');
//Route::get('/files', [FileUploadController::class, 'list'])->name('files.list');
Route::post('/file-upload', [FileUploadController::class, 'upload'])->name('file.upload');
Route::post('/upload-to-sorb', [FileUploadController::class, 'uploadToSorb'])->name('file.uploadToSorb');
Route::get('/check-status', [FileUploadController::class, 'checkStatus'])->name('file.checkStatus');
Route::get('/get-full-report', [FileUploadController::class, 'getFullReport'])->name('file.getFullReport');
Route::get('/get-additional-report', [FileUploadController::class, 'getAdditionalReport'])->name('file.getAdditionalReport');
Route::get('/files', [FileUploadController::class, 'list'])->name('uploads.list'); // To view the list
Route::get('/files/{file_name}', [FileUploadController::class, 'view'])->name('files.view'); // To read/view file details
Route::get('/files/{id}/update', [FileUploadController::class, 'update'])->name('uploads.update'); // To update/edit file
Route::delete('/files/{id}', [FileUploadController::class, 'destroy'])->name('files.destroy'); // To delete file
Route::get('/home', [FileUploadController::class, 'index'])->name('uploads.home'); // For upload form (assuming it's your home page)
Route::get('/move-file', [FileUploadController::class, 'moveFile'])->name('file.move');
Route::post('/uploadDB', [FileUploadController::class, 'uploadToDatabase'])->name('uploadDB');
Route::post('/skip', [FileUploadController::class, 'skipFile'])->name('skip');
Route::get('/get-file-task-status', [FileUploadController::class, 'getFileTaskStatus'])->name('file.getTaskStatus');
Route::get('/file-task-report/{taskId}', [FileUploadController::class, 'getReportData'])->name('file-task-report.show');

