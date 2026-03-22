---
name: format-phpunit-tests
description: Apply strict project standards to PHPUnit tests including AAA pattern and spacing.
---

# Format PHPUnit Tests
*(Standards: Refer to Guidelines: PHPUnit)*

This skill provides the procedural workflow for applying project formatting standards to PHPUnit tests.

## 1. Procedural Workflow

1. **Verify Suite & Structure**:
    - **Mirroring**: Unit and Functional tests MUST mirror the `app/` directory structure inside `tests/App/`.
    - **Unit Test**: MUST extend `Tests\Support\TestCases\UnitTestCase`.
    - **Functional/Integration Test**: MUST extend `Tests\Support\TestCases\FunctionalTestCase`.
2. **Apply AAA+A Pattern**:
    - Insert `// Arrange`, `// Anticipate` (if mocking), `// Act`, and `// Assert` block comments.
    - **Strict Enclosure**: Absolutely NO code is allowed outside these blocks. Setup code like `$this->withoutExceptionHandling()` must be inside `// Arrange`.
    - Normalize spacing by leaving exactly ONE extra line break after each comment (`// Arrange\n\n`).
3. **Convert Data Providers**:
    - Ensure return type is `Generator`.
    - Use `yield` with descriptive string keys.
4. **Final Check**:
    - Ensure `CoversClass` attribute is present.

## 2. Examples

- **[Example Functional Test (Action-based)](file:///Volumes/Dev/laravel-aion/.ai/skills/format-phpunit-tests/examples/FunctionalTest.php)**
- **[Example Integration Test (Endpoint-based)](file:///Volumes/Dev/laravel-aion/.ai/skills/format-phpunit-tests/examples/IntegrationTest.php)**
