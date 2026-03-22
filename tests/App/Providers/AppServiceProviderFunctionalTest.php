<?php

declare(strict_types=1);

namespace Tests\App\Providers;

use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(AppServiceProvider::class)]
#[Group('providers')]
class AppServiceProviderFunctionalTest extends FunctionalTestCase
{
    #[TestWith(['testing'])]
    #[TestWith(['local'])]
    #[TestWith(['staging'])]
    public function test_it_boots_various_application_defaults(string $env): void
    {
        // Arrange

        app()->detectEnvironment(fn (): string => $env);

        // Act

        new AppServiceProvider($this->app)->boot();

        // Assert

        $this->assertModelsUnguarded();
        $this->assertDateDefaults();
        $this->assertModelsAreStrict();

        $this->assertStringStartsWith('http://', URL::to('/'));

    }

    public function test_it_boots_various_application_defaults_for_production(): void
    {
        // Arrange

        app()->detectEnvironment(fn (): string => 'production');

        // Act

        new AppServiceProvider($this->app)->boot();

        // Assert

        $this->assertModelsUnguarded();
        $this->assertDateDefaults();
        $this->assertModelsAreNotStrict();

        $this->assertStringStartsWith('https://', URL::to('/'));
    }

    /*
     * Asserts.
     */

    private function assertModelsUnguarded(): void
    {
        $this->assertTrue(Model::isUnguarded());
    }

    private function assertDateDefaults(): void
    {
        $this->assertInstanceOf(CarbonImmutable::class, Date::now());
    }

    private function assertModelsAreStrict(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());
        $this->assertTrue(Model::preventsSilentlyDiscardingAttributes());
        $this->assertTrue(Model::preventsAccessingMissingAttributes());
    }

    private function assertModelsAreNotStrict(): void
    {
        $this->assertFalse(Model::preventsLazyLoading());
        $this->assertFalse(Model::preventsSilentlyDiscardingAttributes());
        $this->assertFalse(Model::preventsAccessingMissingAttributes());
    }
}
