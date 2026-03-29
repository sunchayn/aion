# Aion

![aion-gh-cover.png](https://raw.githubusercontent.com/sunchayn/aion/refs/heads/base/.aion/art/aion-gh-cover.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sunchayn/aion.svg?style=flat-square)](https://packagist.org/packages/sunchayn/aion)
[![License](https://img.shields.io/packagist/l/sunchayn/aion.svg?style=flat-square)](https://packagist.org/packages/sunchayn/aion)
[![PHP Version](https://img.shields.io/packagist/php-v/sunchayn/aion.svg?style=flat-square)](https://packagist.org/packages/sunchayn/aion)

**A modular customizable Laravel starter kit**

Aion is an agentic-ai ready, modular Laravel 13 starter kit that is customizable to your applications' requirements. It provides a modular and clutter-free foundation for both stateful and stateless applications, optimized for quick-starting your next project with full code ownership.

<img src="https://raw.githubusercontent.com/sunchayn/aion/refs/heads/base/.aion/art/aion-cover.png" style="border: 1px solid black;">

## Why Aion?

Aion is designed for developers who want full control over their application's foundation without the technical debt typically associated with complex starter kits or heavy packages.

- **Full Code Ownership**: Unlike other options that provide capabilities like authentication or strict configuration via vendor packages, Aion places them directly in your codebase. You own every line, making customization and debugging straightforward.
- **Architectural Modularity**: By utilizing single-responsibility Actions, your business logic is decoupled from transport layers. This allows you to reuse the same domain logic across different application types, whether it's a REST API, an Inertia-powered frontend, or a CLI tool.
- **Clutter-free**: A minimalist repository structure where unnecessary folders (e.g., `resources`) are omitted when not required. Routes are isolated within Application directories in `app/Http`, and tool configurations are centralized in the `tools/` folder.
- **Customizable**: The configuration wizard allows you to pick the application type, support FE or not and perform granular cleanups, such as selecting only required database and log channel, ensuring you start with a lean and clutter-free repository.

### Kit Technical Details

- **Modular (Pseudo-DDD)**: Business logic is decoupled into domain modules (`app/Modules`) and transport layers (a.k.a. Applications) living in `app/Http`.
- **Agentic-AI Ready**: Aion provides specialized AI guidelines and skills that build upon Laravel Boost. These are native to the codebase, enabling AI assistants to understand and follow your local architectural patterns with precision.
- **Flexible Stacks**: Supports Headless (API-only), or bare-minimum frontend implementations using Vue or Blade (no UI is provided as of now, only the infrastructure).
- **Strict Defaults**: Enforces strict Laravel standards (CarbonImmutable dates, strict models, and auto-eager loading). 
- **ECS Logs Provided**: Supports using Elastic Common Schema (ECS) standardized logs across the board.

### Available Features

Feature-wise, the authentication flow is shipped with the kit by default.
- Registration/login (stateless and stateful) \[configurable/opt-in\]
- Password Reset
- Email verification
- Token refresh (for stateless auth) \[configurable/opt-in\]
- Two-Factor Auth
- Oauth [configurable/opt-in]
- Magic Links

## Directory Structure

- `.ai`: Developer guidelines and skill definitions for AI agents.
- `.aion`: Engine configuration (Features, Stacks, and Pruning Operations). This directory is deleted once the kit is initialized.
- `app/Http`: Transport Layers (REST API vs. Web/Session) consuming module logic.
- `app/Modules`: Domain logic (Auth, User, Shared) containing Actions, DTOs, and Contracts.
- `tools`: Tools configurations like phpstan.

## Getting Started

### Installation

You can create a new project using `composer create-project`:

```bash
composer create-project sunchayn/aion <project-name>
```

Alternatively, clone the repository and run:

```bash
composer install
```

### Configuration Wizard

Initialize the codebase interactively using the Spark engine:

The wizard will pop-up once a new project is initialized it will help configure your application and dependency installation based on your selected stack.

_Note: the command will cleanup after itself once done._

#### Stacks & Features

| Category           | Option           | Description                                                                         |
|--------------------|------------------|-------------------------------------------------------------------------------------|
| **Architecture**   | **Stack Choice** | Toggle between a **Bare API** (headless) or **API + Frontend**.                     |
| **Authentication** | **API Type**     | Choose between **Stateless** (JWT-based) or **Stateful** (Session/Cookie) security. |
|                    | **Social OAuth** | Enable OAuth authentication and select providers (Google, GitHub, Apple).           |
| **Infrastructure** | **Databases**    | Select needed connections.                                                          |
|                    | **Logging**      | Select needed logging channels.                                                     |
|                    | **ECS**          | Choose **ECS** (Elastic Common Schema) format or default logging formats.           |
| **Code Quality**   | **PHPStan**      | Define the static analysis strictness level (5 to 10).                              |

## Development Commands

| Command                     | Result                                                                     |
|-----------------------------|----------------------------------------------------------------------------|
| `composer test:coverage`    | Runs the tests in parallel with coverage (html format).                    |
| `composer test:coverage-ci` | Runs the tests in parallel with coverage optimized for CI (clover format). |
| `composer style:fix`        | Applies Laravel Pint code style rules.                                     |
| `composer phpstan`          | Performs static analysis on the core modules.                              |
| `composer rector`           | Executes automated refactoring and modernization.                          |

---

## License

Aion is open-source software licensed under the [MIT license](LICENSE.md).
