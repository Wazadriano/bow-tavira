<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('rejects requests without a file', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/import/preview', [
            'type' => 'workitems',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('rejects invalid file types', function () {
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

    $response = $this->actingAs($this->user)
        ->postJson('/api/import/preview', [
            'file' => $file,
            'type' => 'workitems',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

it('validates type parameter', function () {
    $file = UploadedFile::fake()->createWithContent(
        'test.csv',
        "ref_no,department,description\nBOW-001,IT,Test item\n"
    );

    $response = $this->actingAs($this->user)
        ->postJson('/api/import/preview', [
            'file' => $file,
            'type' => 'invalid_type',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

it('returns preview data for a valid CSV file', function () {
    $file = UploadedFile::fake()->createWithContent(
        'test.csv',
        "ref_no,department,description\nBOW-001,IT,Test item\nBOW-002,Finance,Another item\n"
    );

    $response = $this->actingAs($this->user)
        ->postJson('/api/import/preview', [
            'file' => $file,
            'type' => 'workitems',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'temp_file',
            'headers',
            'preview_rows',
            'total_rows',
            'column_mapping',
        ]);

    expect($response->json('total_rows'))->toBeGreaterThanOrEqual(1);
    expect($response->json('headers'))->toContain('ref_no');
});

it('returns sheets list for multi-sheet Excel files', function () {
    $tempPath = createTestXlsx([
        'Sheet1' => [
            ['ref_no', 'department', 'description'],
            ['BOW-001', 'IT', 'Test item'],
        ],
        'BOW List' => [
            ['ref_no', 'department', 'description'],
            ['BOW-010', 'Finance', 'BOW item'],
        ],
    ]);

    $file = new UploadedFile(
        $tempPath,
        'test.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $response = $this->actingAs($this->user)
        ->postJson('/api/import/preview', [
            'file' => $file,
            'type' => 'workitems',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'temp_file',
            'sheets',
            'selected_sheet',
        ]);

    expect($response->json('sheets'))->toHaveCount(2);
    expect($response->json('sheets'))->toContain('Sheet1', 'BOW List');
});

it('auto-selects BOW List sheet if present', function () {
    $tempPath = createTestXlsx([
        'Summary' => [
            ['col_a', 'col_b'],
            ['a1', 'b1'],
        ],
        'BOW List' => [
            ['ref_no', 'department', 'description'],
            ['BOW-001', 'IT', 'Item 1'],
        ],
        'Other' => [
            ['x', 'y'],
            ['1', '2'],
        ],
    ]);

    $file = new UploadedFile(
        $tempPath,
        'test.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $response = $this->actingAs($this->user)
        ->postJson('/api/import/preview', [
            'file' => $file,
            'type' => 'workitems',
        ]);

    $response->assertStatus(200);
    expect($response->json('selected_sheet'))->toBe('BOW List');
});

it('requires authentication', function () {
    $file = UploadedFile::fake()->createWithContent(
        'test.csv',
        "ref_no,department,description\nBOW-001,IT,Test\n"
    );

    $response = $this->postJson('/api/import/preview', [
        'file' => $file,
        'type' => 'workitems',
    ]);

    $response->assertStatus(401);
});

/**
 * Helper to create a real xlsx file with multiple sheets.
 */
function createTestXlsx(array $sheets): string
{
    $spreadsheet = new Spreadsheet;
    $first = true;

    foreach ($sheets as $sheetName => $data) {
        if ($first) {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($sheetName);
            $first = false;
        } else {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);
        }

        foreach ($data as $rowNum => $row) {
            foreach ($row as $colNum => $value) {
                $sheet->setCellValueByColumnAndRow($colNum + 1, $rowNum + 1, $value);
            }
        }
    }

    $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_').'.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempPath);

    return $tempPath;
}
