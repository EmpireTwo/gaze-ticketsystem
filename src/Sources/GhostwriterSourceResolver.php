<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Sources;

use Empire2\GazeTicketsystem\Contracts\TicketSourceResolver;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Soft optional resolver for the `support_draft` source type backed by the
 * `empire2/gaze-ghostwriter` package.
 *
 * This class is designed to be safe to ship even when ghostwriter is NOT
 * installed: it feature-detects the upstream classes via `class_exists()`
 * and degrades to returning null when they are missing.
 *
 * When ghostwriter IS installed the resolver:
 *   1. Loads the support draft via `Empire2\GazeGhostwriter\Models\SupportDraft`.
 *   2. Reads its associated mail message (subject, body_text, body_html, from).
 *   3. Optionally resolves a customer from the sender via
 *      `Empire2\GazeGhostwriter\Support\SmartActionCustomerResolver`.
 *   4. Builds a "view source" URL targeting the ghostwriter draft page.
 */
final class GhostwriterSourceResolver implements TicketSourceResolver
{
    private const SUPPORT_DRAFT_CLASS = 'Empire2\\GazeGhostwriter\\Models\\SupportDraft';

    private const CUSTOMER_RESOLVER_CLASS = 'Empire2\\GazeGhostwriter\\Support\\SmartActionCustomerResolver';

    private const ROUTE_NAME = 'gaze-ghostwriter.drafts.show';

    public function resolve(string $sourceType, mixed $sourceId): ?TicketSourceData
    {
        if ($sourceType !== 'support_draft') {
            return null;
        }

        if (! class_exists(self::SUPPORT_DRAFT_CLASS)) {
            return null;
        }

        $draftClass = self::SUPPORT_DRAFT_CLASS;

        try {
            /** @var object|null $draft */
            $draft = $draftClass::query()->with('message')->find($sourceId);
        } catch (Throwable) {
            return null;
        }

        if ($draft === null) {
            return null;
        }

        $message = $draft->message ?? null;

        if ($message === null) {
            return null;
        }

        $subject = (string) ($message->subject ?? '');
        $title = $subject !== '' ? "Ticket: {$subject}" : null;

        $body = $message->body_text ?? null;

        if (! is_string($body) || trim($body) === '') {
            $html = $message->body_html ?? '';
            $body = is_string($html) ? trim(strip_tags($html)) : null;
        }

        $contactName = is_string($message->from_name ?? null) ? $message->from_name : null;
        $contactEmail = is_string($message->from_email ?? null) ? $message->from_email : null;
        $customerId = null;

        if (class_exists(self::CUSTOMER_RESOLVER_CLASS) && $contactEmail !== null) {
            try {
                $customer = call_user_func([self::CUSTOMER_RESOLVER_CLASS, 'resolve'], $contactEmail);
            } catch (Throwable) {
                $customer = null;
            }

            if (is_object($customer) && property_exists($customer, 'id')) {
                $customerId = $customer->id;
                $name = $customer->fullname ?? null;
                if (is_string($name) && $name !== '') {
                    $contactName = $name;
                }
                $email = $customer->user->email ?? null;
                if (is_string($email) && $email !== '') {
                    $contactEmail = $email;
                }
            }
        }

        $url = null;
        try {
            if (Route::has(self::ROUTE_NAME)) {
                $url = (string) route(self::ROUTE_NAME, $sourceId);
            }
        } catch (Throwable) {
            $url = null;
        }

        return new TicketSourceData(
            title: $title,
            body: null,
            contactName: $contactName,
            contactEmail: $contactEmail,
            customerId: $customerId,
            url: $url,
            sourceContext: is_string($body) ? trim($body) : null,
        );
    }
}
