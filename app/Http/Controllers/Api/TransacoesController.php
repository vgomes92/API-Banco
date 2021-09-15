<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Conta;
use App\Models\Transacoe;
use Unirest;
use DateTime;

class TransacoesController extends Controller
{   
    
    public function index()
    {   
        $msg = array(
            'msg' => 'informe a conta, moeda e valor!'
        );
        return response()->json($msg);
    }

    public function index_extrato()
    {   
        $msg = array(
            'msg' => 'informe a conta, data inicial e data final. As datas devem estar no formato: YYYY-mm-dd'
        );
        return response()->json($msg);
    }

    public function deposito($conta,$moeda,$valor)
    {   
        //valida moeda
        if(!$this->valida_moedas($moeda)){
            $dados = array(
                'msg' => 'Moeda solicitada nao existe!',
                'moedas validas' => ''
            );

            $dados['moedas validas']=$this->retorna_moedas();

            return response()->json($dados);
        }
        
        $dados = array(
            'num_conta' => '',
            'moeda' => '',
            'valor' => '',
            'tipo' => '',
            'data' =>'',
            'novo_saldo' =>'',
            'sucessfull' => ''
        );

        $contas = Conta::orderBy('num_conta')->where('num_conta',$conta)->get();

        $conta_id = null;
        
        foreach ($contas as $c) {
            if($moeda == $c->moeda){
              $conta_id = $c->id;
              $c->saldo += $valor;
              $c->save();

              $new_transaction = new Transacoe;
              $new_transaction-> num_conta = $conta;
              $new_transaction-> valor = $valor;
              $new_transaction-> tipo = 'deposito';
              $new_transaction-> moeda = $moeda;
              $new_transaction-> hora_data = date("Y-m-d H:i:s");
              $new_transaction->save();

              $dados['num_conta'] = $conta;
              $dados['moeda'] = $moeda;
              $dados['valor'] = $valor;
              $dados['tipo'] = 'deposito';
              $dados['data'] = $new_transaction->hora_data;
              $dados['novo_saldo'] = $c->saldo;
              $dados['sucessfull'] = TRUE;

              return response()->json($dados);
            }
        }

        if(!$conta_id){
            $new_conta = new Conta;
            $new_conta-> num_conta = $conta;
            $new_conta-> moeda = $moeda;
            $new_conta-> saldo = $valor;
            $new_conta->save();

            $new_transaction = new Transacoe;
            $new_transaction-> num_conta = $conta;
            $new_transaction-> valor = $valor;
            $new_transaction-> tipo = 'deposito';
            $new_transaction-> moeda = $moeda;
            $new_transaction-> hora_data = date("Y-m-d H:i:s");
            $new_transaction->save();

            $dados['num_conta'] = $conta;
            $dados['moeda'] = $moeda;
            $dados['valor'] = $valor;
            $dados['tipo'] = 'deposito';
            $dados['data'] = $new_transaction->hora_data;
            $dados['novo_saldo'] = $new_conta->saldo;
            $dados['sucessfull'] = TRUE;

            return response()->json($dados);
        }
    }

