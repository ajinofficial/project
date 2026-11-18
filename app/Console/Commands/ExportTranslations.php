<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ExportTranslations extends Command
{
    protected $signature = 'localization:export-js';
    protected $description = 'Export Laravel language catalogs to cacheable browser dictionaries';

    public function handle(): int
    {
        $fallback = config('app.fallback_locale', 'en');
        foreach (array_keys(config('localization.supported')) as $locale) {
            if (! preg_match('/^[a-z]{2,3}(?:[_-][A-Za-z]{2,4})?$/D', $locale)) {
                $this->error('Invalid configured locale code.');
                return self::FAILURE;
            }
            $messages = [];
            foreach (array_unique([$fallback, $locale]) as $language) {
                $json = lang_path($language.'.json');
                if (File::exists($json)) {
                    $messages = array_replace($messages, json_decode(File::get($json), true, 512, JSON_THROW_ON_ERROR));
                }
                foreach (File::glob(lang_path($language.'/*.php')) as $file) {
                    $group = pathinfo($file, PATHINFO_FILENAME);
                    foreach (Arr::dot(require $file) as $key => $value) {
                        if (is_string($value)) {
                            $messages[$group.'.'.$key] = $value;
                        }
                    }
                }
            }
            File::ensureDirectoryExists(public_path('js/locales'));
            File::put(public_path('js/locales/'.$locale.'.js'), 'window.AppTranslations = '.json_encode(
                $messages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ).";\n");
            $this->info('Exported '.$locale);
        }
        return self::SUCCESS;
    }
}
