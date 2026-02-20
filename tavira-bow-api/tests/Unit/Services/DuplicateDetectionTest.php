<?php

use App\Models\GovernanceItem;
use App\Models\Risk;
use App\Models\RiskCategory;
use App\Models\RiskTheme;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkItem;
use App\Services\DuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(DuplicateDetectionService::class);
    $this->user = User::factory()->create();
    // Generate unique prefix to avoid seeder collisions
    $this->prefix = 'DTEST-'.substr(uniqid(), -6).'-';
});

describe('workitem duplicates', function () {
    it('detects exact ref_no match', function () {
        $ref = $this->prefix.'WI-001';
        WorkItem::create([
            'ref_no' => $ref,
            'description' => 'Existing task',
            'department' => 'IT',
        ]);

        $columnMapping = [0 => 'ref_no', 1 => 'description', 2 => 'department'];
        $rows = [[$ref, 'Some description', 'IT']];

        $result = $this->service->detect($rows, $columnMapping, 'workitems');

        expect($result)->toHaveCount(1);
        expect($result[0]['matches'][0]['match_type'])->toBe('exact_ref');
        expect($result[0]['matches'][0]['confidence'])->toBe(100);
        expect($result[0]['matches'][0]['action'])->toBe('update');
    });

    it('detects similar description in same department', function () {
        WorkItem::create([
            'ref_no' => $this->prefix.'WI-100',
            'description' => 'Implement automated quarterly reporting system for the finance department',
            'department' => 'DupTestFinance',
        ]);

        $columnMapping = [0 => 'ref_no', 1 => 'description', 2 => 'department'];
        $rows = [[$this->prefix.'WI-NEW', 'Implement automated quarterly reporting system for finance', 'DupTestFinance']];

        $result = $this->service->detect($rows, $columnMapping, 'workitems');

        expect($result)->toHaveCount(1);
        expect($result[0]['matches'][0]['match_type'])->toBe('similar_description');
        expect($result[0]['matches'][0]['confidence'])->toBeGreaterThanOrEqual(60);
    });

    it('returns empty for unique items', function () {
        WorkItem::create([
            'ref_no' => $this->prefix.'WI-200',
            'description' => 'Something completely different about HR processes',
            'department' => 'DupTestHR',
        ]);

        $columnMapping = [0 => 'ref_no', 1 => 'description', 2 => 'department'];
        $rows = [[$this->prefix.'WI-300', 'Brand new unique project for marketing department launch', 'DupTestMarketing']];

        $result = $this->service->detect($rows, $columnMapping, 'workitems');

        expect($result)->toBeEmpty();
    });
});

describe('supplier duplicates', function () {
    it('detects exact ref_no match', function () {
        $ref = $this->prefix.'SUP-001';
        Supplier::create([
            'ref_no' => $ref,
            'name' => 'Acme Corp',
            'status' => 'Active',
        ]);

        $columnMapping = [0 => 'ref_no', 1 => 'name'];
        $rows = [[$ref, 'Different Name']];

        $result = $this->service->detect($rows, $columnMapping, 'suppliers');

        expect($result)->toHaveCount(1);
        expect($result[0]['matches'][0]['match_type'])->toBe('exact_ref');
    });

    it('detects similar supplier name', function () {
        Supplier::create([
            'ref_no' => $this->prefix.'SUP-100',
            'name' => 'Microsoft Corporation',
            'status' => 'Active',
        ]);

        $columnMapping = [0 => 'ref_no', 1 => 'name'];
        $rows = [[$this->prefix.'SUP-200', 'Microsoft Corp']];

        $result = $this->service->detect($rows, $columnMapping, 'suppliers');

        expect($result)->toHaveCount(1);
        expect($result[0]['matches'][0]['match_type'])->toBe('similar_name');
        expect($result[0]['matches'][0]['confidence'])->toBeGreaterThanOrEqual(70);
    });
});

