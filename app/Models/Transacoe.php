<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transacoe extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'num_conta',
        'valor',
        'tipo',
        'hora_data',
        'moeda',
    ];

    public function conta(){
        return $this->belongsTo(Conta::class);
    }

}