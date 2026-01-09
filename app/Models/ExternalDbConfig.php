<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalDbConfig extends Model
{
    protected $table = 'external_db_configs';

    protected $fillable = [
        'name',
        'host',
        'port',
        'database',
        'username',
        'password',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Encrypt password when setting
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = encrypt($value);
        }
    }

    /**
     * Decrypt password when getting
     */
    public function getPasswordAttribute($value)
    {
        if ($value) {
            try {
                return decrypt($value);
            } catch (\Exception $e) {
                return $value; // fallback jika tidak ter-encrypt
            }
        }
        return null;
    }

    /**
     * Get active configuration
     */
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Set this config as active and deactivate others
     */
    public function setActive()
    {
        self::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }
}
