<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SettingFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property array<array-key, mixed> $value
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static SettingFactory factory($count = null, $state = [])
 * @method static Builder<static>|Setting newModelQuery()
 * @method static Builder<static>|Setting newQuery()
 * @method static Builder<static>|Setting query()
 * @method static Builder<static>|Setting whereCreatedAt($value)
 * @method static Builder<static>|Setting whereId($value)
 * @method static Builder<static>|Setting whereKey($value)
 * @method static Builder<static>|Setting whereUpdatedAt($value)
 * @method static Builder<static>|Setting whereValue($value)
 *
 * @mixin Eloquent
 */
final class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