describe('risk duplicates', function () {
    it('detects exact ref_no match', function () {
        $shortId = substr(uniqid(), -4);
        $theme = RiskTheme::create([
            'code' => 'DT-'.$shortId,
            'name' => 'Dup Test Theme',
            'board_appetite' => 3,
            'order' => 99,
            'is_active' => true,
        ]);
        $category = RiskCategory::create([
            'theme_id' => $theme->id,
            'code' => 'DC-'.$shortId,
            'name' => 'Dup Test Category',
            'order' => 0,
            'is_active' => true,
        ]);
        $ref = $this->prefix.'R-001';
        Risk::create([
            'ref_no' => $ref,
            'name' => 'Existing risk',
            'category_id' => $category->id,
        ]);

        $columnMapping = [0 => 'ref_no', 1 => 'name'];
        $rows = [[$ref, 'Updated risk name']];

        $result = $this->service->detect($rows, $columnMapping, 'risks');

        expect($result)->toHaveCount(1);
        expect($result[0]['matches'][0]['match_type'])->toBe('exact_ref');
    });
});

describe('governance duplicates', function () {
    it('detects exact ref_no match', function () {
        $ref = $this->prefix.'GOV-001';
        GovernanceItem::create([
            'ref_no' => $ref,
            'description' => 'Annual compliance review',
            'department' => 'DupTestLegal',
        ]);

        $columnMapping = [0 => 'ref_no', 1 => 'description', 2 => 'department'];
        $rows = [[$ref, 'New description', 'DupTestLegal']];

        $result = $this->service->detect($rows, $columnMapping, 'governance');

        expect($result)->toHaveCount(1);
        expect($result[0]['matches'][0]['match_type'])->toBe('exact_ref');
    });

    it('detects similar description in same department', function () {
        GovernanceItem::create([
            'ref_no' => $this->prefix.'GOV-100',
            'description' => 'Monthly board meeting preparation and compliance review process',
            'department' => 'DupTestCompliance',
        ]);

        $columnMapping = [0 => 'ref_no', 1 => 'description', 2 => 'department'];
        $rows = [[$this->prefix.'GOV-NEW', 'Monthly board meeting preparation and compliance review', 'DupTestCompliance']];

        $result = $this->service->detect($rows, $columnMapping, 'governance');

        expect($result)->toHaveCount(1);
        expect($result[0]['matches'][0]['match_type'])->toBe('similar_description');
    });
});

describe('multiple rows', function () {
    it('processes all rows and returns matches for existing', function () {
        WorkItem::create(['ref_no' => $this->prefix.'WI-M1', 'description' => 'Task A', 'department' => 'IT']);
        WorkItem::create(['ref_no' => $this->prefix.'WI-M2', 'description' => 'Task B', 'department' => 'HR']);

        $columnMapping = [0 => 'ref_no', 1 => 'description', 2 => 'department'];
        $rows = [
            [$this->prefix.'WI-M1', 'Updated A', 'IT'],
            [$this->prefix.'WI-BRAND-NEW', 'Completely new', 'Marketing'],
            [$this->prefix.'WI-M2', 'Updated B', 'HR'],
        ];

        $result = $this->service->detect($rows, $columnMapping, 'workitems');

        expect($result)->toHaveCount(2);
        expect($result[0]['imported_ref'])->toBe($this->prefix.'WI-M1');
        expect($result[1]['imported_ref'])->toBe($this->prefix.'WI-M2');
    });

    it('returns correct row numbers with header offset', function () {
        WorkItem::create(['ref_no' => $this->prefix.'WI-ROW5', 'description' => 'X', 'department' => 'IT']);

        $columnMapping = [0 => 'ref_no', 1 => 'description', 2 => 'department'];
        $rows = [
            [$this->prefix.'WI-R1', 'New 1', 'IT'],
            [$this->prefix.'WI-R2', 'New 2', 'HR'],
            [$this->prefix.'WI-ROW5', 'Existing', 'IT'],
        ];

        $result = $this->service->detect($rows, $columnMapping, 'workitems');

        expect($result)->toHaveCount(1);
        expect($result[0]['row_number'])->toBe(4);
    });
});
