## /reflect - Analyze session for retrospective on mistakes and patterns

**Purpose**: Analyze the current session for reusable patterns and mistakes, then update modular guideline files (stubs or overrides) in `.ai/guidelines/` with learnings.

**Usage**: `/reflect`

**Process**:
1. I will analyze the session for:
    - Recurring mistakes that happened multiple times
    - Universal patterns that are domain-agnostic
    - Decision points where confusion arose
    - Architectural/design patterns violated or followed
    - Testing patterns and organization issues (AAA, Data Providers, etc.)
    - Code quality and style issues (Commenting, PHP 8 features)
    - Module boundary violations (Isolation, Contracts, Events)

2. I will categorize learnings by their related guideline file:
    - **Compliance/Process** → `.ai/guidelines/stubs/compliance.stub`
    - **Modular Architecture** → `.ai/guidelines/stubs/architecture.stub`
    - **PHP Standards** → `.ai/guidelines/php/core.blade.php` (override extending Boost PHP)
    - **Laravel Architecture** → `.ai/guidelines/laravel/core.blade.php` (override extending Boost Laravel)
    - **General Code Quality** → `.ai/guidelines/stubs/quality.stub`
    - **PHPUnit Testing** → `.ai/guidelines/phpunit/core.blade.php` (override for PHPUnit standards)
    - **Agent Behavior** → `.ai/guidelines/stubs/behavior.stub`

3. I will determine if learnings should be:
    - **Added to existing guidelines**: Universal, reusable patterns applicable to all future work
    - **Saved as session notes**: Feature-specific or temporary learnings in `.ai/guidelines/stubs/session-learnings.stub` (create file if it doesn't exist)

4. I will generate proposed changes with:
    - Clear section title referencing the related guideline file
    - Organized subsections matching existing modular structure
    - Concrete examples using `<code-snippet>` tags where helpful
    - Consistent formatting matching existing stubs (Zero Emoji Usage)
    - Absolute file paths for all references

5. I will show you the proposed changes and ask for confirmation

6. Only after your approval will I:
    - Update/Create the appropriate `.stub` or `.blade.php` files
    - **MANDATORY**: Run `php artisan boost:update` to synchronize changes into `CLAUDE.md`

**Confirmation prompt**:
```
Here are the proposed additions based on this session's learnings:

[PROPOSED CHANGES TO .ai/guidelines/...]

Do you want me to apply these changes and run boost:update? (yes/no)
```

**Guidelines for Updates**:
- Follow the exact format of existing files (stubs or blade overrides)
- Use standard Markdown headers and spacing
- Add to existing sections when possible, create new sections only when necessary
- Keep entries concise but complete with concrete examples
- Mirror the style of existing guideline files (High density, professional tone)
- Never add suggestions - only mandatory requirements
- NEVER use emojis (including symbols like ✅/❌)
- Use bold text for emphasis instead of icons
- Ensure code examples use `<code-snippet>` tags

**Note**: The command focuses on reusable patterns and mandatory requirements. After any modification to the modular files, `php artisan boost:update` must be executed to ensure the authoritative `CLAUDE.md` remains in sync with the source stubs and overrides.
