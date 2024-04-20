<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plasma extends Model
{
    use HasFactory;

    protected $table="plasmas";

    protected $fillable = [
        'freezer_id',
        'bag_serial_number',
        'group_id',
        'donation_date',
        'hospital_id',
    ];

    public function freezer()
    {
       return $this->belongsTo(Freezer::class);
    }

    public function staff()
    {
       return $this->belongsTo(Staff::class);
    }

    public function bank()
    {
       return $this->belongsTo(Bank::class);
    }

    public function group()
    {
       return $this->belongsTo(Group::class);
    }

    public function hospital()
    {
       return $this->belongsTo(Hospital::class);
    }
}
