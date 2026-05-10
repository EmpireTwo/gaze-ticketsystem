# Gaze Ticketsystem

Standalone Laravel ticket-system. Livewire-driven kanban + list board, AI-assisted ticket analysis, follow-up reminders, notifications, and a soft optional integration with [`empire2/gaze-ghostwriter`](https://github.com/EmpireTwo/gaze-ghostwriter) for turning support drafts into tickets.

[![CI](https://github.com/EmpireTwo/gaze-ticketsystem/actions/workflows/ci.yml/badge.svg)](https://github.com/EmpireTwo/gaze-ticketsystem/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/empire2/gaze-ticketsystem.svg)](https://packagist.org/packages/empire2/gaze-ticketsystem)
[![License](https://img.shields.io/packagist/l/empire2/gaze-ticketsystem.svg)](LICENSE)

The package ships:

- A `tickets` URL prefix with kanban + list + split detail-panel views (Livewire 4).
- `Ticket`, `TicketComment`, `TicketStatus`, `TicketType` Eloquent models with auto-generated `TK-YYYYMM-NNNNN` numbers, Spatie activity log + media library integration.
- A `TicketAiAnalysisService` driven by `laravel/ai` agents, with overridable prompt templates.
- A `gaze-ticketsystem:check-follow-ups` command (alias `ticket:check-follow-ups`) and an opt-in hourly schedule.
- Database notifications for assignments, new comments, and overdue follow-ups.
- A neutral `TicketSourceResolver` contract that lets you plug in arbitrary external sources (e.g. ghostwriter support drafts) without coupling the package to them.

## Requirements

- PHP `^8.3` (`laravel/ai` requires PHP 8.3+)
- Laravel `^12.0` (`laravel/ai` requires Laravel 12+)
- Livewire `^4.0`
- `empiretwo/gaze-laravel` (auto-installed)
- `spatie/laravel-activitylog` `^4.8`
- `spatie/laravel-medialibrary` `^11.3`
- `laravel/ai` `^0.4.3`

## Install

```bash
composer require empire2/gaze-ticketsystem

php artisan vendor:publish --tag=gaze-ticketsystem-config
php artisan vendor:publish --tag=gaze-ticketsystem-migrations
php artisan migrate
```

Composer will pull `empiretwo/gaze-laravel` automatically; the gaze CLI binary is downloaded into `vendor/bin/gaze` by its bundled installer plugin (Composer asks you to trust the plugin once).

Optional:

```bash
php artisan vendor:publish --tag=gaze-ticketsystem-views
php artisan vendor:publish --tag=gaze-ticketsystem-seeders
```

Seed the default ticket statuses and types from your `DatabaseSeeder`:

```php
$this->call([
    \Empire2\GazeTicketsystem\Database\Seeders\TicketStatusSeeder::class,
    \Empire2\GazeTicketsystem\Database\Seeders\TicketTypeSeeder::class,
]);
```

## Configuration

`config/gaze-ticketsystem.php` exposes every host integration point. The most relevant keys:

| Key | Purpose |
| --- | --- |
| `enabled` | Master switch; disables routes + Livewire registration when false. |
| `user_model` | Authenticatable model used for assignees, creators and comment authors. |
| `customer_model` | Optional. When set, the create form shows a customer search box and `Ticket::customer()` returns a real relation; when null, customer search is hidden. |
| `admin_resolver` | Optional `callable(): Collection<Authenticatable>` used to populate "assign to" dropdowns and follow-up notification recipients. |
| `layout` | Blade layout used by the package's Livewire components (default `components.layouts.app`). |
| `middleware` | Middleware stack for the package routes (default `['web', 'auth']`). |
| `route_prefix` | URL prefix (default `tickets`). |
| `schedule_follow_ups` | When true the service provider registers `CheckTicketFollowUpsCommand` to run hourly in production. |
| `source_resolvers` | Map of `source_type => TicketSourceResolver class-string`. See "Optional Ghostwriter integration" below. |
| `ai.*` | AI feature flags + analysis model. |
| `notifications.follow_up_due_after_hours` | Threshold used by host applications to trigger follow-up notifications. |
| `media.disk` | Disk used for ticket / comment attachments. |

## Host User model integration

The package never imports your `User` model. Instead, three things must line up:

1. The model class is configured via `gaze-ticketsystem.user_model`.
2. The model is `Authenticatable` (any standard Laravel User works).
3. The "admins" lookup either:
   - is provided as a closure in `gaze-ticketsystem.admin_resolver`, **or**
   - exposes an `admins()` query scope on the user model.

The package ships an `IsTicketAdmin` trait you can drop onto your user model as a starting point:

```php
use Empire2\GazeTicketsystem\Concerns\IsTicketAdmin;

class User extends Authenticatable
{
    use IsTicketAdmin;

    public function scopeAdmins($query)
    {
        return $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin']));
    }
}
```

Or wire it via config without touching the model:

```php
'admin_resolver' => fn () => \App\Models\User::query()
    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin']))
    ->get(),
```

The contract is: the resolver returns an `Illuminate\Support\Collection<Authenticatable>`.

## Optional Ghostwriter integration

When `empire2/gaze-ghostwriter` is installed alongside this package, support drafts can be turned into tickets via the bundled `GhostwriterSourceResolver` (already mapped under `'support_draft'` in the default config).

The `GhostwriterSourceResolver` feature-detects ghostwriter at runtime — it is safe to ship in stand-alone setups and silently no-ops when the upstream classes are missing.

To wire your own source type, implement the contract:

```php
use Empire2\GazeTicketsystem\Contracts\TicketSourceResolver;
use Empire2\GazeTicketsystem\Sources\TicketSourceData;

class CrmInquirySourceResolver implements TicketSourceResolver
{
    public function resolve(string $sourceType, mixed $sourceId): ?TicketSourceData
    {
        if ($sourceType !== 'crm_inquiry') {
            return null;
        }

        $inquiry = \App\Models\CrmInquiry::find($sourceId);

        return $inquiry ? new TicketSourceData(
            title: "Anfrage: {$inquiry->subject}",
            contactName: $inquiry->contact_name,
            contactEmail: $inquiry->contact_email,
            sourceContext: $inquiry->message,
            url: route('crm.inquiries.show', $inquiry),
        ) : null;
    }
}
```

Then map it in `config/gaze-ticketsystem.php`:

```php
'source_resolvers' => [
    'support_draft' => \Empire2\GazeTicketsystem\Sources\GhostwriterSourceResolver::class,
    'crm_inquiry'   => \App\Tickets\CrmInquirySourceResolver::class,
],
```

Visiting `/tickets/create?prefill=1&source_type=crm_inquiry&source_id=42` will then prefill the ticket form from your CRM record, and `Ticket::sourceUrl()` will link back to it.

## Quick start

```php
use Empire2\GazeTicketsystem\Models\Ticket;
use Empire2\GazeTicketsystem\Enums\Priority;

$ticket = Ticket::create([
    'title'         => 'Login flow broken',
    'body'          => 'User reports a 500 on /login since 9:30',
    'contact_name'  => 'Max Mustermann',
    'contact_email' => 'max@example.com',
    'created_by'    => $authUser->id,
    'status_id'     => $defaultStatus->id,
    'type_id'       => $supportType->id,
    'priority'      => Priority::HIGH,
]);
```

Then visit `/tickets` for the kanban board, `/tickets/{ticket}` for the split view, and `/tickets/settings` to manage statuses + types.

## Privacy boundaries

This package routes every text prompt and structured LLM response through
the [`empiretwo/gaze-laravel`](https://packagist.org/packages/empiretwo/gaze-laravel)
boundary. With `gaze_enabled=true` (config key
`gaze-ticketsystem.ai.gaze_enabled`, env `GAZE_TICKETSYSTEM_GAZE_ENABLED`),
prompts are passed through `gaze clean` before they reach the model, and
the `restore` step puts placeholder tokens back into the model output
before persistence. With `gaze_enabled=false` (default), the
`GuardedAgentRunner` short-circuits with `GazeDisabledException` — there is
no bypass branch, all three AI entry points (`analyzeRaw`, `analyze`,
`replyToComment`) fail closed.

**Image attachments are NOT redacted.** Gaze is a text-only boundary.
Ticket screenshots and other image attachments are sent to the configured
AI provider as-is. Each AI call with non-empty attachments emits a
`Log::warning('gaze-ticketsystem AI call with un-redactable image
attachments', ['ticket_id' => ..., 'count' => N])` so operators can audit
out-of-band PII exposure. Treat image upload as out-of-band PII exposure
and disable image attachments if your compliance posture forbids it.

**Embeddings:** this package does not generate embeddings — it only sends
text prompts (with optional images) to a structured-output agent. If you
add embedding paths in a downstream extension, route the input text
through `Gaze::clean()` only (no restore) and skip the call when
`gaze_enabled=false` (fail-closed).

## Console commands

```bash
php artisan gaze-ticketsystem:check-follow-ups
# alias:
php artisan ticket:check-follow-ups
```

When `gaze-ticketsystem.schedule_follow_ups` is true (default), the service provider registers the command to run hourly in `production`. Disable it and wire it up yourself in `routes/console.php` if you need different scheduling.

## Testing

```bash
composer test
composer analyse
composer format
```

The package ships its own Pest test suite. Tests that exercise the full Livewire UI assume a host User model is configured — see `tests/TestCase.php` for the orchestration. The `GhostwriterIntegrationTest` is skipped automatically when `empire2/gaze-ghostwriter` is not installed.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT — see [LICENSE](LICENSE).
