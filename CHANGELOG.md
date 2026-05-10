# Changelog

All notable changes to `empire2/gaze-ticketsystem` are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added
- Initial extraction from EmpireTwo/Dashboard.
- Livewire admin board (kanban + list + split-view detail panel) under `/tickets`.
- `Ticket`, `TicketComment`, `TicketStatus`, `TicketType` models with auto-generated `TK-YYYYMM-NNNNN` ticket numbers, Spatie activity log + media library integration.
- `Priority` enum with German labels, badge classes, colors and icons.
- AI ticket analysis service (`TicketAiAnalysisService`) backed by `laravel/ai` agents and overridable prompt templates.
- `gaze-ticketsystem:check-follow-ups` console command (alias `ticket:check-follow-ups`) and optional hourly schedule registration.
- `TicketAssignedNotification`, `TicketCommentAddedNotification`, `TicketFollowUpDueNotification` (database channel).
- Configurable host integration: User model, optional Customer model, admin resolver closure, layout, route prefix, middleware, scheduler toggle, AI/media settings.
- Soft optional Ghostwriter integration via the `TicketSourceResolver` contract and `source_resolvers` config map. The bundled `GhostwriterSourceResolver` feature-detects `empire2/gaze-ghostwriter` at runtime and degrades gracefully when it is not installed.
- Bundled `Empire2\GazeTicketsystem\Livewire\Toast` component as a thin wrapper around Livewire dispatch events.
- Default seeders for ticket statuses and types, plus an optional ghostwriter smart-action seeder.
