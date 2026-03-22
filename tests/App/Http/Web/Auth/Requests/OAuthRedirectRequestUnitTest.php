<?php

namespace Tests\App\Http\Web\Auth\Requests;

use App\Http\Web\Auth\Requests\OAuthRedirectRequest;
use App\Modules\Auth\OAuthProviders\Enum\OperationTypeEnum;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TestCases\FunctionalTestCase;

#[CoversClass(OAuthRedirectRequest::class)]
#[Group('authentication')]
class OAuthRedirectRequestUnitTest extends FunctionalTestCase
{
    public function test_it_validates_operation_type_successfully(): void
    {
        // Arrange

        $request = new OAuthRedirectRequest;

        // Act

        $validator = Validator::make(
            ['operation_type' => OperationTypeEnum::Auth->value],
            $request->rules()
        );

        // Assert

        $this->assertTrue($validator->passes());
    }

    public function test_it_fails_validation_for_invalid_operation_type(): void
    {
        // Arrange

        $request = new OAuthRedirectRequest;

        // Act

        $validator = Validator::make(
            ['operation_type' => 'invalid_operation'],
            $request->rules()
        );

        // Assert

        $this->assertFalse($validator->passes());

        $this->assertArrayHasKey('operation_type', $validator->errors()->toArray());
    }
}
