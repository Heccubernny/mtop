<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReloadlyApi extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const PROVIDER_RELOADLY = 'RELOADLY';
    const PROVIDER_CLUBKONNECT = 'CLUBKONNECT';


    const GIFT_CARD = 'GIFT-CARD';

    const UTILITY_PAYMENT = 'UTILITY-PAYMENT';
    
    const UTILITY = 'UTILITY';

    const MOBILE_TOPUP = 'MOBILE-TOPUP';

    const STATUS_ACTIVE = 1;

    const ENV_SANDBOX = 'SANDBOX';

    const ENV_PRODUCTION = 'PRODUCTION';

    protected $casts = [
        'credentials' => 'object',
    ];

    /**
     * Get reloadly api configuration
     */
    public function scopeReloadly($query)
    {
        return $query->where('provider', self::PROVIDER_RELOADLY);
    }

    public function scopeClubkonnect($query)
    {
        return $query->where('provider', self::PROVIDER_CLUBKONNECT);
    }

    public function scopeGiftCard($query)
    {
        return $query->where('type', self::GIFT_CARD);
    }

    public function scopeUtilityPayment($query)
    {
        return $query->where('type', self::UTILITY_PAYMENT);
    }

    public function scopeMobileTopUp($query)
    {
        return $query->where('type', self::MOBILE_TOPUP);
    }

    public function scopeUtility($query)
    {
        return $query->where('type', self::UTILITY);
    }

    /**
     * Get active record
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
