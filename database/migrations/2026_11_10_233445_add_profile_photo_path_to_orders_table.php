<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Tracks the storage path of a directly-uploaded profile photo.
            // Null when only a CDN URL is used.
            // e.g. "profile-photos/abc123.jpg"
            $table->string('profile_photo_path')->nullable()->after('profile_picture_url');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('profile_photo_path');
        });
    }
};
