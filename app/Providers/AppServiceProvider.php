<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootModelsDefaults();
        $this->bootDateDefaults();
        $this->bootDatabaseDefaults();
        $this->bootHttpDefaults();
    }

    private function bootModelsDefaults(): void
    {
        Model::unguard();
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::automaticallyEagerLoadRelationships();
    }

    private function bootDateDefaults(): void
    {
        Date::use(CarbonImmutable::class);
    }

    private function bootDatabaseDefaults(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    private function bootHttpDefaults(): void
    {
        if ($this->app->isProduction()) {
            URL::forceHttps();
        }
    }
}
