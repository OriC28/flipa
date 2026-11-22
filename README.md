# Flipa Framework

[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg?style=flat-square&logo=php)](https://www.php.net/)
[![Tests](https://img.shields.io/badge/tests-241%20passed-brightgreen.svg?style=flat-square&logo=githubactions)](tests)
[![Coverage](https://img.shields.io/badge/coverage-93.7%25%20lines-success.svg?style=flat-square)](reports/coverage)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

**Flipa** is a lightweight, educational full-stack micro-framework built from scratch using modern **PHP 8.3+**.

Designed with a 100% pedagogical purpose, its goal is to demystify the inner mechanics of enterprise PHP frameworks (such as Laravel or Symfony). Flipa emphasizes clean code, strict typing, standard architectural patterns, and deep understanding over black-box magic, keeping the codebase transparent, testable, and extensible.

---

## Table of Contents

- [Core Features](#core-features)
- [Architectural Patterns & Design Strategies](#architectural-patterns--design-strategies)
- [Technology Stack](#technology-stack)
- [Project Directory Structure](#project-directory-structure)
- [Getting Started](#getting-started)
- [Testing & Code Coverage](#testing--code-coverage)
- [Contributing](#contributing)
- [License](#license)

---

## Core Features

- **IoC Container with Auto-Wiring:**
  Reflection-based Dependency Injection container capable of recursively resolving nested dependencies, managing singletons, handling default and primitive parameters, union types, and detecting circular dependencies.
- **Expressive HTTP Router:**
  RESTful route registration (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`) supporting both Closures and Controller action tuples (`[Controller::class, 'method']`), dynamic route parameters (`/users/{id}`), route inspection, and automated `404 Not Found` and `405 Method Not Allowed` (with `Allow` headers) handling.
- **Middleware Pipeline:**
  Onion-style middleware pipeline powered by functional composition (`array_reduce`). Ships with built-in middlewares for CORS origin and preflight (`OPTIONS` 204) negotiation, CSRF token verification, and standard security headers.
- **Active Record ORM & Fluent Query Builder:**
  Database abstraction supporting SQLite and MySQL drivers. Features an immutable Query Builder with SQL injection defenses, mass-assignment guards (`$fillable`), and lifecycle hooks.
- **Event Dispatcher & Model Lifecycle Events:**
  Observer-based event system supporting class-based listeners with container resolution and operation cancellation (returning `false` stops propagation and cancels database persistence).
- **Form Request & Validation Engine:**
  Dedicated request objects that encapsulate authorization checks and declarative, pipe-delimited validation rules (`required|min:3|email`).
- **Native PHP View Engine:**
  Clean template rendering supporting dot notation paths (`users.profile` &rarr; `users/profile.php`), cascade directory fallbacks, isolated variable scopes via `extract()`, and exception-safe output buffer cleanup.
- **Robust Exception Handling & Content Negotiation:**
  Centralized exception handler that automatically negotiates HTML error pages vs. structured JSON payloads based on the request's `Accept` header. Masks internal details in `production` environments while keeping detailed diagnostics in `development`.
- **PSR-3 Inspired File Logger:**
  Thread-safe file logger utilizing exclusive write locks (`LOCK_EX`), timestamp formatting, context interpolation (`{key}`), and automatic log file creation.

---

## Architectural Patterns & Design Strategies

| Pattern / Strategy | Implementation in Flipa |
|---|---|
| **Inversion of Control (IoC) & DI** | Central `Container` resolves class dependencies automatically using reflection, decoupling controllers and services from concrete implementations. |
| **Pipeline (Onion Architecture)** | `Pipeline` executes middleware layers sequentially around the core request dispatcher using reverse reduction. |
| **Front Controller & MVC** | Single entry point (`public/index.php`) delegates execution through the Router, Controllers, and Views. |
| **Active Record** | `Model` instances map directly to database rows, encapsulating both data access and business logic. |
| **Immutable Query Builder** | Builder operations (`select()`, `where()`, `orderBy()`) return cloned instances, preventing side effects during query construction. |
| **Observer (Event-Driven)** | `Dispatcher` broadcasts events (`Creating`, `Created`, `Updating`, `Updated`, `Deleting`, `Deleted`) to decoupled listeners. |
| **Strategy Pattern** | `Validator` delegates validation logic to interchangeable rule objects (`Required`, `Min`, `Email`). |
| **Content Negotiation** | `Handler` inspects headers to dynamically format error responses as HTML views or JSON payloads. |
| **Immutability (HTTP Layer)** | `Request` and `Response` use `readonly` properties and clone-with methods to ensure predictable HTTP state. |

---

## Technology Stack

- **PHP 8.3+:** Strict types (`declare(strict_types=1);`), constructor property promotion, match expressions, readonly properties, and native enums.
- **Composer:** PSR-4 autoloading for `App\` and `Core\` namespaces.
- **vlucas/phpdotenv:** Environment configuration management.
- **filp/whoops:** Interactive stack traces and error diagnostics in local development.
- **PHPUnit 12:** Modern test framework for unit and integration suites.
- **PCOV:** Lightweight and high-performance code coverage driver.
- **Tailwind CSS 4:** Utility-first CSS framework compiled with the official Tailwind CLI.
- **pnpm:** Fast, disk-efficient package manager for the frontend toolchain.

---

## Project Directory Structure

```text
flipa/
├── app/                      # Starter / Userland application skeleton
│   ├── Controllers/          # Application controllers (e.g., DashboardController)
│   ├── Listeners/            # Custom event listeners
│   ├── Middlewares/          # Application-specific middleware (e.g., AuthMiddleware)
│   ├── Models/               # Active Record models (e.g., User)
│   └── Requests/             # Form validation requests (e.g., StoreUserRequest)
├── bootstrap/                # Application bootstrap and service registration
│   └── app.php
├── config/                   # Configuration files (database, cors, middleware, etc.)
├── core/                     # Flipa Framework Core Engine
│   ├── Config/               # Configuration repository loader
│   ├── Container/            # IoC container and dependency resolver
│   ├── Database/             # Database connection, drivers, and ORM
│   ├── Events/               # Event dispatcher and contracts
│   ├── Exceptions/           # Central error handler and exception hierarchy
│   ├── Foundation/           # Application kernel and path resolution
│   ├── Http/                 # Request, Response, Router, Pipeline, FormRequest
│   ├── Log/                  # File logging service
│   ├── Middleware/           # Built-in framework middlewares (CORS, CSRF, Security)
│   ├── Session/              # Session abstraction and flash messages
│   ├── Support/              # Global helper functions
│   ├── Validation/           # Validation engine and rules
│   └── View/                 # Template renderer
├── database/                 # SQLite database storage (development)
├── package.json              # Frontend dependencies and build scripts
├── public/                   # Web server document root (Front Controller)
│   ├── assets/
│   │   └── css/              # Compiled Tailwind CSS (generated)
│   └── index.php
├── resources/                # Application views and frontend sources
│   ├── css/
│   │   └── app.css           # Tailwind CSS entry point
│   └── views/
│       └── index.php         # Welcome page
├── routes/                   # Route definitions
│   └── web.php
├── storage/                  # Logs and compiled assets
│   └── logs/
└── tests/                    # Automated test suites
    ├── Feature/              # Integration and end-to-end tests
    ├── Fixtures/             # Test doubles, mock models, and listeners
    └── Unit/                 # Isolated component unit tests
```

---

## Getting Started

### Prerequisites

- **PHP >= 8.3** with the following extensions:
  - `pdo`
  - `pdo_sqlite`
  - `mbstring`
  - `pcov` *(optional, required only for code coverage reports)*
- **Composer** (v2+)
- **Node.js >= 20** and **pnpm** (for the Tailwind CSS toolchain)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/oric28/flipa.git
   cd flipa
   ```

2. **Install Composer dependencies:**
   ```bash
   composer install
   ```

3. **Install frontend dependencies and build assets:**
   ```bash
   pnpm install
   ```
   This also compiles Tailwind CSS into `public/assets/css/app.css`. Use `pnpm dev` to rebuild the stylesheet automatically while developing.

4. **Configure environment variables:**
   ```bash
   cp .env.example .env
   ```

5. **Start the local PHP development server:**
   ```bash
   php -S localhost:8000 -t public
   ```

6. **Visit the application:**
   Open [http://localhost:8000](http://localhost:8000) in your web browser.

---

## Testing & Code Coverage

The framework contains a comprehensive test suite of **241 automated tests** covering both low-level unit behaviors and end-to-end application integration.

### Running Tests

Execute the full test suite with PHPUnit:

```bash
vendor/bin/phpunit
```

Run specific test suites:

```bash
# Run unit tests only
vendor/bin/phpunit --testsuite Unit

# Run integration tests only
vendor/bin/phpunit --testsuite Feature
```

### Generating Coverage Reports

Generate an interactive HTML code coverage report:

```bash
composer test:coverage
```

The report will be created in `reports/coverage/index.html`. Open it in any browser:

```bash
xdg-open reports/coverage/index.html
# or start a quick preview server:
php -S localhost:8080 -t reports/coverage
```

### Current Test Coverage Summary

| Suite / Component | Tests | Assertions | Line Coverage | Status |
|---|---|---|---|---|
| **Unit Tests (`tests/Unit/`)** | 190 | 375 | — | 🟢 Passed |
| **Integration Tests (`tests/Feature/`)** | 51 | 136 | — | 🟢 Passed |
| **Total Test Suite** | **241** | **511** | — | **100% Passed** |
| **Core Framework (`core/`)** | — | — | **94.24%** (949 / 1,007 lines) | 🟢 Excellent |
| **Overall Project Total** | — | — | **93.69%** (951 / 1,015 lines) | 🟢 Excellent |
| **Functions & Methods** | — | — | **83.03%** (181 / 218 methods) | 🟢 Solid |
| **Classes & Traits** | — | — | **69.39%** (34 / 49 classes) | 🟡 High |

---

## Contributing

Because Flipa is built primarily as an educational experiment, suggestions, feedback, and discussions about framework architecture, design patterns, and PHP best practices are warmly welcomed! Feel free to open an issue or submit a pull request.

---

## License

Flipa is open-sourced software licensed under the [MIT License](LICENSE).