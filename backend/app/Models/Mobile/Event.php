<?php

namespace App\Models\Mobile;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mobile.events';
    protected $primaryKey = 'event_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'event_name',
        'event_description',
        'event_date',
        'event_time',
        'event_end_time',
        'event_venue',
        'participants',
        'event_status',
        'event_image',

        // 🔥 notification flags (IMPORTANT)
        'reminder_notified_at',
        'ongoing_notified_at',
        'ended_notified_at',
    ];

    protected $casts = [
        'event_date'            => 'date',
        'reminder_notified_at'  => 'datetime',
        'ongoing_notified_at'   => 'datetime',
        'ended_notified_at'     => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'deleted_at'            => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | TIME ACCESSORS (SAFE FORMAT)
    |--------------------------------------------------------------------------
    */
    public function getEventTimeAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }

    public function getEventEndTimeAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGE ACCESSOR
    |--------------------------------------------------------------------------
    */
    public function getEventImageAttribute($value)
    {
        return $value ? secure_url('storage/' . $value) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT STATUS (SAFE + TIMEZONE AWARE)
    |--------------------------------------------------------------------------
    */
    public function getCurrentStatusAttribute(): string
    {
        try {
            if (!$this->event_date || !$this->getRawOriginal('event_time')) {
                return $this->event_status ?? 'upcoming';
            }

            $date = $this->event_date->toDateString();

            $start = Carbon::parse(
                $date . ' ' . $this->getRawOriginal('event_time'),
                'Asia/Manila'
            );

            $end = $this->getRawOriginal('event_end_time')
                ? Carbon::parse(
                    $date . ' ' . $this->getRawOriginal('event_end_time'),
                    'Asia/Manila'
                )
                : (clone $start)->addHour();

            $now = now('Asia/Manila');

            if ($now->lt($start)) {
                return 'upcoming';
            }

            if ($now->between($start, $end)) {
                return 'ongoing';
            }

            return 'done';

        } catch (\Throwable $e) {
            return $this->event_status ?? 'upcoming';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OPTIONAL: FORCE STATUS UPDATE (USED BY SCHEDULER)
    |--------------------------------------------------------------------------
    */
    public function syncStatus(): string
    {
        $computed = $this->current_status;

        if ($this->event_status !== $computed) {
            $this->event_status = $computed;
            $this->save();
        }

        return $computed;
    }
}

// namespace App\Models\Mobile;

// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;
// use Carbon\Carbon;

// class Event extends Model
// {
//     use HasFactory, SoftDeletes;

//     protected $table = 'mobile.events';
//     protected $primaryKey = 'event_id';
//     public $incrementing = true;
//     protected $keyType = 'int';

//     protected $fillable = [
//         'event_name',
//         'event_description',
//         'event_date',
//         'event_time',
//         'event_end_time',
//         'event_venue',
//         'participants',
//         'event_status', // optional if you still want to persist
//         'event_image',
//     ];

//     protected $casts = [
//         'event_date'     => 'date',
//         'created_at'     => 'datetime',
//         'updated_at'     => 'datetime',
//         'deleted_at'     => 'datetime',
//     ];

//     // ✅ Accessor for formatted times
//     // public function getEventTimeAttribute($value)
//     // {
//     //     return Carbon::parse($value)->format('H:i');
//     // }

//     // public function getEventEndTimeAttribute($value)
//     // {
//     //     return Carbon::parse($value)->format('H:i');
//     // }
//     public function getEventTimeAttribute($value)
// {
//     return $value ? Carbon::parse($value)->format('H:i') : null;
// }

// public function getEventEndTimeAttribute($value)
// {
//     return $value ? Carbon::parse($value)->format('H:i') : null;
// }


//     // ✅ Virtual attribute: current_status
//     public function getCurrentStatusAttribute()
// {
//     $now = Carbon::now();

//     $start = Carbon::parse($this->event_date)->setTimeFromTimeString($this->event_time);
//     $end   = Carbon::parse($this->event_date)->setTimeFromTimeString($this->event_end_time);

//     if ($now->lt($start)) {
//         return 'upcoming';
//     }

//     if ($now->between($start, $end)) {
//         return 'ongoing';
//     }

//     return 'done';
// }

// public function getEventImageAttribute($value)
// {
//     return $value ? secure_url('storage/' . $value) : null;
// }

// // public function getEventImageAttribute($value)
// // {
// //     return $value ? url('storage/' . $value) : null;
// // }

// // App\Models\Event.php
// public function refreshStatus()
// {
//     $now = now();
//     $start = \Carbon\Carbon::parse("{$this->date} {$this->time}");
//     $end   = \Carbon\Carbon::parse("{$this->date} {$this->end_time}");

//     if ($now->lt($start)) {
//         $this->status = 'upcoming';
//     } elseif ($now->between($start, $end)) {
//         $this->status = 'ongoing';
//     } else {
//         $this->status = 'done';
//     }

//     // Save if status actually changed
//     if ($this->isDirty('status')) {
//         $this->save();
//     }

//     return $this->status;
// }



// } 