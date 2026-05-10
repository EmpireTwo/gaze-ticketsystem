<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Models;

use App\Models\User;
use Empire2\GazeTicketsystem\Contracts\TicketSourceResolver;
use Empire2\GazeTicketsystem\Database\Factories\TicketFactory;
use Empire2\GazeTicketsystem\Enums\Priority;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $ticket_number
 * @property string $title
 * @property string $body
 * @property string $contact_name
 * @property string $contact_email
 * @property int|null $customer_id
 * @property int|null $assigned_to
 * @property int|null $created_by
 * @property int $status_id
 * @property int $type_id
 * @property Priority $priority
 * @property string|null $source_type
 * @property int|null $source_id
 * @property Carbon|null $follow_up_at
 * @property Carbon|null $follow_up_notified_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TicketStatus $status
 * @property-read TicketType $type
 * @property-read Authenticatable|null $assignee
 * @property-read Authenticatable|null $creator
 * @property-read Model|null $customer
 * @property-read Collection<int, TicketComment> $comments
 * @property-read MediaCollection<int, Media> $media
 *
 * @mixin \Eloquent
 */
class Ticket extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity;

    protected $fillable = [
        'ticket_number',
        'title',
        'body',
        'contact_name',
        'contact_email',
        'customer_id',
        'assigned_to',
        'created_by',
        'status_id',
        'type_id',
        'priority',
        'source_type',
        'source_id',
        'follow_up_at',
        'follow_up_notified_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'follow_up_at' => 'datetime',
            'follow_up_notified_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }
        });
    }

    protected static function newFactory(): Factory
    {
        return TicketFactory::new();
    }

    public static function generateTicketNumber(): string
    {
        $prefix = 'TK-'.now()->format('Ym');
        $lastTicket = static::query()
            ->where('ticket_number', 'like', $prefix.'%')
            ->orderByDesc('ticket_number')
            ->value('ticket_number');

        if (is_string($lastTicket) && $lastTicket !== '') {
            $lastSequence = (int) substr($lastTicket, -5);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix.'-'.str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['follow_up_notified_at']);
    }

    /**
     * @return BelongsTo<TicketStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'type_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function assignee(): BelongsTo
    {
        /** @var class-string<Model> $model */
        $model = $this->resolveUserModel();

        return $this->belongsTo($model, 'assigned_to');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function creator(): BelongsTo
    {
        /** @var class-string<Model> $model */
        $model = $this->resolveUserModel();

        return $this->belongsTo($model, 'created_by');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function customer(): BelongsTo
    {
        /** @var class-string<Model>|null $model */
        $model = config('gaze-ticketsystem.customer_model');

        // When the host hasn't configured a customer model we still need to
        // return a BelongsTo so chained calls don't blow up. Bind to a noop
        // self-reference; the relation will yield null.
        if ($model === null || ! class_exists($model)) {
            /** @var class-string<Model> $self */
            $self = static::class;

            return $this->belongsTo($self, 'customer_id');
        }

        return $this->belongsTo($model, 'customer_id');
    }

    /**
     * @return HasMany<TicketComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at');
    }

    public function sourceUrl(): ?string
    {
        if (! is_string($this->source_type) || $this->source_type === '' || $this->source_id === null) {
            return null;
        }

        try {
            $resolver = app(TicketSourceResolver::class);
        } catch (\Throwable) {
            return null;
        }

        $data = $resolver->resolve($this->source_type, $this->source_id);

        return $data?->url;
    }

    public function isOverdue(): bool
    {
        return $this->follow_up_at !== null
            && $this->follow_up_at->isPast()
            && $this->closed_at === null;
    }

    /**
     * @return class-string
     */
    private function resolveUserModel(): string
    {
        $model = config('gaze-ticketsystem.user_model');

        if (is_string($model) && $model !== '' && class_exists($model)) {
            return $model;
        }

        return User::class;
    }
}
