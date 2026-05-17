<?php

namespace App\Providers;

use App\Events\ExamCompleted;
use App\Listeners\SendExamResultNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ExamCompleted::class => [
            SendExamResultNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}