    public function saque($conta, $moeda, $valor){

        //valida moeda
        if(!$this->valida_moedas($moeda)){
            $dados = array(
                'msg' => 'Moeda solicitada nao existe!',
                'moedas validas' => ''
            );

            $dados['moedas validas']=$this->retorna_moedas();

            return response()->json($dados);
        }
        
        $dados = array(
            'num_conta' => '',
            'moeda' => '',
            'valor' => '',
            'tipo' => '',
            'data' =>'',
            'novo_saldo' =>'',
            'sucessfull' => ''
        );

        $saldo_contas = array(
            'moeda' => '',
            'saldo' => ''
        );

        $arraySaldos = array();
        
        //pegando as contas iguais a conta passada
        $contas = Conta::orderBy('num_conta')->where('num_conta',$conta)->get();
        
        //conta nao existe
        if(sizeof($contas)==0){
            $dados = array(
                'msg' => 'Conta solicitada nao existe!'
            );
            return response()->json($msg);
        }

        //verifica saldo moeda solicitada
        foreach ($contas as $c) {
            if($c->moeda == $moeda and $c->saldo >= $valor){//possui saldo na moeda solicitada
                $c->saldo -= $valor;
                $c->save();

                $new_transaction = new Transacoe;
                $new_transaction-> num_conta = $conta;
                $new_transaction-> valor = $valor;
                $new_transaction-> tipo = 'saque';
                $new_transaction-> moeda = $moeda;
                $new_transaction-> hora_data = date("Y-m-d H:i:s");
                $new_transaction->save();

                //retorno
                foreach ($contas as $c) {
                    $saldo_contas['moeda'] = $c->moeda;
                    $saldo_contas['saldo'] = $c->saldo;
                    $arraySaldos[] = $saldo_contas;
                }

                $dados['num_conta'] = $conta;
                $dados['moeda'] = $moeda;
                $dados['valor'] = $valor;
                $dados['tipo'] = 'saque';
                $dados['data'] = $new_transaction->hora_data;
                $dados['novo_saldo'] = $arraySaldos;
                $dados['sucessfull'] = TRUE;

                return response()->json($dados);

            }
        }    
        
        //nao possui saldo moeda solicitada
        $saldo_total = $this->calcula_saldo_total($conta,$moeda);


        if($valor>$saldo_total){//Saldo de todas as contas insuficiente
            return response()->json("Saldo Insuficiente!");

        }
        
        //saldo de todas as contas suficiente
        $saldo_soma = 0.0;
        $diferenca = 0.0;
        $saldo_brl = 0.0;
        $cotacao = 0.0;
            
        //retira saldo da moeda solicitada
        foreach ($contas as $c) {
            if($c->moeda == $moeda){
                $saldo_soma = $c->saldo;
                $c->saldo -= $saldo_soma;
                $c->save();

                $diferenca = $valor - $saldo_soma;
            }
        }
            
        //retira saldo outras moedas
        foreach ($contas as $c) {
            if($c->moeda != $moeda){

                if($c->moeda =='BRL'){//moeda é BRL

                    $cotacao = $this->cotacao_venda2($moeda);
                    if(($c->saldo/$cotacao)>=$diferenca){//atingiu valor
                        $cotacao = $this->cotacao_compra2($moeda);   //verifica saldo em BRL que falta
                        $saldo_brl = $diferenca*$cotacao;           //saldo necessário
                        $c->saldo -= $saldo_brl;
                        $c->save();

                        $new_transaction = new Transacoe;
                        $new_transaction-> num_conta = $conta;
                        $new_transaction-> valor = $valor;
                        $new_transaction-> tipo = 'saque';
                        $new_transaction-> moeda = $moeda;
                        $new_transaction-> hora_data = date("Y-m-d H:i:s");
                        $new_transaction->save();

                         //retorno
                        foreach ($contas as $c) {
                            $saldo_contas['moeda'] = $c->moeda;
                            $saldo_contas['saldo'] = $c->saldo;
                            $arraySaldos[] = $saldo_contas;
                        }

                        $dados['num_conta'] = $conta;
                        $dados['moeda'] = $moeda;
                        $dados['valor'] = $valor;
                        $dados['tipo'] = 'saque';
                        $dados['data'] = $new_transaction->hora_data;
                        $dados['novo_saldo'] = $arraySaldos;
                        $dados['sucessfull'] = TRUE;

                        return response()->json($dados);


                    }else{//não atingiu valor
                        $saldo_soma += $c->saldo/$cotacao;      //adiciona valor retirado
                        $c->saldo == 0.0;                       //zera a conta
                        $c->save();

                        $diferenca = $valor - $saldo_soma;
                    }

                }else{//moeda não é BRL
                    $cotacao = $this->cotacao_compra2($c->moeda);
                    $saldo_brl = $c->saldo*$cotacao;
                    
                    if($moeda != 'BRL'){
                        $cotacao = $this->cotacao_venda2($moeda);

                        if(($saldo_brl/$cotacao)>=$diferenca){//atingiu valor
                            //verifica quanto deve tirar
                            $cotacao = $this->cotacao_compra2($moeda);
                            $saldo_brl = $diferenca*$cotacao;
                            $cotacao = $this->cotacao_venda2($c->moeda);
                            $c->saldo -= $saldo_brl/$cotacao;
                            $c->save();
    
                            $new_transaction = new Transacoe;
                            $new_transaction-> num_conta = $conta;
                            $new_transaction-> valor = $valor;
                            $new_transaction-> tipo = 'saque';
                            $new_transaction-> moeda = $moeda;
                            $new_transaction-> hora_data = date("Y-m-d H:i:s");
                            $new_transaction->save();
    
                             //retorno
                            foreach ($contas as $c) {
                                $saldo_contas['moeda'] = $c->moeda;
                                $saldo_contas['saldo'] = $c->saldo;
                                $arraySaldos[] = $saldo_contas;
                            }

                            $dados['num_conta'] = $conta;
                            $dados['moeda'] = $moeda;
                            $dados['valor'] = $valor;
                            $dados['tipo'] = 'saque';
                            $dados['data'] = $new_transaction->hora_data;
                            $dados['novo_saldo'] = $arraySaldos;
                            $dados['sucessfull'] = TRUE;

                            return response()->json($dados);
    
    
                        }else{//não atingiu valor
                            $saldo_soma += ($saldo_brl/$cotacao);
                            $c->saldo == 0.0;
                            $c->save();
    
                            $diferenca = $valor - $saldo_soma;
                        }

                    }else{
                        if($saldo_brl>=$diferenca){//atingiu valor
                            //verifica quanto deve tirar
                            $cotacao = $this->cotacao_venda2($c->moeda);
                            $saldo_brl = $diferenca/$cotacao;
                            $c->saldo -= $saldo_brl;

                            $c->save();
    
                            $new_transaction = new Transacoe;
                            $new_transaction-> num_conta = $conta;
                            $new_transaction-> valor = $valor;
                            $new_transaction-> tipo = 'saque';
                            $new_transaction-> moeda = $moeda;
                            $new_transaction-> hora_data = date("Y-m-d H:i:s");
                            $new_transaction->save();
    
                             //retorno
                            foreach ($contas as $c) {
                                $saldo_contas['moeda'] = $c->moeda;
                                $saldo_contas['saldo'] = $c->saldo;
                                $arraySaldos[] = $saldo_contas;
                            }

                            $dados['num_conta'] = $conta;
                            $dados['moeda'] = $moeda;
                            $dados['valor'] = $valor;
                            $dados['tipo'] = 'saque';
                            $dados['data'] = $new_transaction->hora_data;
                            $dados['novo_saldo'] = $arraySaldos;
                            $dados['sucessfull'] = TRUE;

                            return response()->json($dados);

                        }else{//não atingiu valor
                            $saldo_soma += $saldo_brl;
                            $c->saldo == 0.0;
                            $c->save();
    
                            $diferenca = $valor - $saldo_soma;
                        }
                    }
                    

                    

                }
               
            }
        }   

        
        
    }

