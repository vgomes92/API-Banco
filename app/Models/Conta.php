<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conta extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'num_conta',
        'moeda',
        'saldo',
    ];

    public function transacoes(){
        return $this->hasMany(Transacoe::class);
    }

    



}