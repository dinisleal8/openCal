<?php

use App\Services\FoodAnalysis;

test('it parses a plain json array', function () {
    $items = FoodAnalysis::parse('[{"name":"Rice","calories":130,"protein_g":2.7,"carbs_g":28,"fat_g":0.3,"serving_description":"1/2 cup"}]');

    expect($items)->toHaveCount(1)
        ->and($items[0]['name'])->toBe('Rice')
        ->and($items[0]['calories'])->toBe(130.0);
});

test('it parses markdown fenced json', function () {
    $items = FoodAnalysis::parse("```json\n[{\"name\":\"Apple\",\"calories\":95}]\n```");

    expect($items)->toHaveCount(1)
        ->and($items[0]['name'])->toBe('Apple');
});

test('it extracts json embedded in prose', function () {
    $items = FoodAnalysis::parse('Here is the analysis: [{"name":"Egg","calories":78}] hope it helps!');

    expect($items)->toHaveCount(1)
        ->and($items[0]['name'])->toBe('Egg');
});

test('it supports an items wrapper', function () {
    $items = FoodAnalysis::parse('{"items":[{"name":"Toast","calories":80}]}');

    expect($items)->toHaveCount(1)
        ->and($items[0]['name'])->toBe('Toast');
});

test('it wraps a single item object', function () {
    $items = FoodAnalysis::parse('{"name":"Banana","calories":105,"protein_g":1.3,"carbs_g":27,"fat_g":0.4,"serving_description":"1 medium"}');

    expect($items)->toHaveCount(1)
        ->and($items[0]['name'])->toBe('Banana');
});

test('it returns an empty array when no food is detected', function () {
    expect(FoodAnalysis::parse('[]'))->toBe([]);
});

test('it returns null for unparseable content', function () {
    expect(FoodAnalysis::parse('I could not find any food in this image.'))->toBeNull()
        ->and(FoodAnalysis::parse(''))->toBeNull()
        ->and(FoodAnalysis::parse(null))->toBeNull();
});

test('it normalizes missing numeric fields to zero', function () {
    $items = FoodAnalysis::parse('[{"name":"Mystery"}]');

    expect($items[0]['calories'])->toBe(0.0)
        ->and($items[0]['protein_g'])->toBe(0.0)
        ->and($items[0]['serving_description'])->toBe('');
});
