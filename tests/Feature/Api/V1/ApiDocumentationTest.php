<?php

use Illuminate\Support\Facades\Artisan;

function countPostmanRequests(array $items): int
{
    return collect($items)->sum(function (array $item): int {
        $count = isset($item['request']) ? 1 : 0;

        if (isset($item['item']) && is_array($item['item'])) {
            $count += countPostmanRequests($item['item']);
        }

        return $count;
    });
}

it('keeps api documentation and postman collection valid', function () {
    $docsPath = base_path('docs/api/v1.md');
    $collectionPath = base_path('docs/api/HARIS_API_v1.postman_collection.json');
    $environmentPath = base_path('docs/api/HARIS_API_v1.postman_environment.json');

    expect($docsPath)->toBeFile();
    expect($collectionPath)->toBeFile();
    expect($environmentPath)->toBeFile();

    $collection = json_decode(file_get_contents($collectionPath), true, flags: JSON_THROW_ON_ERROR);
    $environment = json_decode(file_get_contents($environmentPath), true, flags: JSON_THROW_ON_ERROR);

    expect($collection['info']['schema'])->toBe('https://schema.getpostman.com/json/collection/v2.1.0/collection.json');
    expect($environment['name'])->toBe('HARIS API v1 Local');
    expect(countPostmanRequests($collection['item']))->toBe(58);

    Artisan::call('route:list', ['--path' => 'api/v1']);

    expect(Artisan::output())->toContain('Showing [58] routes');
});
