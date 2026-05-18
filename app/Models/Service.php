<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'link',
        'image',
        'status',
    ];

    /**
     * Resolve stable `link` values for the given service primary keys (users.services).
     *
     * @param  array<int, mixed>|null  $ids
     * @return array<int, string>
     */
    public static function linksForServiceIds(?array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return static::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->pluck('link')
            ->map(fn ($link) => is_string($link) ? trim($link) : '')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Attach service_names and service_links for API serialization (matches `services.link` in DB).
     */
    public static function appendPresentationToUser(User $user): void
    {
        $ids = $user->services ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }

        $user->setAttribute('service_links', static::linksForServiceIds($ids));

        if ($ids === []) {
            $user->setAttribute('service_names', []);

            return;
        }

        $byId = static::query()->whereIn('id', $ids)->get()->keyBy('id');
        $user->setAttribute('service_names', collect($ids)->map(function ($id) use ($byId) {
            $row = $byId->get($id);

            return $row->name ?? '';
        })->values()->all());
    }
}
