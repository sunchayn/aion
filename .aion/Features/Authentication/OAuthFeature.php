<?php

namespace Aion\Features\Authentication;

use Aion\Choices\Enums\ConfigKeyEnum;
use Aion\Choices\Enums\OAuthProviderEnum;
use Aion\Engine\AionConfig;
use Aion\Engine\Operations\AddComposerDependencyOperation;
use Aion\Engine\Operations\ReplaceRegexOperation;
use Aion\Engine\PathResolver;
use Aion\Engine\PromptTypeEnum;
use Aion\Features\AionFeatureContract;
use Aion\Features\OptionDefinition;
use Aion\Stacks\StackStrategyContract;
use RuntimeException;

readonly class OAuthFeature implements AionFeatureContract
{
    public static function getOptionsDefinitions(): array
    {
        return [
            ConfigKeyEnum::OAuth->value => new OptionDefinition(
                label: 'Enable OAuth authentication?',
                type: PromptTypeEnum::Confirm,
                default: false,
            ),
            ConfigKeyEnum::OAuthProviders->value => new OptionDefinition(
                label: 'Select OAuth providers',
                type: PromptTypeEnum::MultiSelect,
                default: [OAuthProviderEnum::Google->value, OAuthProviderEnum::GitHub->value],
                options: OAuthProviderEnum::toOptions(),
                shouldSkip: fn (AionConfig $config) => ! $config->bool(ConfigKeyEnum::OAuth),
                transformer: fn ($value) => is_array($value)
                    ? array_map(fn ($v) => OAuthProviderEnum::from($v), $value)
                    : [OAuthProviderEnum::from($value)]
            ),
        ];
    }

    public function getOperations(StackStrategyContract $stack, AionConfig $config, PathResolver $pathResolver): iterable
    {
        if (! $config->bool(ConfigKeyEnum::OAuth)) {
            return;
        }

        yield from $this->getDependencyOperations($config);

        yield from $this->getGuardOperations();

        yield from $this->getTestSkipOperations($config);
    }

    private function getDependencyOperations(AionConfig $config): iterable
    {
        yield new AddComposerDependencyOperation('laravel/socialite', '^5.17');

        $providerPackages = [
            OAuthProviderEnum::Apple->value => 'socialiteproviders/apple:^5.5',
            OAuthProviderEnum::GitHub->value => 'socialiteproviders/github:^4.1',
            OAuthProviderEnum::Google->value => 'socialiteproviders/google:^4.1',
        ];

        foreach ($config->array(ConfigKeyEnum::OAuthProviders) as $provider) {
            if (isset($providerPackages[$provider->value])) {
                [$package, $version] = explode(':', $providerPackages[$provider->value]);
                yield new AddComposerDependencyOperation($package, $version);
            }
        }
    }

    private function getGuardOperations(): iterable
    {
        $guardFiles = [
            'app/Http/Api/Auth/Controllers/ContinueWithOAuthController.php',
            'app/Http/Web/Auth/Controllers/OAuthRedirectController.php',
        ];

        foreach ($guardFiles as $file) {
            yield new ReplaceRegexOperation(
                filePath: $file,
                pattern: '/\s*if\s*\(!\s*class_exists\(\'Laravel\\\\Socialite\\\\Facades\\\\Socialite\'\)\)\s*\{[^}]*?abort\(404\);[^}]*\}/s',
                replacement: '',
            );
        }

        yield new ReplaceRegexOperation(
            filePath: 'app/Modules/Auth/OAuthProviders/ProvidersFactory.php',
            pattern: '/\s*\$this->ensureProviderIsInstalled\(\$provider\);/s',
            replacement: '',
        );
    }

    private function getTestSkipOperations(AionConfig $config): iterable
    {
        yield from $this->getSocialiteSkipOperations();
        yield from $this->getProviderSpecificSkipOperations($config);
        yield from $this->getFactorySkipOperations($config);
    }

    private function getSocialiteSkipOperations(): iterable
    {
        $testFiles = [
            'tests/Integration/Http/Api/Auth/LinkSocialAccountIntegrationTest.php',
            'tests/Integration/Http/Api/Auth/ContinueWithOAuthIntegrationTest.php',
            'tests/Integration/Http/Api/Auth/UnlinkSocialAccountIntegrationTest.php',
        ];

        foreach ($testFiles as $testFile) {
            yield new ReplaceRegexOperation(
                filePath: $testFile,
                pattern: '/\s*protected function setUp\(\): void\s*\{[^}]*?\$this->skipTestWhenSocialiteIsNotAvailable\(\);[^}]*\}/s',
                replacement: '',
            );
        }
    }

    private function getProviderSpecificSkipOperations(AionConfig $config): iterable
    {
        $providerSkips = [
            OAuthProviderEnum::Google->value => [
                'file' => 'tests/App/Modules/Auth/OAuthProviders/Providers/GoogleOAuthProviderFunctionalTest.php',
                'pattern' => '/\s*protected function setUp\(\): void\s*\{[^}]*?\$this->skipTestWhenGoogleIsMissing\(\);[^}]*\}/s',
            ],
            OAuthProviderEnum::GitHub->value => [
                'file' => 'tests/App/Modules/Auth/OAuthProviders/Providers/GitHubOAuthProviderFunctionalTest.php',
                'pattern' => '/\s*protected function setUp\(\): void\s*\{[^}]*?\$this->skipTestWhenGitHubIsMissing\(\);[^}]*\}/s',
            ],
            OAuthProviderEnum::Apple->value => [
                'file' => 'tests/App/Modules/Auth/OAuthProviders/Providers/AppleOAuthProviderFunctionalTest.php',
                'pattern' => '/\s*\$this->skipTestWhenAppleIsMissing\(\);/s',
            ],
        ];

        foreach ($config->array(ConfigKeyEnum::OAuthProviders) as $provider) {
            if (isset($providerSkips[$provider->value])) {
                yield new ReplaceRegexOperation(
                    filePath: $providerSkips[$provider->value]['file'],
                    pattern: $providerSkips[$provider->value]['pattern'],
                    replacement: '',
                );
            }
        }
    }

    private function getFactorySkipOperations(AionConfig $config): iterable
    {
        $selectedProviders = array_map(fn ($provider) => $provider->value, $config->array(ConfigKeyEnum::OAuthProviders));

        $allProviders = OAuthProviderEnum::toOptions();

        if (count($selectedProviders) === count($allProviders)) {
            yield new ReplaceRegexOperation(
                filePath: 'tests/App/Modules/Auth/OAuthProviders/ProvidersFactoryFunctionalTest.php',
                pattern: '/\s*match\s*\(\$provider\)\s*\{[^}]*?\};/s',
                replacement: '',
            );

            return;
        }

        foreach ($selectedProviders as $providerValue) {
            $case = match ($providerValue) {
                OAuthProviderEnum::Google->value => 'Google',
                OAuthProviderEnum::GitHub->value => 'GitHub',
                OAuthProviderEnum::Apple->value => 'Apple',
                default => throw new RuntimeException("Unrecognised provider value: {$providerValue}"),
            };

            yield new ReplaceRegexOperation(
                filePath: 'tests/App/Modules/Auth/OAuthProviders/ProvidersFactoryFunctionalTest.php',
                pattern: sprintf('/\s*ProviderEnum::%s\s+=>\s+\$this->skipTestWhen%sIsMissing\(\),?/s', $case, $case),
                replacement: '',
            );
        }
    }
}
