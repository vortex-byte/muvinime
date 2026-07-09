<?php

namespace Muvinime\Cron;

class Scheduler
{
    public function registerSchedules(array $schedules): array
    {
        if (!isset($schedules["10min"])) {
            $schedules["10min"] = [
                'interval' => 10 * 60,
                'display'  => __('Once every 10 minutes')
            ];
        }
        return $schedules;
    }
}
