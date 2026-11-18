<?php

namespace Tests\Unit;

use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;

class MalayalamCatalogTest extends TestCase
{
    public function test_all_english_catalog_entries_have_malayalam_translations(): void
    {
        $root = dirname(__DIR__, 2);
        $catalogs = [
            'json' => [
                json_decode(file_get_contents($root.'/lang/en.json'), true, 512, JSON_THROW_ON_ERROR),
                json_decode(file_get_contents($root.'/lang/ml.json'), true, 512, JSON_THROW_ON_ERROR),
            ],
        ];
        foreach (glob($root.'/lang/en/*.php') as $file) {
            $catalogs[basename($file)] = [Arr::dot(require $file), Arr::dot(require $root.'/lang/ml/'.basename($file))];
        }
        foreach ($catalogs as $group => [$english, $malayalam]) {
            foreach ($english as $key => $source) {
                if (! is_string($source)) {
                    $this->assertIsArray($source, $group.':'.$key);
                    continue;
                }
                $label = $group.':'.$key;
                $this->assertArrayHasKey($key, $malayalam, $label);
                $translation = $malayalam[$key];
                $this->assertIsString($translation, $label);
                $this->assertDoesNotMatchRegularExpression('/\x{FFFD}|\?{2,}/u', $translation, $label);
                if ($source !== 'WhatsApp') {
                    $this->assertMatchesRegularExpression('/[\x{0D00}-\x{0D7F}]/u', $translation, $label);
                    $this->assertNotSame($source, $translation, $label);
                }
                preg_match_all('/:[a-z_]+/i', $source, $expected);
                preg_match_all('/:[a-z_]+/i', $translation, $actual);
                sort($expected[0]);
                sort($actual[0]);
                $this->assertSame($expected[0], $actual[0], $label);
            }
        }
    }
}
