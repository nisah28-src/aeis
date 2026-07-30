<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('job_postings', 'job_listings');
        Schema::rename('candidates', 'applications');

        Schema::table('job_listings', function (Blueprint $table) {
            $table->unsignedBigInteger('employer_id')->nullable()->after('id');
            $table->foreign('employer_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedBigInteger('candidate_user_id')->nullable()->after('job_id');
            $table->foreign('candidate_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('job_id')->references('id')->on('job_listings')->nullOnDelete();
        });

        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_user_id');
            $table->string('job_id');
            $table->foreign('candidate_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('job_id')->references('id')->on('job_listings')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['candidate_user_id', 'job_id']);
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['candidate_user_id']);
            $table->dropForeign(['job_id']);
            $table->dropColumn('candidate_user_id');
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropForeign(['employer_id']);
            $table->dropColumn('employer_id');
        });

        Schema::dropIfExists('saved_jobs');

        Schema::rename('applications', 'candidates');
        Schema::rename('job_listings', 'job_postings');
    }
};