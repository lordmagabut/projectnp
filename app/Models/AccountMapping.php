<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountMapping extends Model
{
    protected $table = 'account_mappings';

    protected $fillable = [
        'key',
        'description',
        'coa_id',
    ];

    public function coa()
    {
        return $this->belongsTo(Coa::class, 'coa_id');
    }

    /**
     * Get COA ID by key
     */
    public static function getCoaId(string $key): ?int
    {
        $mapping = self::where('key', $key)->first();
        return $mapping?->coa_id;
    }

    /**
     * Set COA for a key
     */
    public static function setCoa(string $key, int $coaId, string $description = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'coa_id' => $coaId,
                'description' => $description,
            ]
        );
    }
}
