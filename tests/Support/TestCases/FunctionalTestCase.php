<?php

namespace Tests\Support\TestCases;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\Support\CreatesApplication;
use TiMacDonald\Log\LogFake;
use Tymon\JWTAuth\JWTGuard;

abstract class FunctionalTestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Str::createRandomStringsNormally();
        Str::createUuidsNormally();

        Http::preventStrayRequests();
        Process::preventStrayProcesses();

        Sleep::fake();
        LogFake::bind();

        $this->freezeSecond();
    }

    protected function skipTestWhenJwtIsNotAvailable(): void
    {
        if (! class_exists(JWTGuard::class)) {
            $this->markTestSkipped('JWTAuth package is not installed.');
        }
    }

    protected function skipTestWhenSocialiteIsNotAvailable(): void
    {
        if (! class_exists('Laravel\Socialite\Facades\Socialite')) {
            $this->markTestSkipped('Socialite package is not installed.');
        }
    }

    protected function skipTestWhenGoogleIsMissing(): void
    {
        if (! class_exists('SocialiteProviders\Google\Provider')) {
            $this->markTestSkipped('Socialite Google provider package is not installed.');
        }
    }

    protected function skipTestWhenGitHubIsMissing(): void
    {
        if (! class_exists('SocialiteProviders\GitHub\Provider')) {
            $this->markTestSkipped('Socialite GitHub provider package is not installed.');
        }
    }

    protected function skipTestWhenAppleIsMissing(): void
    {
        if (! class_exists('SocialiteProviders\Apple\Provider')) {
            $this->markTestSkipped('Socialite Apple provider package is not installed.');
        }
    }
}
