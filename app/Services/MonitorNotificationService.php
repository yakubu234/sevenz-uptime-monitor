<?php

namespace App\Services;

use App\Mail\MonitorRecoveredMail;
use App\Mail\MonitorDownMail;
use App\Models\Monitor;
use Illuminate\Support\Facades\Mail;

class MonitorNotificationService
{
    public function sendDown(Monitor $monitor): void
    {
        $recipient = config('services.uptime.alert_email');

        if (! $recipient) {
            return;
        }

        Mail::to($recipient)->send(new MonitorDownMail($monitor));
    }

    public function sendRecovered(Monitor $monitor): void
    {
        $recipient = config('services.uptime.alert_email');

        if (! $recipient) {
            return;
        }

        Mail::to($recipient)->send(new MonitorRecoveredMail($monitor));
    }
}
