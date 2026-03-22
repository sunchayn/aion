---
name: create-dto-action
description: Procedural workflow and templates for creating strict DTOs and Single Action Classes.
---

# Creating DTOs and Actions
*(Standards: Refer to Guidelines: Architecture)*

This skill provides the procedural workflow and templates for creating DTOs and Actions.

## 1. Procedural Steps

1. **Naming**: Identify the `VerbNounAction` (e.g., `ProcessOrderAction`).
2. **Provisioning**: Create a DTO that encapsulates ALL inputs required by the Action. If the Action needs a Model, the DTO factory (e.g. `fromRequest`) is responsible for resolving it.
3. **Scaffolding**: Use `php artisan make:class app/Modules/{Domain}/Actions/{Name}`.
4. **Implementation**: Ensure only one `execute()` method exists. The Action must NOT perform database queries (e.g. `::find()`) to retrieve its own inputs.
5. **Finalization**: Add the `/** @final */` annotation.

## 2. Examples

- **[Example DTO](file:///Volumes/Dev/laravel-aion/.ai/skills/create-dto-action/examples/DTO.php)**
- **[Example Action](file:///Volumes/Dev/laravel-aion/.ai/skills/create-dto-action/examples/Action.php)**

> [!TIP]
> Use `php artisan make:class ...` for Actions and DTOs to ensure they are placed in the correct module directory.
