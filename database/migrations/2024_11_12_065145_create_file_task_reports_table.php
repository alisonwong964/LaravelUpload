<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileTaskReportsTable extends Migration
{
    public function up()
    {
        Schema::create('file_task_reports', function (Blueprint $table) {
            $table->id();
            $table->string('task_id')->unique(); // Links to task ID in file_tasks
            $table->decimal('malscore', 5, 2)->default(0); // Malscore with two decimal points
            $table->integer('js_count')->default(0); // JavaScript instance count
            $table->text('urls')->nullable(); // JSON-encoded list of URLs
            $table->string('malfamily')->default('None'); // Malicious family name
            $table->timestamps(); // Adds created_at and updated_at timestamps
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_task_reports');
    }
}
