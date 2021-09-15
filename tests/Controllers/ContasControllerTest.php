<?php
    
namespace Tests\Controllers;
    
use Illuminate\Http\Response;
use Tests\TestCase;
use App\Http\Controllers\Api\ContasController;
    
class ContasControllerTest extends TestCase {

    public function testsaldoformat() {
        $this->json('get', 'api/saque/10/BRL/100');
        $this->json('get', 'api/saque/10/USD/100');
        $this->json('get', 'api/saque/10/EUR/100');
        $this->json('get', 'api/deposito/10/BRL/100');
        $this->json('get', 'api/deposito/10/USD/100');
        $this->json('get', 'api/deposito/10/EUR/100');
        $this->json('get', 'api/saldo/10')
         ->assertStatus(Response::HTTP_OK)
         ->assertJsonStructure(
             [
                'conta',
                'data',
                'saldo_detalhado' => [
                     '*' => [
                         'saldo',
                         'moeda'
                     ]
                 ]
             ]
         );

         
    }

    public function testsaldomoedaformat() {
        $this->json('get', 'api/saldo/10/BRL')
         ->assertStatus(Response::HTTP_OK)
         ->assertJsonStructure(
             [
                'saldo',
                'moeda',
                'data',
                'saldo_detalhado' => [
                     '*' => [
                         'saldo',
                         'moeda_conversao',
                         'taxa_venda',
                         'taxa_compra'
                     ]
                 ]
             ]
         );
         $this->json('get', 'api/saldo/10/USD')
         ->assertStatus(Response::HTTP_OK)
         ->assertJsonStructure(
             [
                'saldo',
                'moeda',
                'data',
                'saldo_detalhado' => [
                     '*' => [
                         'saldo',
                         'moeda_conversao',
                         'taxa_venda',
                         'taxa_compra'
                     ]
                 ]
             ]
         );
    }

    

    public function testmoedainexistente() {
    
        $this->json('get', 'api/saldo/10/AAA')
             ->assertStatus(Response::HTTP_OK)
             ->assertJsonStructure(
                 [
                    'msg',
                    'moedas validas' => [
                         '*' => [
                             'simbolo',
                             'nomeFormatado',
                             'tipoMoeda'
                         ]
                     ]
                 ]
             );
    }

    public function testsemconta() {
    
        $this->json('get', 'api/saldo/')
             ->assertStatus(Response::HTTP_OK)
             ->assertJsonStructure(
                 [
                    'msg'
                 ]
             );
    }

    public function testcontainexistente() {
    
        $this->json('get', 'api/saldo/2121361316')
             ->assertStatus(Response::HTTP_OK)
             ->assertJsonStructure(
                 [
                    'msg'
                 ]
             );

             $this->json('get', 'api/saldo/2121361316/USD')
             ->assertStatus(Response::HTTP_OK)
             ->assertJsonStructure(
                 [
                    'msg'
                 ]
             );
    }

    
    
}