<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Languages\Registry\LanguageRegistry;
use App\Modules\Languages\Analyzers\PhpAnalyzer;
use App\Modules\Languages\Analyzers\JavaScriptAnalyzer;
use App\Modules\Languages\Analyzers\PythonAnalyzer;
use App\Modules\Languages\Analyzers\TypeScriptAnalyzer;
use App\Modules\Languages\Analyzers\JavaAnalyzer;
use App\Modules\Languages\Analyzers\CAnalyzer;
use App\Modules\Languages\Analyzers\CppAnalyzer;
use App\Modules\Languages\Analyzers\CSharpAnalyzer;
use App\Modules\Languages\Analyzers\GoAnalyzer;
use App\Modules\Languages\Analyzers\DartAnalyzer;

class LanguageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LanguageRegistry::class, function () {
            $registry = new LanguageRegistry();

            $registry->register(new PhpAnalyzer());
            $registry->register(new JavaScriptAnalyzer());
            $registry->register(new PythonAnalyzer());
            $registry->register(new TypeScriptAnalyzer());
            $registry->register(new JavaAnalyzer());
            $registry->register(new CAnalyzer());
            $registry->register(new CppAnalyzer());
            $registry->register(new CSharpAnalyzer());
            $registry->register(new GoAnalyzer());
            $registry->register(new DartAnalyzer());

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}