    public function extrato($conta, $data_inicial, $data_final){
        $transacoes = Transacoe::orderBy('hora_data')->where('num_conta',$conta)->get();

        //conta nao possui transacoes
        if(sizeof($transacoes)==0){
            return response()->json("Conta solicitada nao possui transacoes!");
        }

        $datainicial = new DateTime($data_inicial);
        $datafinal = new DateTime($data_final);
        
        $arrayTransacoes = array();
        $tipo_transacao = array(
            'num_conta' => '',
            'valor' => '',
            'tipo' => '',
            'moeda' => '',
            'data_hora' => ''
        );

        foreach ($transacoes as $t){
            $data_transacao = new DateTime(substr($t->hora_data, 0,11));
            
            if($data_transacao >= $datainicial and $data_transacao <= $datafinal){
                $tipo_transacao['num_conta'] = $t->num_conta;
                $tipo_transacao['valor'] = $t->valor;
                $tipo_transacao['tipo'] = $t->tipo;
                $tipo_transacao['moeda'] = $t->moeda;
                $tipo_transacao['data_hora'] = $t->hora_data;

                $arrayTransacoes[] = $tipo_transacao;
            }

        }

        return response()->json($arrayTransacoes);
    }

    public function calcula_saldo_total($conta,$tipo_moeda){
        
        //pegando as contas iguais a conta passada
        $contas = Conta::orderBy('num_conta')->where('num_conta',$conta)->get();
        
        $saldo = 0.0;
        $saldo_brl = 0.0;
        $cotacao = 0.0;
        
        //moeda solicitada é real
        if($tipo_moeda == 'BRL'){
            foreach ($contas as $c) {

                if($tipo_moeda == $c->moeda){//moeda igual solicitada
                    $saldo += $c->saldo;

                }else{//moeda diferente solicitada
                    $cotacao = $this->cotacao_venda2($c->moeda);
                    $saldo += ($c->saldo)*$cotacao;

                }
            }
        }else{//moeda solicitada não é real
            foreach ($contas as $c) {
                if($c->moeda == 'BRL'){//moeda da conta é real
                    $cotacao = $this->cotacao_venda2($tipo_moeda);
                    $saldo += ($c->saldo)/$cotacao;

                } elseif($c->moeda == $tipo_moeda) {//moeda igual solicitada
                    $saldo += $c->saldo;

                } else {//moeda diferente solicitada
                    $cotacao = $this->cotacao_compra2($c->moeda);
                    $saldo_brl = ($c->saldo)*$cotacao;
                    $cotacao = $this->cotacao_venda2($tipo_moeda);
                    $saldo += $saldo_brl/$cotacao;
                    
                }
            }
        }
        return $saldo;

    }

