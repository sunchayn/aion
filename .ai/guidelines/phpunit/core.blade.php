{!! Blade::render(file_get_contents(base_path('vendor/laravel/boost/.ai/phpunit/core.blade.php')), ['assist' => $assist]) !!}

### Suite Selection & Naming

#### 1. Unit Tests (`*UnitTest.php`)
- **Purpose**: Test a single class in isolation without Laravel's container or database.
- **Base Class**: `Tests\Support\TestCases\UnitTestCase`.
- **Location**: Mirror `app/` in `tests/App/` (e.g., `tests/App/Modules/Auth/DTOs/UserDataUnitTest.php`).
- **Targets**: DTOs, Value Objects, Exceptions, pure logic classes.
- **Preference**: Use whenever logic doesn't require database or complex service integration.

#### 2. Functional Tests (`*FunctionalTest.php`)
- **Purpose**: Test the integration of internal module components and business logic.
- **Base Class**: `Tests\Support\TestCases\FunctionalTestCase`.
- **Location**: Mirror `app/` in `tests/App/` (e.g., `tests/App/Modules/Auth/Actions/RegisterUserActionFunctionalTest.php`).
- **Targets**: Actions (main entry points), Models (relationships/scopes), Service Providers.
- **Entails**: Database access, Event fakes, mocking other module Actions.

#### 3. Integration Tests (`*IntegrationTest.php`)
- **Purpose**: Test the HTTP Transport layer and the full request/response cycle.
- **Base Class**: `Tests\Support\TestCases\FunctionalTestCase`.
- **Location**: Mirror `app/Http/` in `tests/Integration/Http/` (e.g., `tests/Integration/Http/Api/Auth/LoginIntegrationTest.php`).
- **Targets**: Controllers, Form Requests, API Resources, Routing.
- **Entails**: Mocking the business logic (Action) to focus strictly on the transport layer (validation, status codes, JSON structure).
- **Mandatory**: Every Integration test MUST include a `test_it_integrates()` method that exercises the endpoint **without any mocking** to ensure the entire stack is correctly wired. This test does NOT need to be comprehensive; it should simply make a valid request and ensure it succeeds.

#### Mirroring Rule
- All tests in `tests/App/` and `tests/Integration/` MUST mirror the corresponding directory structure of the file being tested.

### AAA+A Formatting
*(Procedure: Refer to Skill: format-phpunit-tests)*
- **Supported Blocks**: `// Arrange`, `// Anticipate` (for mocks/expectations), `// Act`, `// Assert`.
- **Absolute Enclosure**: **NOTHING** must be defined outside of these blocks. Every piece of code MUST fall under one of these headers.
- **Leave exactly ONE extra line break** after each comment (`// Arrange\n\n`).
- **Never add extra content to the block comments**: Put the comment in a separate line, with a two line break apart from the block comment.
<code-snippet name="Example clean AAA blocks" lang="php">
    ❌ BAD
    // Arrange - Create a user in the US region so it triggers a {particular} pre-condition

    $user = User::region('us')->create();

    ---

    ✅ GOOD
    // Arrange

    // Create a user in the US region so it triggers a {particular} pre-condition
    $user = User::region('us')->create();
</code-snippet>

### Dependency Injection
- **Always use `resolve(ClassName::class)`** - Laravel IoC will inject spies/mocks automatically.
- **Create spies before calling `resolve()`.**
- **Never manually instantiate with `new ClassName()`** if it has dependencies. Unless it is a Unit test.

### Data Providers
- **Always use `Generator`**.
- **Use descriptive string keys**.

<code-snippet name="Data Provider Example" lang="php">
    public static function dataProvider(): Generator
    {
        yield 'successful scenario' => [
            'input' => 'valid',
            'expected' => true,
        ];

        yield 'unsuccessful scenario' => [
            'input' => 'invalid',
            'expected' => false,
        ];
    }
</code-snippet>

### Implementation Standards
- **Use `spy()` over `mock()`** unless you need to set expectations (Anticipate block).
- **Always use `LogFake::bind()`** to mock logs.
- **Never test private methods directly**.
- **NEVER test controllers directly** (use HTTP endpoint integration tests).
- **Always add `CoversClass(ClassName::class)` attirubte(s)** to all tests adding the classes being covered.
