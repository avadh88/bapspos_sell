<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GatePassItems extends Model
{

    protected $table = 'gate_pass_items_quantity';
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function gatePassItems()
    {
        return $this->belongsTo(\App\GatePass::class);
    }

}
