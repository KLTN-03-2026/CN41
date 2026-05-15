<?php

namespace Modules\Lessons\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Lessons\Models\Lesson;

class LessonProgressSeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = DB::table('students_course')->get();

        if ($enrollments->isEmpty()) {
            $this->command->warn('LessonProgressSeeder: No enrollments found. Run OrderSeeder first.');

            return;
        }

        $inserted = 0;

        foreach ($enrollments as $enrollment) {
            $lessons = Lesson::where('course_id', $enrollment->course_id)
                ->where('status', 1)
                ->orderBy('order')
                ->get(['id', 'duration']);

            if ($lessons->isEmpty()) {
                continue;
            }

            // Reproducible randomness per student+course combination
            mt_srand($enrollment->student_id * 1000 + $enrollment->course_id);

            $ratio = mt_rand(50, 70) / 100;
            $count = max(1, (int) round($lessons->count() * $ratio));
            $selected = $lessons->take($count); // watch in order

            $enrolledAt = Carbon::parse($enrollment->enrolled_at);

            foreach ($selected as $idx => $lesson) {
                $isCompleted = $idx < (int) ($count * 0.6); // first 60% are completed
                $duration = $lesson->duration ?? 600;

                $watchedSeconds = $isCompleted
                    ? $duration
                    : (int) ($duration * (mt_rand(30, 80) / 100));

                $completedAt = $isCompleted
                    ? $enrolledAt->copy()->addDays($idx + 1)->addHours(rand(1, 6))
                    : null;

                DB::table('lesson_progress')->insertOrIgnore([
                    'student_id' => $enrollment->student_id,
                    'lesson_id' => $lesson->id,
                    'course_id' => $enrollment->course_id,
                    'is_completed' => $isCompleted ? 1 : 0,
                    'watched_seconds' => $watchedSeconds,
                    'completed_at' => $completedAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $inserted++;
            }
        }

        $this->command->info("LessonProgressSeeder: Seeded {$inserted} lesson progress records.");
    }
}
