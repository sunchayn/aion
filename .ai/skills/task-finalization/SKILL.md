---
name: task-finalization
description: Procedural workflow for running quality tools before completing a task.
---

# Task Finalization
*(Requirement: Refer to Guidelines: Behavior)*

Before marking a task as complete, you MUST run the following quality tools in order.

## 1. Code Styling
Run Laravel Pint to ensure PSR-12 compliance.
```bash
composer run style:fix
```

## 2. Static Analysis
Run PHPStan to verify type safety (Level 8).
```bash
composer run phpstan
```

## 3. Automated Refactoring
Run Rector to apply quality rules.
```bash
composer run rector
```

## 4. Type Coverage
Verify that type coverage is maintained at 100%.
```bash
composer run test:type-coverage
```

## 5. Summary Checklist
- [ ] All tests passed (`php artisan test`).
- [ ] No regression in types.
- [ ] Guidelines followed (verified against `.ai/guidelines/`).
- [ ] Code is formatted.

> [!IMPORTANT]
> If any tool fails, fix the issue and RE-RUN the entire sequence.