    public function cotacao_compra2($moeda){
        $top=1;
        $format='json';
        $select='cotacaoCompra,cotacaoVenda';
        
        $dia = date('d') - 1;
        $mes = date('m');
        $ano = date('Y');
        $ontem = mktime(0,0,0,$mes,$dia,$ano);

        $data = date('m-d-Y',$ontem);


        $cotacao = Unirest\Request::post("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoMoedaDia(moeda=@moeda,dataCotacao=@dataCotacao)?@moeda='$moeda'&@dataCotacao='$data'&$top=1&$format=json&$select=cotacaoCompra,cotacaoVenda");
        $decodedText = html_entity_decode($cotacao->raw_body);
        $myArray = json_decode($decodedText, true);
        
        $valores = $myArray["value"][0];
        $cotacao_compra = $valores["cotacaoCompra"];
        return $cotacao_compra;
    }

    public function cotacao_venda2($moeda){
        $top=1;
        $format='json';
        $select='cotacaoCompra,cotacaoVenda';
        
        $dia = date('d') - 1;
        $mes = date('m');
        $ano = date('Y');
        $ontem = mktime(0,0,0,$mes,$dia,$ano);

        $data = date('m-d-Y',$ontem);

    
        $cotacao = Unirest\Request::post("https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoMoedaDia(moeda=@moeda,dataCotacao=@dataCotacao)?@moeda='$moeda'&@dataCotacao='$data'&$top=1&$format=json&$select=cotacaoCompra,cotacaoVenda");
        $decodedText = html_entity_decode($cotacao->raw_body);
        $myArray = json_decode($decodedText, true);
        
        $valores = $myArray["value"][0];
        $cotacao_venda = $valores["cotacaoVenda"];
        return $cotacao_venda;
    }

    public function valida_moedas($moeda){
        $nome_moedas = Unirest\Request::post('https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/Moedas?$top=100&$format=json');
        
        $decodedText = html_entity_decode($nome_moedas->raw_body);
        $myArray = json_decode($decodedText, true);
        $moedas = $myArray["value"];

        foreach($moedas as $m){
            if ($m['simbolo']==$moeda){
                return true;
            }
        }
        if($moeda=='BRL'){
            return true;
        }
        return false;
    }

    public function retorna_moedas(){
        $nome_moedas = Unirest\Request::post('https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/Moedas?$top=100&$format=json');
        
        $decodedText = html_entity_decode($nome_moedas->raw_body);
        $myArray = json_decode($decodedText, true);
        $moedas = $myArray["value"];

        return $moedas;
    }


}
