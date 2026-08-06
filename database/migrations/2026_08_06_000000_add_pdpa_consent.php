<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pdpa_consent_version')->nullable()->after('role');
            $table->timestamp('pdpa_consented_at')->nullable()->after('pdpa_consent_version');
        });

        // One row per candidate application consent — kept separate from
        // `applications` (owned by the Flask/Python side) and indexed by
        // email so a right-to-be-forgotten request can find every consent
        // tied to a candidate, account or not.
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->text('job_id')->nullable();
            $table->foreign('job_id')->references('id')->on('job_listings')->nullOnDelete();
            $table->string('notice_version');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pdpa_consent_version', 'pdpa_consented_at']);
        });
    }
};
