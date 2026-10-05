# Graph Report - dashboard_banjir-web  (2026-08-27)

## Corpus Check
- Corpus is ~39,715 words - fits in a single context window. You may not need a graph.

## Summary
- 171 nodes · 166 edges · 27 communities (21 shown, 6 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Composer Autoload & Namespaces
- Composer Build & Lifecycle Scripts
- User Authentication & Seeding
- Database & Cache Configuration
- Database Migrations
- Vite & Frontend Tooling
- Package Manifest & Dependencies
- Development Dependencies & Boost
- Composer Plugins Configuration
- Application Service Providers
- Application Bootstrap & Middleware
- Feature HTTP Tests
- Logging & Error Handling
- Unit Tests
- Console & Artisan Commands
- Base HTTP Controllers
- Web Routes
- Flood Early Warning PRD

## God Nodes (most connected - your core abstractions)
1. `User` - 9 edges
2. `require-dev` - 9 edges
3. `scripts` - 9 edges
4. `setup` - 7 edges
5. `AppServiceProvider` - 5 edges
6. `config` - 5 edges
7. `UserFactory` - 5 edges
8. `require` - 4 edges
9. `psr-4` - 4 edges
10. `post-create-project-cmd` - 4 edges

## Surprising Connections (you probably didn't know these)
- `ExampleTest` --inherits--> `TestCase`  [EXTRACTED]
  tests/Feature/ExampleTest.php → tests/TestCase.php

## Import Cycles
- None detected.

## Communities (27 total, 6 thin omitted)

### Community 0 - "Composer Autoload & Namespaces"
Cohesion: 0.08
Nodes (25): autoload, autoload-dev, psr-4, psr-4, description, extra, laravel, keywords (+17 more)

### Community 1 - "Composer Build & Lifecycle Scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 2 - "User Authentication & Seeding"
Cohesion: 0.20
Nodes (9): User, DatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Seeder, Illuminate\Foundation\Auth\User (+1 more)

### Community 3 - "Database & Cache Configuration"
Cohesion: 0.20
Nodes (6): UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\Hash, Illuminate\Support\Str, Pdo\Mysql, static

### Community 4 - "Database Migrations"
Cohesion: 0.23
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 5 - "Vite & Frontend Tooling"
Cohesion: 0.18
Nodes (11): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+3 more)

### Community 6 - "Package Manifest & Dependencies"
Cohesion: 0.20
Nodes (9): @laravel/multiplex, optionalDependencies, @laravel/multiplex, private, $schema, scripts, build, dev (+1 more)

### Community 7 - "Development Dependencies & Boost"
Cohesion: 0.22
Nodes (9): require-dev, fakerphp/faker, laravel/boost, laravel/pail, laravel/pao, laravel/pint, mockery/mockery, nunomaduro/collision (+1 more)

### Community 8 - "Composer Plugins Configuration"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 10 - "Application Bootstrap & Middleware"
Cohesion: 0.40
Nodes (4): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Request

### Community 11 - "Feature HTTP Tests"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 12 - "Logging & Error Handling"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

## Knowledge Gaps
- **59 isolated node(s):** `Controller`, `$schema`, `name`, `type`, `description` (+54 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **6 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `scripts` connect `Composer Build & Lifecycle Scripts` to `Composer Autoload & Namespaces`?**
  _High betweenness centrality (0.091) - this node is a cross-community bridge._
- **Why does `require-dev` connect `Development Dependencies & Boost` to `Composer Autoload & Namespaces`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **Why does `config` connect `Composer Plugins Configuration` to `Composer Autoload & Namespaces`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **What connects `Controller`, `$schema`, `name` to the rest of the system?**
  _59 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Composer Autoload & Namespaces` be split into smaller, more focused modules?**
  _Cohesion score 0.07692307692307693 - nodes in this community are weakly interconnected._
- **Should `Composer Build & Lifecycle Scripts` be split into smaller, more focused modules?**
  _Cohesion score 0.08 - nodes in this community are weakly interconnected._