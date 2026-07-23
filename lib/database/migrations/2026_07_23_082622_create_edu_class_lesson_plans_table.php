<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A class can now draw from more than one lesson plan — the teacher picks
 * which plan a session follows when starting it (see StartSessionRequest),
 * rather than the class having a single implicit plan. `edu_classes.
 * lesson_plan_id` is kept untouched as-is (nothing currently reading it
 * changes); this pivot is the new source of truth for "which plans are
 * available to pick from", seeded here from each class's existing single plan
 * so nothing looks different to a class that has only ever had one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_class_lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained('edu_classes')->cascadeOnDelete();
            $table->foreignId('lesson_plan_id')->constrained('edu_lesson_plans')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_room_id', 'lesson_plan_id']);
        });

        DB::table('edu_classes')->select('id', 'lesson_plan_id')
            ->whereNotNull('lesson_plan_id')
            ->orderBy('id')
            ->chunk(200, function ($classes) {
                $now = now();
                DB::table('edu_class_lesson_plans')->insert(
                    $classes->map(fn ($c) => [
                        'class_room_id' => $c->id,
                        'lesson_plan_id' => $c->lesson_plan_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_class_lesson_plans');
    }
};
