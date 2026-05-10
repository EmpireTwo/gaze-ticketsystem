<?php

declare(strict_types=1);

namespace Empire2\GazeTicketsystem\Models;

use Empire2\GazeTicketsystem\Database\Factories\TicketCommentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int|null $user_id
 * @property string $body
 * @property bool $is_ai_response
 * @property bool $is_internal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ticket $ticket
 * @property-read Model|null $author
 * @property-read MediaCollection<int, Media> $media
 *
 * @mixin \Eloquent
 */
class TicketComment extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'body',
        'is_ai_response',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_ai_response' => 'boolean',
            'is_internal' => 'boolean',
        ];
    }

    protected static function newFactory(): Factory
    {
        return TicketCommentFactory::new();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function author(): BelongsTo
    {
        /** @var class-string<Model> $model */
        $model = config('gaze-ticketsystem.user_model', \App\Models\User::class);

        return $this->belongsTo($model, 'user_id');
    }
}
