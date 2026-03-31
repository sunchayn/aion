<?php

namespace Aion\Features\Authentication;

use Aion\Choices\Enums\ApiTypeEnum;
use Aion\Choices\Enums\ConfigKeyEnum;
use Aion\Engine\AionConfig;
use Aion\Engine\Operations\AddComposerDependencyOperation;
use Aion\Engine\Operations\CopyFileOperation;
use Aion\Engine\Operations\ReplaceRegexOperation;
use Aion\Engine\Operations\ReplaceTextOperation;
use Aion\Engine\PathResolver;
use Aion\Engine\PromptTypeEnum;
use Aion\Features\AionFeatureContract;
use Aion\Features\OptionDefinition;
use Aion\Stacks\BareApiStack;
use Aion\Stacks\StackStrategyContract;

readonly class ApiTokensFeature implements AionFeatureContract
{
    public static function getOptionsDefinitions(): array
    {
        return [
            ConfigKeyEnum::ApiType->value => new OptionDefinition(
                label: 'Do you want to have stateless or stateful API?',
                type: PromptTypeEnum::Select,
                default: ApiTypeEnum::Stateless->value,
                options: ApiTypeEnum::toOptions(),
                hint: 'Stateless uses tokens. Stateful uses cookies/sessions.',
                transformer: fn ($value) => ApiTypeEnum::from($value)
            ),
        ];
    }

    public function getOperations(StackStrategyContract $stack, AionConfig $config, PathResolver $pathResolver): iterable
    {
        $apiType = $config->get(ConfigKeyEnum::ApiType);

        if ($apiType === ApiTypeEnum::Stateless) {
            yield from $this->getStatelessOperations($stack, $pathResolver);

            return;
        }

        yield from $this->getCleanupOperations();
    }

    private function getStatelessOperations(StackStrategyContract $stack, PathResolver $pathResolver): iterable
    {
        yield new CopyFileOperation(
            source: $pathResolver->stub('stateless/config/auth.php'),
            destination: 'config/auth.php',
        );

        yield new AddComposerDependencyOperation('tymon/jwt-auth', '^2.3.0');

        if ($stack instanceof BareApiStack) {
            yield new CopyFileOperation(
                source: $pathResolver->stub('stateless/config/app.php'),
                destination: 'config/app.php',
            );

            yield from $this->getTestSkipOperations();
        }
    }

    private function getTestSkipOperations(): iterable
    {
        $simpleSkips = [
            'tests/App/Modules/Users/Models/UserFunctionalTest.php',
            'tests/App/Modules/Auth/Services/TokenIssuers/JwtTokenIssuerFunctionalTest.php',
        ];

        foreach ($simpleSkips as $file) {
            yield new ReplaceRegexOperation(
                filePath: $file,
                pattern: '/\s*\$this->skipTestWhenJwtIsNotAvailable\(\);/s',
                replacement: '',
            );
        }

        $redundantSetupSkips = [
            'tests/App/Modules/Auth/Actions/StatelessLoginActionFunctionalTest.php',
            'tests/Integration/Http/Api/Auth/RefreshTokenIntegrationTest.php',
        ];

        foreach ($redundantSetupSkips as $file) {
            yield new ReplaceRegexOperation(
                filePath: $file,
                pattern: '/\s*protected function setUp\(\): void\s*\{[^}]*?\$this->skipTestWhenJwtIsNotAvailable\(\);[^}]*\}/s',
                replacement: '',
            );
        }
    }

    private function getCleanupOperations(): iterable
    {
        $cleanupPoints = [
            ['file' => 'app/Modules/Shared/Models/User.php', 'search' => 'use App\Modules\Auth\Models\Concerns\JwtAuthenticatable;'],
            ['file' => 'app/Modules/Shared/Models/User.php', 'search' => 'use Tymon\JWTAuth\Contracts\JWTSubject;'],
            ['file' => 'app/Modules/Shared/Models/User.php', 'search' => ', JWTSubject'],
            ['file' => 'app/Modules/Shared/Models/User.php', 'search' => ', JwtAuthenticatable'],
            ['file' => 'app/Http/Api/routes/v1.php', 'search' => "Route::post('refresh', AuthControllers\RefreshTokenController::class)\n            ->name('auth.tokens.refresh');"],
        ];

        foreach ($cleanupPoints as $point) {
            yield new ReplaceTextOperation(
                filePath: $point['file'],
                search: $point['search'],
                replace: '',
            );
        }
    }
}
