<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Languages\Registry\LanguageRegistry;
use App\Modules\Languages\Analyzers\PhpAnalyzer;

class AnalyzerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LanguageRegistry::class, function () {
            $registry = new LanguageRegistry();

            // تسجيل اللغات هنا
            $registry->register(new PhpAnalyzer());

            return $registry;
        });
    }

    public function boot(): void
    {
        //
    }
}