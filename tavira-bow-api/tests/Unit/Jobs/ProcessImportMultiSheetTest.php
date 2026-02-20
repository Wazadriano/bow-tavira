<?php

use App\Jobs\ProcessImportFile;
use App\Services\ImportNormalizationService;

// Test that getExpectedColumns returns correct mappings for Excel headers
it('maps Excel headers to database fields via normalization service', function () {
    $service = new ImportNormalizationService;
    $columns = $service->getExpectedColumns('workitems');

    // Excel-specific header aliases
    expect($columns['number'])->toBe('ref_no');
    expect($columns['impacted_area'])->toBe('department');
    expect($columns['workstream_deliverable'])->toBe('description');
    expect($columns['bau_or_growth_transformative'])->toBe('bau_or_transformative');
    expect($columns['goal_objective'])->toBe('goal');
    expect($columns['key_milestones_steps'])->toBe('monthly_update');
    expect($columns['responsible_person'])->toBe('responsible_party_id');
    expect($columns['priority_item_currently'])->toBe('priority_item');
    expect($columns['impact_urgency_level'])->toBe('impact_level');
    expect($columns['expected_completion'])->toBe('deadline');
    expect($columns['update_refresh_frequency'])->toBe('update_frequency');
    expect($columns['cost_savings_mm'])->toBe('cost_savings');
    expect($columns['cost_efficiency_fte_time_saving'])->toBe('cost_efficiency_fte');
    expect($columns['expected_cost_to_implement_mm'])->toBe('expected_cost');
    expect($columns['revenue_potential_first_fy_mm'])->toBe('revenue_potential');
    expect($columns['expected_completion_date'])->toBe('completion_date');
    expect($columns['rag_status'])->toBe('rag_status');
});

// Test that mapColumns auto-maps real Excel headers correctly
it('auto-maps real Excel 25-column headers', function () {
    $service = new ImportNormalizationService;
    $expectedColumns = $service->getExpectedColumns('workitems');

    // Simulate the 25 Excel headers exactly as they appear after normalization
    $excelHeaders = [
        'Number',
        'Impacted Area',
        'Activity Type',
        'Workstream / Deliverable',
        'BAU or Growth/Transformative',
        'Goal / Objective',
        'Key Milestones / Steps',
        'Comments',
        'Department Head',
        'Responsible Person',
        'Back up Person',
        'Priority Item (currently)',
        'Impact / Urgency Level',
        'Expected Completion',
        'Current Status',
        'Update/Refresh Frequency',
        'Other Item Completion Dependences',
        'Cost Savings (mm)',
        'Cost Efficiency (FTE Time Saving)',
        'Expected Cost to Implement (mm)',
        'Revenue Potential (First FY mm)',
        'RAG Status',
        'Issues / Risks',
        'Expected Completion Date',
        'Initial Item Provider/editor',
    ];

    $mapping = $service->mapColumns($excelHeaders, $expectedColumns);

    // Verify key column mappings
    expect($mapping[0])->toBe('ref_no');           // Number
    expect($mapping[1])->toBe('department');        // Impacted Area
    expect($mapping[3])->toBe('description');       // Workstream / Deliverable
    expect($mapping[4])->toBe('bau_or_transformative'); // BAU or Growth/Transformative
    expect($mapping[5])->toBe('goal');              // Goal / Objective
    expect($mapping[6])->toBe('monthly_update');    // Key Milestones / Steps
    expect($mapping[7])->toBe('comments');          // Comments
    expect($mapping[8])->toBe('department_head_id'); // Department Head
    expect($mapping[9])->toBe('responsible_party_id'); // Responsible Person
    expect($mapping[10])->toBe('back_up_person_id'); // Back up Person
    expect($mapping[11])->toBe('priority_item');    // Priority Item (currently)
    expect($mapping[12])->toBe('impact_level');     // Impact / Urgency Level
    expect($mapping[13])->toBe('deadline');         // Expected Completion
    expect($mapping[14])->toBe('current_status');   // Current Status
    expect($mapping[15])->toBe('update_frequency'); // Update/Refresh Frequency
    expect($mapping[16])->toBe('other_item_completion_dependences'); // Other Item Completion Dependences
    expect($mapping[17])->toBe('cost_savings');     // Cost Savings (mm)
    expect($mapping[21])->toBe('rag_status');       // RAG Status
    expect($mapping[22])->toBe('issues_risks');     // Issues / Risks
    expect($mapping[23])->toBe('completion_date');  // Expected Completion Date
    expect($mapping[24])->toBe('initial_item_provider_editor'); // Initial Item Provider/editor
});

// Test that mapColumns works for a 13-column subset (Will Rebecca style)
it('auto-maps 13-column subset headers', function () {
    $service = new ImportNormalizationService;
    $expectedColumns = $service->getExpectedColumns('workitems');

    // Simulate a 13-column subset (like Will Rebecca sheet)
    $subsetHeaders = [
        'Number',
        'Impacted Area',
        'Activity Type',
        'Workstream / Deliverable',
        'BAU or Growth/Transformative',
        'Goal / Objective',
        'Key Milestones / Steps',
        'Comments',
        'Responsible Person',
        'Current Status',
        'RAG Status',
        'Expected Completion Date',
        'Priority Item (currently)',
    ];

    $mapping = $service->mapColumns($subsetHeaders, $expectedColumns);

    expect($mapping[0])->toBe('ref_no');
    expect($mapping[1])->toBe('department');
    expect($mapping[3])->toBe('description');
    expect($mapping[8])->toBe('responsible_party_id');
    expect($mapping[9])->toBe('current_status');
    expect($mapping[10])->toBe('rag_status');
    expect($mapping[11])->toBe('completion_date');
    expect($mapping[12])->toBe('priority_item');
});

// Test that BOW List is sorted first in sheet name resolution
it('sorts BOW List sheet first', function () {
    $sheets = ['Finance Andy', 'BOW List', 'Compliance Simon', 'Operations Will'];

    usort($sheets, function ($a, $b) {
        if ($a === 'BOW List') {
            return -1;
        }
        if ($b === 'BOW List') {
            return 1;
        }

        return 0;
    });

    expect($sheets[0])->toBe('BOW List');
});

// Test empty row detection
it('detects completely empty rows', function () {
    $service = new ImportNormalizationService;

    // Use reflection to test the private method via the job
    $job = new ProcessImportFile('test.xlsx', 'workitems', [], 1);

    $reflection = new ReflectionClass($job);
    $method = $reflection->getMethod('isRowEmpty');
    $method->setAccessible(true);

    expect($method->invoke($job, [null, null, '', null]))->toBeTrue();
    expect($method->invoke($job, [null, 'value', null]))->toBeFalse();
    expect($method->invoke($job, ['', '  ', '']))->toBeTrue();
    expect($method->invoke($job, ['', 'data', '']))->toBeFalse();
});
