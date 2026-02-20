<?php

use App\Services\ImportNormalizationService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

it('counts actual columns in header row ignoring nulls', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'ref_no');
    $sheet->setCellValue('B1', 'department');
    $sheet->setCellValue('C1', 'description');
    // D1 is null

    $count = ImportNormalizationService::getActualColumnCount($sheet);
    expect($count)->toBe(3);
});

it('returns 0 for empty sheet', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $count = ImportNormalizationService::getActualColumnCount($sheet);
    expect($count)->toBe(0);
});

it('handles gaps in header row', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'col_a');
    // B1 is null (gap)
    $sheet->setCellValue('C1', 'col_c');
    $sheet->setCellValue('D1', 'col_d');

    $count = ImportNormalizationService::getActualColumnCount($sheet);
    expect($count)->toBe(4);
});

it('converts sheet to limited array with correct dimensions', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // 3 headers
    $sheet->setCellValue('A1', 'ref_no');
    $sheet->setCellValue('B1', 'department');
    $sheet->setCellValue('C1', 'description');

    // 2 data rows
    $sheet->setCellValue('A2', 'BOW-001');
    $sheet->setCellValue('B2', 'IT');
    $sheet->setCellValue('C2', 'Test item');

    $sheet->setCellValue('A3', 'BOW-002');
    $sheet->setCellValue('B3', 'Finance');
    $sheet->setCellValue('C3', 'Another');

    $data = ImportNormalizationService::sheetToLimitedArray($sheet);

    expect($data)->toHaveCount(3); // 1 header + 2 data rows
    expect($data[0])->toHaveCount(3); // 3 columns
    expect($data[0][0])->toBe('ref_no');
    expect($data[1][0])->toBe('BOW-001');
    expect($data[2][2])->toBe('Another');
});

it('returns empty array for empty sheet', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    $data = ImportNormalizationService::sheetToLimitedArray($sheet);
    expect($data)->toBe([]);
});

it('does not return 16383 columns when Table object inflates sheet', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Simulate a sheet with only 5 real headers but getHighestDataColumn reports XFC
    $sheet->setCellValue('A1', 'Number');
    $sheet->setCellValue('B1', 'Department');
    $sheet->setCellValue('C1', 'Description');
    $sheet->setCellValue('D1', 'Status');
    $sheet->setCellValue('E1', 'RAG');

    $sheet->setCellValue('A2', 'BOW-001');
    $sheet->setCellValue('B2', 'IT');
    $sheet->setCellValue('C2', 'Test');
    $sheet->setCellValue('D2', 'In Progress');
    $sheet->setCellValue('E2', 'Green');

    $colCount = ImportNormalizationService::getActualColumnCount($sheet);
    expect($colCount)->toBe(5);

    $data = ImportNormalizationService::sheetToLimitedArray($sheet);
    expect($data[0])->toHaveCount(5);
    expect($data[1])->toHaveCount(5);
});
