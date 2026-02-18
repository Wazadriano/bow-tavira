<?php

use App\Models\WorkItem;
use App\Services\ICalendarService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = new ICalendarService;
});

it('generates valid ics format', function () {
    $workItem = new WorkItem([
        'ref_no' => 'IT-001',
        'description' => 'Test task',
        'deadline' => Carbon::parse('2026-03-15'),
    ]);
    $workItem->id = 42;

    $ics = $this->service->generateTaskEvent($workItem);

    expect($ics)
        ->toContain('BEGIN:VCALENDAR')
        ->toContain('END:VCALENDAR')
        ->toContain('BEGIN:VEVENT')
        ->toContain('END:VEVENT')
        ->toContain('VERSION:2.0')
        ->toContain('METHOD:PUBLISH');
});

it('includes correct DTSTART and DTEND from deadline', function () {
    $workItem = new WorkItem([
        'ref_no' => 'IT-002',
        'description' => 'Deadline task',
        'deadline' => Carbon::parse('2026-06-20'),
    ]);
    $workItem->id = 10;

    $ics = $this->service->generateTaskEvent($workItem);

    expect($ics)
        ->toContain('DTSTART;VALUE=DATE:20260620')
        ->toContain('DTEND;VALUE=DATE:20260621');
});

it('includes correct SUMMARY with ref_no and description', function () {
    $workItem = new WorkItem([
        'ref_no' => 'FIN-003',
        'description' => 'Quarterly audit',
        'deadline' => Carbon::parse('2026-04-01'),
    ]);
    $workItem->id = 5;

    $ics = $this->service->generateTaskEvent($workItem);

    expect($ics)->toContain('SUMMARY:BOW - FIN-003: Quarterly audit');
});

it('includes correct UID based on work item id', function () {
    $workItem = new WorkItem([
        'ref_no' => 'OPS-007',
        'description' => 'Ops task',
        'deadline' => Carbon::parse('2026-05-10'),
    ]);
    $workItem->id = 77;

    $ics = $this->service->generateTaskEvent($workItem);

    expect($ics)->toContain('UID:bow-workitem-77@tavira-bow');
});

it('escapes special characters in description', function () {
    $workItem = new WorkItem([
        'ref_no' => 'IT-010',
        'description' => "Task with, commas; semicolons\nand newlines",
        'deadline' => Carbon::parse('2026-07-01'),
    ]);
    $workItem->id = 99;

    $ics = $this->service->generateTaskEvent($workItem);

    expect($ics)
        ->toContain('\\,')
        ->toContain('\\;')
        ->toContain('\\n');
});

it('includes VALARM reminder', function () {
    $workItem = new WorkItem([
        'ref_no' => 'IT-011',
        'description' => 'Alarm test',
        'deadline' => Carbon::parse('2026-08-01'),
    ]);
    $workItem->id = 1;

    $ics = $this->service->generateTaskEvent($workItem);

    expect($ics)
        ->toContain('BEGIN:VALARM')
        ->toContain('TRIGGER:-PT1H')
        ->toContain('END:VALARM');
});
