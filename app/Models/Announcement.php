<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'category',
        'body',
        'author_name',
        'is_pinned',
        'publish_at',
        'ends_at',
    ];

    protected $casts = [
        'is_pinned'  => 'boolean',
        'publish_at' => 'datetime',
        'ends_at'    => 'date',
    ];

    // Visible na sa residents
    public function scopePublished($query)
    {
        return $query->where('publish_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', today());
            });
    }

    // Naka-schedule pa
    public function scopeScheduled($query)
    {
        return $query->where('publish_at', '>', now());
    }

    // Parehong shape ng sample arrays na ginamit ng design
    public function toCard(): array
    {
        return [
            'id'       => $this->id,
            'title'    => $this->title,
            'category' => $this->category,
            'date'     => $this->publish_at->format('F j, Y'),
            'time'     => $this->publish_at->format('g:i A'),
            'author'   => $this->author_name ?? 'Admin',
            'body'     => $this->body,
            'pinned'   => $this->is_pinned,
            'tone'     => match ($this->category) {
                'Utilities'       => 'copper',
                'Community event' => 'teal',
                'Maintenance'     => 'success',
                default           => 'ink',
            },
        ];
    }
}