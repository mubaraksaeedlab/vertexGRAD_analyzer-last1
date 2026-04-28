<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Languages\Models\SupportedLanguage;

class SupportedLanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code' => 'php',
                'name' => 'PHP',
                'extensions' => ['php'],
                'is_active' => true,
                'sort_order' => 1,
                'description' => 'PHP language support for web applications and backend systems.',
            ],
            [
                'code' => 'python',
                'name' => 'Python',
                'extensions' => ['py'],
                'is_active' => true,
                'sort_order' => 2,
                'description' => 'Python language support for scripting, AI, and backend services.',
            ],
            [
                'code' => 'javascript',
                'name' => 'JavaScript',
                'extensions' => ['js', 'mjs', 'cjs'],
                'is_active' => true,
                'sort_order' => 3,
                'description' => 'JavaScript language support for frontend and backend development.',
            ],
            [
                'code' => 'typescript',
                'name' => 'TypeScript',
                'extensions' => ['ts', 'tsx'],
                'is_active' => true,
                'sort_order' => 4,
                'description' => 'TypeScript language support for typed JavaScript applications.',
            ],
            [
                'code' => 'java',
                'name' => 'Java',
                'extensions' => ['java'],
                'is_active' => true,
                'sort_order' => 5,
                'description' => 'Java language support for enterprise and Android-related projects.',
            ],
            [
                'code' => 'csharp',
                'name' => 'C#',
                'extensions' => ['cs'],
                'is_active' => true,
                'sort_order' => 6,
                'description' => 'C# language support for .NET applications and services.',
            ],
            [
                'code' => 'cpp',
                'name' => 'C++',
                'extensions' => ['cpp', 'cc', 'cxx', 'hpp', 'hh', 'hxx'],
                'is_active' => true,
                'sort_order' => 7,
                'description' => 'C++ language support for high-performance and systems programming.',
            ],
            [
                'code' => 'c',
                'name' => 'C',
                'extensions' => ['c', 'h'],
                'is_active' => true,
                'sort_order' => 8,
                'description' => 'C language support for low-level and systems programming.',
            ],
            [
                'code' => 'dart',
                'name' => 'Dart',
                'extensions' => ['dart'],
                'is_active' => true,
                'sort_order' => 9,
                'description' => 'Dart language support for Flutter and cross-platform applications.',
            ],
            [
                'code' => 'go',
                'name' => 'Go',
                'extensions' => ['go'],
                'is_active' => true,
                'sort_order' => 10,
                'description' => 'Go language support for fast backend and concurrent systems.',
            ],
        ];

        foreach ($languages as $language) {
            SupportedLanguage::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}