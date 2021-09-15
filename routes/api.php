<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::namespace('Api')
    ->group(function(){

        
        Route::get('/saldo', 'ContasController@index');
        Route::get('/saldo/{num_conta}/{moeda?}', 'ContasController@show');
        Route::get('/deposito','TransacoesController@index');
        Route::get('/deposito/{num_conta}/{moeda}/{valor}', 'TransacoesController@deposito');
        Route::get('/saque', 'TransacoesController@index');
        Route::get('/saque/{num_conta}/{moeda}/{valor}', 'TransacoesController@saque');
        Route::get('/extrato', 'TransacoesController@index_extrato');
        Route::get('/extrato/{num_conta}/{data_inicial}/{data_final}', 'TransacoesController@extrato');
        Route::get('/moedas', 'TransacoesController@retorna_moedas');
        
        
        

    });


