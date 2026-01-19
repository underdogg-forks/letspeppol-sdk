<?php

namespace LetsPeppolSdk\Tests\Fixtures;

use RuntimeException;

/**
 * Helper class to load test fixtures from JSON files
 */
class FixtureLoader
{
    /**
     * Load a fixture from a JSON file
     *
     * @param string $filename The fixture filename (without .json extension)
     * @param string|null $key Optional key to get specific data from the fixture
     * @return array The loaded fixture data
     */
    public static function load(string $filename, ?string $key = null): array
    {
        $path = __DIR__ . '/' . $filename . '.json';
        
        if (!file_exists($path)) {
            throw new RuntimeException("Fixture file not found: {$path}");
        }
        
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in fixture: {$path}");
        }
        
        if ($key !== null) {
            if (!isset($data[$key])) {
                throw new RuntimeException("Key '{$key}' not found in fixture: {$path}");
            }
            $value = $data[$key];
            if (!is_array($value)) {
                throw new RuntimeException("Fixture key '{$key}' does not contain an array: {$path}");
            }
            return $value;
        }
        
        return $data;
    }
}
