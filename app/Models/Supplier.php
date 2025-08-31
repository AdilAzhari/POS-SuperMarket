<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SupplierFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $address
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property-read Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 *
 * @method static SupplierFactory factory($count = null, $state = [])
 * @method static Builder<static>|Supplier newModelQuery()
 * @method static Builder<static>|Supplier newQuery()
 * @method static Builder<static>|Supplier query()
 * @method static Builder<static>|Supplier whereAddress($value)
 * @method static Builder<static>|Supplier whereContactEmail($value)
 * @method static Builder<static>|Supplier whereContactPhone($value)
 * @method static Builder<static>|Supplier whereCreatedAt($value)
 * @method static Builder<static>|Supplier whereId($value)
 * @method static Builder<static>|Supplier whereName($value)
 * @method static Builder<static>|Supplier whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_phone',
        'contact_email',
        'address',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
