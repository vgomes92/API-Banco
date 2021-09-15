<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Models\Conta;
use Unirest;

class ContasController extends Controller
{
    public function index()
    {
        $msg = array(
            'msg' => 'informe a conta!'
        );
        $contas = Conta::orderBy('num_conta')->get();
        return response()->json($msg);
    }


    public function show($conta = null, $tipo_moeda = null)
        //Saldo em todas as moedas
    {   if(!$tipo_moeda){
            $contas = Conta::orderBy('num_conta')->where('num_conta',$conta)->get();

            //conta nao existe
            if(sizeof($contas)==0){
                $msg = array(
                    'msg' => 'Conta solicitada nao existe!'
                );
                return response()->json($msg);
            }

            $array_Saldos = array();
            $saldo_moeda = array(
                'saldo' => '',
                'moeda' => ''
            );

            foreach ($contas as $c) {
                $saldo_moeda['moeda'] = $c->moeda;
                $saldo_moeda['saldo'] = $c->saldo;
                $array_Saldos[] = $saldo_moeda;
            }

            $dados = array(
                'conta' => '',
                'data' => '',
                'saldo_detalhado' =>''
            );

            $dados['conta']=$conta;
            $dados['data']= date('d-m-Y H:i');
            $dados['saldo_detalhado']=$array_Saldos;

            return response()->json($dados);
        }

        //valida moeda
        if(!$this->valida_moedas($tipo_moeda)){
            $dados = array(
                'msg' => 'Moeda solicitada nao existe!',
                'moedas validas' => ''
            );

            $dados['moedas validas']=$this->retorna_moedas();

            return response()->json($dados);
        }

        //saldo moeda solicitada
        $contas = Conta::orderBy('num_conta')->where('num_conta',$conta)->get();

        //conta nao existe
        if(sizeof($contas)==0){
            $msg = array(
                'msg' => 'Conta solicitada nao existe!'
            );
            return response()->json($msg);
        }
        
        $saldo = 0.0;
        $saldo_brl = 0.0;
        $cotacao = 0.0;
        
        $arraySaldos = array();
        $valor_moeda = array(
            'saldo' => '',
            'moeda_conversao' => '',
            'taxa_venda' => '',
            'taxa_compra' => ''
        );

        //moeda solicitada é real
        if($tipo_moeda == 'BRL'){
            foreach ($contas as $c) {

                

                if($tipo_moeda == $c->moeda){//moeda igual solicitada
                    $saldo += $c->saldo;

                    $valor_moeda['saldo'] = number_format((float)$c->saldo, 2, '.', '');
                    $valor_moeda['moeda_conversao'] = $c->moeda;
                    $valor_moeda['taxa_venda'] = '';
                    $valor_moeda['taxa_compra'] = '';
                    $arraySaldos[] = $valor_moeda;
                }else{//moeda diferente solicitada
                    $cotacao = $this->cotacao_venda($c->moeda);
                    $saldo += ($c->saldo)*$cotacao;

                    $valor_moeda['saldo'] = number_format((float)($c->saldo), 2, '.', '');
                    $valor_moeda['moeda_conversao'] = $c->moeda;
                    $valor_moeda['taxa_venda'] = $cotacao;
                    $valor_moeda['taxa_compra'] = '';
                    $arraySaldos[] = $valor_moeda;
                }
            }
        }else{//moeda solicitada não é real
            foreach ($contas as $c) {
                if($c->moeda == 'BRL'){//moeda da conta é real
                    $cotacao = $this->cotacao_venda($tipo_moeda);
                    $saldo += ($c->saldo)/$cotacao;
                    
                    $valor_moeda['saldo'] = number_format((float)($c->saldo), 2, '.', '');
                    $valor_moeda['moeda_conversao'] = $c->moeda;
                    $valor_moeda['taxa_venda'] = $cotacao;
                    $valor_moeda['taxa_compra'] = '';
                    $arraySaldos[] = $valor_moeda;

                } elseif($c->moeda == $tipo_moeda) {//moeda igual solicitada
                    $saldo += $c->saldo;

                    $valor_moeda['saldo'] = number_format((float)$c->saldo, 2, '.', '');
                    $valor_moeda['moeda_conversao'] = $c->moeda;
                    $valor_moeda['taxa_venda'] = '';
                    $valor_moeda['taxa_compra'] = '';
                    $arraySaldos[] = $valor_moeda;

                } else {//moeda diferente solicitada
                    $cotacao = $this->cotacao_compra($c->moeda);
                    $saldo_brl = ($c->saldo)*$cotacao;
                    $valor_moeda['taxa_compra'] = $cotacao;
                    $cotacao = $this->cotacao_venda($tipo_moeda);
                    $saldo += $saldo_brl/$cotacao;
                    $valor_moeda['taxa_venda'] = $cotacao;

                    $valor_moeda['saldo'] = number_format((float)($saldo_brl), 2, '.', '');
                    $valor_moeda['moeda_conversao'] = $c->moeda;
                    $arraySaldos[] = $valor_moeda;
                    
                }
            }
        }

        $dados = array(
            'saldo' => '',
            'moeda' => '',
            'data' => '',
            'saldo_detalhado' =>''
        );

        $dados['saldo'] = number_format((float)$saldo, 2, '.', '');
        $dados['moeda'] = $tipo_moeda;
        $dados['data'] = date('d-m-Y H:i');
        $dados['saldo_detalhado'] = $arraySaldos;

        return response()->json($dados);
    }

    public function cotacao_compra($moeda){
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

    public function cotacao_venda($moeda){
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
