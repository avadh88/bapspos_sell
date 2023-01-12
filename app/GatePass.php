<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GatePass extends Model
{

    protected $table = 'gate_pass';
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
    * Get the attributes for the Item.
    */
    public function values()
    {
        return $this->hasMany(\App\GatePassItems::class);
    }

}
