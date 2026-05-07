<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Payment\Jobs\CancelPendingOrders;
use Modules\Quiz\Models\QuizGenerationJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CancelPendingOrders)->everyMinute();

// Xóa job records cũ hơn 7 ngày
Schedule::call(function () {
    QuizGenerationJob::where('created_at', '<', now()->subDays(7))->delete();
})->daily()->name('cleanup-quiz-generation-jobs');

// Xóa file PDF tạm cũ hơn 24h (phòng khi worker bị kill trước khi kịp tự xóa)
Schedule::call(function () {
    $dir = storage_path('app/quiz-tmp');
    if (! is_dir($dir)) {
        return;
    }
    foreach (new FilesystemIterator($dir) as $file) {
        if ($file->getMTime() < now()->subHours(24)->timestamp) {
            @unlink($file->getPathname());
        }
    }
})->daily()->name('cleanup-quiz-tmp-files');
