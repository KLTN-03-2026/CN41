<?php

namespace Tests\Unit\Jobs;

use Modules\Quiz\Jobs\GenerateQuizJob;
use Modules\Upload\Jobs\TranscodeToHlsJob;
use Tests\TestCase;

class QueueAssignmentTest extends TestCase
{
    public function test_generate_quiz_job_is_on_ai_queue(): void
    {
        $job = new GenerateQuizJob(1);

        $this->assertSame('ai', $job->queue);
    }

    public function test_transcode_hls_job_is_on_hls_queue(): void
    {
        $job = new TranscodeToHlsJob(1);

        $this->assertSame('hls', $job->queue);
    }
}
