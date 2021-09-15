<?php
    
namespace Tests\Controllers;
    
use Illuminate\Http\Response;
use Tests\TestCase;
    
class TransacoesControllerTest extends TestCase {
    
    public function testdepositoformat() {
        $this->json('get', 'api/deposito/10/BRL/100');
        $this->json('get', 'api/deposito/10/USD/100')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(
                [
                    'num_conta',
                    'moeda',
                    'valor',
                    'tipo',
                    'data',
                    'novo_saldo',
                    'sucessfull'

                ]
            );
        
    }

    public function testsaqueformat() {
    
        $this->json('get', 'api/saque/10/USD/110')
             ->assertStatus(Response::HTTP_OK)
             ->assertJsonStructure(
                 [
                    'num_conta',
                    'moeda',
                    'valor',
                    'tipo',
                    'data',
                    'novo_saldo' => [
                        '*' => [
                            'moeda',
                            'saldo'
                        ]
                    ]
    
                 ]
             );
        
             $this->json('get', 'api/saque/10/BRL/120')
             ->assertStatus(Response::HTTP_OK)
             ->assertJsonStructure(
                 [
                    'num_conta',
                    'moeda',
                    'valor',
                    'tipo',
                    'data',
                    'novo_saldo' => [
                        '*' => [
                            'moeda',
                            'saldo'
                        ]
                    ]
    
                 ]
             );
    }

    public function testextratoformat() {
    
        $this->json('get', 'api/extrato/10/10-09-2021/14-09-2021')
             ->assertStatus(Response::HTTP_OK)
             ->assertJsonStructure(
                    [
                        '*' => [
                            'num_conta',
                            'valor',
                            'tipo',
                            'moeda',
                            'data_hora'
                        ]
                    ]
    
             );
    }

    public function testmoedainexistentesaque() {
    
        $this->json('get', 'api/saque/10/SSS/100')
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

    public function testmoedainexistentedeposito() {
    
        $this->json('get', 'api/deposito/10/AAA/100')
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

    public function testdeposito() {
        $conta = rand();
        $this->json('get', 'api/deposito/$conta/USD/100');
        $this->json('get', 'api/deposito/12/USD/100');
        $this->json('get', 'api/deposito/500/USD/100');
        $this->json('get', 'api/deposito/500/BRL/100');
        $response = $this->json('get', 'api/deposito/12/BRL/100');

        $this->assertEquals($response['num_conta'], '12');
        $this->assertEquals($response['moeda'], 'BRL');
        $this->assertEquals($response['valor'], '100');
        $this->assertEquals($response['tipo'], 'deposito');
    }
    
    public function testsaque() {

        $response = $this->json('get', 'api/saque/12/BRL/200');

        $this->assertEquals($response['num_conta'], '12');
        $this->assertEquals($response['moeda'], 'BRL');
        $this->assertEquals($response['valor'], '200');
        $this->assertEquals($response['tipo'], 'saque');

        $response = $this->json('get', 'api/saque/500/USD/110');

        $this->assertEquals($response['num_conta'], '500');
        $this->assertEquals($response['moeda'], 'USD');
        $this->assertEquals($response['valor'], '110');
        $this->assertEquals($response['tipo'], 'saque');
    }

    public function testextratomsg() {

        $response = $this->json('get', 'api/extrato');

        $this->assertEquals($response['msg'], 'informe a conta, data inicial e data final. As datas devem estar no formato: YYYY-mm-dd');
    }

    public function testsaquemsg() {

        $response = $this->json('get', 'api/saque');

        $this->assertEquals($response['msg'], 'informe a conta, moeda e valor!');
    }

    public function containexistente() {
        $conta = rand();
        $response = $this->json('get', 'api/deposito/$conta/BRL/1000');

        $this->assertEquals($response['msg'], 'Conta solicitada nao existe!');

        $response = $this->json('get', 'api/saque/$conta/BRL/1000');

        $this->assertEquals($response['msg'], 'Conta solicitada nao existe!');
    }


    
    
}