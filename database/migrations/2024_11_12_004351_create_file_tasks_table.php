<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileTasksTable extends Migration
{
    public function up()
    {
        Schema::create('file_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_id')->unique();
            $table->string('filename');
            $table->string('status')->default('queued'); // Default status is 'queued'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_tasks');
    }
}
