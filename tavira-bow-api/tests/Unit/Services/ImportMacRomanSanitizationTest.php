<?php

use App\Services\ImportNormalizationService;

beforeEach(function () {
    $this->service = new ImportNormalizationService;
});

it('replaces MacRoman left double quote artifact', function () {
    expect($this->service->fixMacRomanArtifacts("test \u{00D2}value\u{00D3}"))
        ->toBe("test \u{201C}value\u{201D}");
});

it('replaces MacRoman right single quote artifact', function () {
    expect($this->service->fixMacRomanArtifacts("it\u{00D5}s working"))
        ->toBe("it\u{2019}s working");
});

it('replaces MacRoman en-dash artifact', function () {
    expect($this->service->fixMacRomanArtifacts("duties \u{00D0} appropriate"))
        ->toBe("duties \u{2013} appropriate");
});

it('does not alter valid French characters', function () {
    $french = 'hotel controle evaluation etat';
    expect($this->service->fixMacRomanArtifacts($french))->toBe($french);
});

it('does not alter clean ASCII text', function () {
    $ascii = 'Simple clean text with no special characters.';
    expect($this->service->fixMacRomanArtifacts($ascii))->toBe($ascii);
});

it('sanitizes Excel data array', function () {
    $data = [
        ['Header A', 'Header B'],
        ["value with \u{00D5}", 'clean value'],
        [null, 42],
    ];

    $result = $this->service->sanitizeExcelData($data);

    expect($result[0])->toBe(['Header A', 'Header B']);
    expect($result[1][0])->toBe("value with \u{2019}");
    expect($result[1][1])->toBe('clean value');
    expect($result[2][0])->toBeNull();
    expect($result[2][1])->toBe(42);
});

it('handles CSV content with MacRoman artifacts via parseCSV', function () {
    $csv = "col1,col2\nToB\u{00D5}s and rec\u{00D5}s \u{00D0} check,test";

    $result = $this->service->parseCSV($csv);

    expect($result[1][0])->toBe("ToB\u{2019}s and rec\u{2019}s \u{2013} check");
});
