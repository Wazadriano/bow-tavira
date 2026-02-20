<?php

namespace App\Services;

use App\Models\WorkItem;
use Illuminate\Support\Carbon;

class ICalendarService
{
    public function generateTaskEvent(WorkItem $workItem): string
    {
        $now = Carbon::now('UTC')->format('Ymd\THis\Z');
        $uid = "bow-workitem-{$workItem->id}@tavira-bow";
        $summary = $this->escapeIcal("BOW - {$workItem->ref_no}: {$workItem->description}");
        $description = $this->escapeIcal($workItem->description ?? '');

        /** @var Carbon|null $deadline */
        $deadline = $workItem->deadline;
        $dtstart = $deadline !== null ? $deadline->format('Ymd') : Carbon::today()->format('Ymd');
        $dtend = $deadline !== null ? $deadline->copy()->addDay()->format('Ymd') : Carbon::tomorrow()->format('Ymd');

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Tavira BOW//Task Reminder//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$now}",
            "DTSTART;VALUE=DATE:{$dtstart}",
            "DTEND;VALUE=DATE:{$dtend}",
            "SUMMARY:{$summary}",
            "DESCRIPTION:{$description}",
            'STATUS:CONFIRMED',
            'BEGIN:VALARM',
            'TRIGGER:-PT1H',
            'ACTION:DISPLAY',
            'DESCRIPTION:Task deadline reminder',
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }

    private function escapeIcal(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);

        return $text;
    }
}
