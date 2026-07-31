<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('folder_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->enum('nature_of_record', ['Current', 'Non-current'])->default('Current');
            $table->string('main_heading')->nullable();
            $table->enum('classification', ['General', 'Confidential'])->default('General');
            $table->date('date_of_opening')->nullable();
            $table->string('file_no')->nullable();
            $table->string('subject_title');
            $table->integer('note_pages')->default(0);
            $table->integer('corresp_pages')->default(0);
            $table->text('remarks')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
