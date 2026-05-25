<?php

namespace Tests\Feature\Jobs;

use Illuminate\Broadcasting\AnonymousEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Jobs\GenerateQuizJob;
use Modules\Quiz\Models\QuizGenerationJob;
use Modules\Quiz\Services\AIQuizService;
use Modules\Upload\Jobs\TranscodeToHlsJob;
use Modules\Upload\Models\MediaFile;
use Modules\Upload\Services\HlsService;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class BroadcastTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    public function test_generate_quiz_job_broadcasts_on_completion(): void
    {
        Event::fake([AnonymousEvent::class]);
        $this->setupAdmin();

        $lesson = Lesson::forceCreate([
            'course_id' => 1, 'title' => 'Test Lesson', 'slug' => 'test-lesson',
            'type' => 'video', 'order' => 1, 'status' => 1,
        ]);
        $jobRecord = QuizGenerationJob::create([
            'lesson_id' => $lesson->id,
            'status' => 'pending',
            'payload' => ['lesson_id' => $lesson->id, 'source' => 'chapter', 'count' => 1, 'max_attempts' => 3],
        ]);

        $this->mock(AIQuizService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractChapterPdfText')->andReturn('pdf text');
            $mock->shouldReceive('generateFromPdfText')->andReturn([[
                'question' => 'Q', 'option_a' => 'A', 'option_b' => 'B',
                'option_c' => 'C', 'option_d' => 'D', 'correct_option' => 'A',
            ]]);
        });

        GenerateQuizJob::dispatch($jobRecord->id);

        $this->assertDatabaseHas('quiz_generation_jobs', ['id' => $jobRecord->id, 'status' => 'done']);

        $expectedChannel = 'private-quiz-job.'.$jobRecord->id;
        Event::assertDispatched(AnonymousEvent::class, function (AnonymousEvent $event) use ($expectedChannel) {
            foreach ((array) $event->broadcastOn() as $channel) {
                $name = $channel instanceof PrivateChannel ? $channel->name : (string) $channel;
                if ($name === $expectedChannel) {
                    return true;
                }
            }

            return false;
        });
    }

    public function test_generate_quiz_job_broadcasts_on_failure(): void
    {
        Event::fake([AnonymousEvent::class]);
        $this->setupAdmin();

        $lesson = Lesson::forceCreate([
            'course_id' => 1, 'title' => 'Test Lesson 2', 'slug' => 'test-lesson-2',
            'type' => 'video', 'order' => 1, 'status' => 1,
        ]);
        $jobRecord = QuizGenerationJob::create([
            'lesson_id' => $lesson->id,
            'status' => 'pending',
            'payload' => ['lesson_id' => $lesson->id, 'source' => 'chapter', 'count' => 1, 'max_attempts' => 3],
        ]);

        $this->mock(AIQuizService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractChapterPdfText')->andReturn('pdf text');
            $mock->shouldReceive('generateFromPdfText')->andThrow(new \Exception('API error'));
        });

        // GenerateQuizJob catches exceptions internally, so no exception propagates here
        GenerateQuizJob::dispatch($jobRecord->id);

        $this->assertDatabaseHas('quiz_generation_jobs', ['id' => $jobRecord->id, 'status' => 'failed']);

        $expectedChannel = 'private-quiz-job.'.$jobRecord->id;
        Event::assertDispatched(AnonymousEvent::class, function (AnonymousEvent $event) use ($expectedChannel) {
            foreach ((array) $event->broadcastOn() as $channel) {
                $name = $channel instanceof PrivateChannel ? $channel->name : (string) $channel;
                if ($name === $expectedChannel) {
                    return true;
                }
            }

            return false;
        });
    }

    public function test_transcode_to_hls_job_broadcasts_progress(): void
    {
        Event::fake([AnonymousEvent::class]);

        $media = MediaFile::forceCreate([
            'disk' => 'local', 'type' => 'video', 'original_name' => 'test.mp4',
            'path' => 'videos/test.mp4', 'url' => '/storage/videos/test.mp4',
            'mime_type' => 'video/mp4', 'size' => 1000000,
        ]);

        $this->mock(HlsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('transcode')->andReturn(null);
        });

        TranscodeToHlsJob::dispatch($media->id);

        $expectedChannel = 'private-hls.'.$media->id;
        Event::assertDispatched(AnonymousEvent::class, function (AnonymousEvent $event) use ($expectedChannel) {
            foreach ((array) $event->broadcastOn() as $channel) {
                $name = $channel instanceof PrivateChannel ? $channel->name : (string) $channel;
                if ($name === $expectedChannel) {
                    return true;
                }
            }

            return false;
        });
    }

    public function test_transcode_to_hls_job_broadcasts_on_failure(): void
    {
        Event::fake([AnonymousEvent::class]);

        $media = MediaFile::forceCreate([
            'disk' => 'local', 'type' => 'video', 'original_name' => 'fail.mp4',
            'path' => 'videos/fail.mp4', 'url' => '/storage/videos/fail.mp4',
            'mime_type' => 'video/mp4', 'size' => 1000000,
        ]);

        $this->mock(HlsService::class, function (MockInterface $mock) {
            $mock->shouldReceive('transcode')->andThrow(new \Exception('transcode failed'));
        });

        // SyncQueue calls failed() then re-throws — catch the re-throw
        try {
            TranscodeToHlsJob::dispatch($media->id);
        } catch (\Exception $e) {
            // Expected: SyncQueue re-throws after calling failed()
        }

        $this->assertDatabaseHas('media_files', ['id' => $media->id, 'hls_status' => 'failed']);

        $expectedChannel = 'private-hls.'.$media->id;
        Event::assertDispatched(AnonymousEvent::class, function (AnonymousEvent $event) use ($expectedChannel) {
            foreach ((array) $event->broadcastOn() as $channel) {
                $name = $channel instanceof PrivateChannel ? $channel->name : (string) $channel;
                if ($name === $expectedChannel) {
                    return true;
                }
            }

            return false;
        });
    }
}
