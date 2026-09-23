<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Compact "time left" wording used on countdown chips: "2d 4h", "3h 12m".
 */
class Countdown
{
    public static function format(CarbonInterface $until): string
    {
        $minutes = max(0, (int) now()->diffInMinutes($until, false));

        $days = intdiv($minutes, 60 * 24);
        $hours = intdiv($minutes % (60 * 24), 60);
        $remainingMinutes = $minutes % 60;

        return match (true) {
            $days > 0 => __('common.duration_days_hours', ['d' => $days, 'h' => $hours]),
            $hours > 0 => __('common.duration_hours_minutes', ['h' => $hours, 'm' => $remainingMinutes]),
            default => __('common.duration_minutes', ['m' => $remainingMinutes]),
        };
    }

    /**
     * Urgency bucket for colouring: under 6 hours is urgent, under 24 is soon.
     */
    public static function urgency(CarbonInterface $until): string
    {
        $hours = now()->diffInHours($until, false);

        return match (true) {
            $hours < 6 => 'urgent',
            $hours < 24 => 'soon',
            default => 'normal',
        };
    }
}
