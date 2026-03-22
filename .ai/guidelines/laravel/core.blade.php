{!! Blade::render(file_get_contents(base_path('vendor/laravel/boost/.ai/laravel/core.blade.php')), ['assist' => $assist]) !!}

### Requests
- Never use inline validation. Always use Form Requests.

### Resources
- **No database queries in API Resources.** Make sure the controller are providing all needed data.

### Architecture Constraints
- **Follow event-driven architecture** with service contracts and dependency injection.
- **Actions & DTOs**: Follow strict structural requirements (defined in **Guidelines: Architecture**). For the creation procedure, refer to **Skill: create-dto-action**.
