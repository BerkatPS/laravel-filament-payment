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
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'type')) {
                $table->enum('type', ['one_time', 'recurring', 'subscription'])->default('one_time')->after('price');
            }
            
            if (!Schema::hasColumn('services', 'features')) {
                $table->json('features')->nullable()->after('type');
            }
            
            if (!Schema::hasColumn('services', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('features');
            }
            
            if (!Schema::hasColumn('services', 'service_code')) {
                $table->string('service_code')->unique()->nullable()->after('is_active');
            }
            
            if (!Schema::hasColumn('services', 'duration_days')) {
                $table->unsignedInteger('duration_days')->nullable()->after('service_code');
            }
            
            if (!Schema::hasColumn('services', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->nullable()->after('duration_days');
            }
            
            if (!Schema::hasColumn('services', 'image_path')) {
                $table->string('image_path')->nullable()->after('discount_percentage');
            }
            
            if (!Schema::hasColumn('services', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'features',
                'is_active',
                'service_code',
                'duration_days',
                'discount_percentage',
                'image_path',
                'deleted_at'
            ]);
        });
    }
};
