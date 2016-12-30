<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
Use App\Vendas as Vendas;
Use App\Estoque as Estoque;
Use App\Contatos as Contatos;
use App\Caixas as Caixas;
use App\Contas as Contas;
use App\Est_movimentacoes as Movs;
use Carbon\Carbon;
use Auth;

class VendasController extends Controller
{
    public function index(){
      $vendas = Vendas::paginate(15);
      return view('vendas.index')->with('vendas', $vendas);
    }
    public function novo(){
      $contatos = contatos::paginate(30);

      return view('vendas.contatos')->with('contatos', $contatos);
    }
    public function produtos($id){
      $estoques = estoque::all();
      if ($id==0){
        $contato = "0";
      } else {
        $contato = contatos::find($id);
      }
      return view('vendas.novo')->with('estoques', $estoques)->with('contato', $contato);
    }

    public function confirmar($id, request $request){
      if ($id==0){
        $contato = "0";
      } else {
        $contato = contatos::find($id);
      }

      $total=0;
      foreach ($request->estoque as $key => $estoque) {
        $produtos[$key] = Estoque::find($estoque);
        $produtos[$key]->quantidade = $request->quantidade[$key];
        $produtos[$key]->valor_custo = $request->quantidade[$key]*$produtos[$key]->valor_custo;
        $total = $total+$produtos[$key]->valor_custo;
      }
      return view('vendas.produtos')->with('produtos', $produtos)->with('contato', $contato)->with('total', $total);
    }

    public function salvar($id, request $request){
      if ($request->forma=="0"){
        #
        #PRECISA SER TORNADO CLASSE!!!! TO DO!
        #
        $abertura = Caixas::whereRaw('date(created_at) = ?', [Carbon::today()])
                        ->orderBy('created_at', 'aaaa')
                        ->where('filial_id', Auth::user()->trabalho_id)
                        ->where('tipo', '0')
                        ->first();
        $fechamento = Caixas::whereRaw('date(created_at) = ?', [Carbon::today()])
                        ->orderBy('created_at', 'aaaa')
                        ->where('filial_id', Auth::user()->trabalho_id)
                        ->where('tipo', '1')
                        ->first();

        if (is_null($abertura)){
          $messages = "Caixa ainda não aberto!";
          return redirect()->action('CaixasController@new_a')->withErrors($messages);
        }
        if (!is_null($abertura)){
          if (!is_null($fechamento)){
            if (strtotime($abertura->created_at) < strtotime($fechamento->created_at)){
              $messages = "Caixa já fechado!";
              return redirect()->action('CaixasController@new_a')->withErrors($messages);
            }
          }
        }
      }
      $venda = new Vendas;
      $venda->funcionario_id = Auth::user()->contato->id;
      $venda->comprador_id = $id;

      $valor_total = 0;
      foreach ($request->quantidade as $key => $qtd) {
        $estoque = Estoque::find($request->id[$key]);

        $valor_total = ($estoque->valor_custo * $qtd)+$valor_total;

        $estoque->quantidade = $estoque->quantidade - $qtd;
        $estoque->save();
      }

      $venda->valor = $valor_total;
      $venda->save();

      foreach ($request->quantidade as $key => $qtd) {
        $mov = new Movs;
        $mov->estoque_id = $request->id[$key];
        $mov->vendas_id = $venda->id;
        $mov->quantidade = $request->quantidade[$key];
        $mov->save();
      }

      $current = Carbon::now();
      if ($request->forma=="0"){
        $movimentacao = new Caixas;
        $movimentacao->filial_id = Auth::user()->trabalho->id;
        $movimentacao->funcionario_id = Auth::user()->contato->id;
        $movimentacao->vendas_id=$venda->id;
        $movimentacao->pag = "0";
        $movimentacao->tipo = "2";
        $movimentacao->valor = $valor_total;
        $movimentacao->forma="0";
        $movimentacao->save();
      } elseif ($request->forma=="1") {
        $conta = new Contas;
        $conta->contatos_id = $id;
        $conta->estado = "1";
        $conta->tipo = "1";
        $conta->nome = "Venda N:".$venda->id;
        $conta->descricao = "Referente a venda numero ".$venda->id." realizada em ".$venda->created_at;
        $conta->vencimento = $current->addDays(3);
        $conta->valor = $valor_total;
        $conta->save();
      } elseif ($request->forma=="2") {
        $conta = new Contas;
        $conta->contatos_id = $id;
        $conta->estado = "0";
        $conta->tipo = "1";
        $conta->nome = "Venda N:".$venda->id;
        $conta->descricao = "Referente a venda numero ".$venda->id." realizada em ".$venda->created_at;
        $conta->valor = $valor_total;
        $conta->vencimento = $current->addDays(30);
        $conta->save();
        $conta->referente = $conta->id;
        $conta->save();
        if ($request->parcelamento=="1" or $request->parcelamento=="2") {
          $subconta = new Contas;
          $subconta->referente = $conta->id;
          $subconta->contatos_id = $id;
          $subconta->estado = "0";
          $subconta->tipo = "1";
          $subconta->nome = "Parcela da venda N:".$venda->id;
          $subconta->descricao = "Referente a venda numero ".$venda->id." realizada em ".$venda->created_at;
          $subconta->valor = $valor_total;
          $subconta->vencimento = $current->addDays(60);
          $subconta->save();
        } if ($request->parcelamento=="2") {
          $subconta2 = new Contas;
          $subconta2->referente = $conta->id;
          $subconta2->contatos_id = $id;
          $subconta2->estado = "0";
          $subconta2->tipo = "1";
          $subconta2->nome = "Parcela da venda N:".$venda->id;
          $subconta2->descricao = "Referente a venda numero ".$venda->id." realizada em ".$venda->created_at;
          $subconta2->valor = $valor_total;
          $subconta2->vencimento = $current->addDays(60);
          $subconta2->save();
        }
      }


      return redirect()->action('VendasController@index');
    }

}
