<?php
namespace App\Models\HRSI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Models\HRSI\EmployeeContact;
use App\Models\HRSI\EmployeeAddress;
use App\Models\HRSI\EmployeeRoleAssignment;


class Employee extends Model
{
    protected $table = 'employees';
    protected $fillable = ['employee_number','user_id','last_name','first_name','middle_name','suffix','sex','date_of_birth','religion_id'];

    // Relationships
    public function appointments(): HasMany { return $this->hasMany(EmployeeAppointment::class); }
    public function currentAppointment(): HasOne {
        return $this->hasOne(EmployeeAppointment::class)
            ->whereRaw('(upper_inf(valid_during) OR current_date <@ valid_during)')
            ->orderByDesc('is_primary')->orderByRaw('lower(valid_during) DESC');
    }
    public function roleAssignments(): HasMany { return $this->hasMany(EmployeeRoleAssignment::class); }
    public function currentRoles(): HasMany {
        return $this->roleAssignments()->whereRaw('(upper_inf(valid_during) OR current_date <@ valid_during)');
    }

    public function contacts(): HasMany { return $this->hasMany(EmployeeContact::class); }
    public function contactsActive(): HasMany {
        return $this->contacts()->whereRaw('(upper_inf(valid_during) OR current_date <@ valid_during)');
    }
    public function addresses(): HasMany { return $this->hasMany(EmployeeAddress::class); }
    public function presentAddress() { return $this->hasOne(EmployeeAddress::class)->where('address_type','present')->whereRaw('(upper_inf(valid_during) OR current_date <@ valid_during)'); }
    public function permanentAddress(){ return $this->hasOne(EmployeeAddress::class)->where('address_type','permanent')->whereRaw('(upper_inf(valid_during) OR current_date <@ valid_during)'); }

    public function govIds(): HasMany { return $this->hasMany(EmployeeGovId::class); }
    public function relatives(): HasMany { return $this->hasMany(EmployeeRelative::class); }
    public function educations(): HasMany { return $this->hasMany(EmployeeEducation::class); }

    // Accessor for current position name (title_override > job position name)
    public function getCurrentPositionNameAttribute(): ?string {
        return $this->currentAppointment?->position_name;
    }
}
