<?php

namespace App\Models\LwsisApp\Hris;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeAddress extends Model
{
    protected $table = 'hris.hr_employee_addresses';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'employee_id',
        'address_type_id',
        'street',
        'barangay_id',
        'city_municipality_id',
        'province_id',
        'region_id',
        'zipcode_id',
        'country_id',
        'is_primary',
        'created_at',
        'updated_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function addressType()
    {
        return $this->belongsTo(HrAddressType::class, 'address_type_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    public function cityMunicipality()
    {
        return $this->belongsTo(CityMunicipality::class, 'city_municipality_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function zipcode()
    {
        return $this->belongsTo(Zipcode::class, 'zipcode_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}