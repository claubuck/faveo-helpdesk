# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

This is **Faveo Helpdesk**, a Laravel 9 helpdesk/ticketing application (upstream: `faveosuite/faveo-helpdesk`, originally `ladybirdweb/faveo-helpdesk`). This repo (`claubuck/faveo-helpdesk`, the only configured git remote) is a personal fork with ongoing custom modifications — mostly route-naming cleanups, breadcrumb fixes, and bug fixes discovered in `storage/logs/laravel.log`. There is no upstream remote configured, so `git log` here reflects both inherited Faveo history and this fork's own commits.

## Common commands

```bash
# PHP dependencies
composer install

# JS dependencies + asset build (Laravel Mix / webpack)
npm install
npm run dev          # development build
npm run watch        # rebuild on change
npm run production   # minified production build

# Run the full test suite
vendor/bin/phpunit

# Run a single test file / method
vendor/bin/phpunit tests/Unit/TicketControllerTest.php
vendor/bin/phpunit --filter testMethodName tests/Unit/TicketControllerTest.php

# Laravel Dusk (browser) tests
php artisan dusk

# Clear caches after changing routes/views/config (important after every deploy — see Gotchas below)
php artisan route:clear && php artisan view:clear && php artisan config:clear
php artisan queue:restart
```

Tests run against the `testing` DB connection (see `phpunit.xml`), which must exist and be migrated/seeded — historically bootstrapped via `php artisan testing-setup` (see `.travis.yml`, `app/Console/Commands/SetupTestEnv.php`).

## Architecture

### Role-based controller split, not a modular/package structure

Controllers, views, and (partly) models are split by **who is accessing them**, not by feature:

- `app/Http/Controllers/Admin/helpdesk/*` — admin panel (agents, departments, SLA, workflows, settings, templates…)
- `app/Http/Controllers/Agent/helpdesk/*` and `Agent/kb/*` — agent-facing ticket handling, dashboard, reports, canned responses
- `app/Http/Controllers/Client/helpdesk/*` and `Client/kb/*` — end-user (client) facing ticket + knowledge-base controllers
- `app/Model/helpdesk/*` — Eloquent models grouped by domain (`Ticket`, `Agent`, `Guest`, `Workflow`, `Notification`, `Settings`, `Theme`, `Form`, …), shared across the Admin/Agent/Client controllers above
- `app/Api/` — separate REST API surface (`ApiServiceProvider` loads `app/Api/routes.php`; versioned controllers in `app/Api/v1` and `app/Api/v2`)

When fixing a bug or adding a feature, check whether the same logic needs to be mirrored across Admin/Agent/Client variants — they frequently duplicate similar flows for each role rather than sharing a single controller.

### Routing

All routes are registered through `app/Providers/RouteServiceProvider.php`, which manually requires four route files under different middleware groups:

- `routes/web.php` (818 lines) — the bulk of the app; internally grouped by middleware stack, e.g. guest (`install`,`update`), authenticated user (`install`,`roles`,`auth`,`update`), agent (`install`,`update`,`auth`,`role.agent`), etc. Look at the `Route::middleware(...)->group(...)` blocks to find where a route belongs.
- `routes/api.php` — mounted under `api` middleware + `/api` prefix
- `routes/installer.php` — mounted under `installer` middleware, for the first-run install wizard
- `routes/update.php` — mounted under `redirect`,`install` middleware, prefix `app/update`

**Important:** `RouteServiceProvider` does **not** set `protected $namespace`, so Laravel never prepends a root controller namespace when resolving action strings. Always reference controllers with their fully-qualified class (`[Client\kb\UserController::class, 'method']`) or by named route (`route('name')`) — never with short action strings like `'Client\kb\UserController@method'` in Blade/`Form::open()`, or `url()->action()` resolution will silently fail with "Action ... not defined" (a bug pattern that has recurred in this codebase; see git history for `contact.blade.php`).

Route **names must be unique app-wide** across `web.php`/`api.php`/etc. — a past bug had two API routes accidentally sharing the same `->name(...)`, which only surfaces as a fatal `LogicException` when routes are cached/serialized, not on every request.

### Theming

Views live under `resources/views/themes/default1/{admin,agent,client,common,installer,layouts,login,update}/`. Views are referenced by full dotted path (e.g. `themes.default1.agent.helpdesk.ticket.timeline`), and controllers hardcode the `default1` theme — there is no dynamic theme-switching layer currently in use.

### Middleware / route names of note (`app/Http/Kernel.php`)

- `role.agent`, `role.user`, `roles` — role-based access gates for the Agent/Client/Admin areas
- `update` (`CheckUpdate` middleware) — checks for new Faveo versions / shows update bar notifications; reads/writes the `bar_notifications` table
- `install`, `installer` — gate access based on whether the app has completed the install wizard
- `api` (custom `ApiKey` middleware, distinct from Laravel's default `api` group name) — API key auth for the REST API

### Background/console

`app/Console/Commands/` holds custom artisan commands for install/update flows (`Install`, `InstallDB`, `Sync`, `SyncFaveoToLatestVersion`, `UpdateEncryption`, `SecureFaveoAPPKey`), mail fetching (`TicketFetch`), and reporting (`SendReport`). Ticket workflows (auto-close, SLA escalation, etc.) are driven by `App\Plugins` (`app/Plugins/ServiceProvider.php`) combined with `Admin/helpdesk/WorkflowController.php` and `Agent/helpdesk/TicketWorkflowController.php`.

### Error logs

Application errors land in `storage/logs/laravel.log` (daily/single Laravel log, see `config/logging.php`). When debugging, check this file first — recurring `ERROR` entries here have previously pointed to real bugs (route/action mismatches, type errors from Eloquent Collections passed to native PHP array functions), while single-timestamp bursts of identical DB connection errors have turned out to be transient infra issues (MySQL restart), not application bugs. Bugsnag is also wired in (`bugsnag/bugsnag-laravel`) for production error monitoring — disable it in local/dev via the admin panel's "Error logs and debugging" settings or `.env` `APP_ENV`.

## Gotchas

- After changing routes, views, or config in a running/deployed environment, Laravel's compiled caches (`bootstrap/cache/routes-v7.php`, compiled views, config cache) can serve stale versions to already-running PHP-FPM/queue workers. Run `route:clear`/`view:clear`/`config:clear` and `queue:restart` after deploys to avoid transient "view not found" / stale-route errors.
- `pluck()` on Eloquent/query builders returns an `Illuminate\Support\Collection`, not a plain array — don't pass its result straight into native PHP array functions (`array_key_exists`, etc.); use the Collection's own methods (`->has()`, `->get()`, …).
