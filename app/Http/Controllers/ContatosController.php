<?php

namespace App\Http\Controllers;
use Datetime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Auth;
use Intervention\Image\ImageManagerStatic as Image;
use App\Contatos as Contatos;
use App\Telefones as Telefones;
use App\Attachments as Attachs;
use App\Funcionarios as Funcionarios;
use App\Enderecos as Enderecos;
use App\Erp_configs as Configs;
use App\User as User;
use App\Combobox_texts as Comboboxes;
use Log;
use Carbon\Carbon;

class ContatosController extends BaseController
{
  public function __construct(){

    parent::__construct();
  }

  public function combobox($app){
  $combobox = Comboboxes::where('combobox_textable_type', 'App\\'.$app)->get();
  return $combobox;
  }

  public function search( Request $request)
  {

    if (!isset(Auth::user()->perms["contatos"]["leitura"]) or Auth::user()->perms["contatos"]["leitura"]!=1){
      return redirect()->action('HomeController@index')
                       ->withErrors([__('messages.perms.leitura')]);
    }
    Log::info('Busca de contatos usando -> "'.$request->busca.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    $contatos = Contatos::query();
    $contatos = $contatos->orderBy('nome', 'asc');
    if (!empty($request->busca)){
      $contatos = $contatos->orWhere(function ($query) use ($request) {
                                        $query->orWhere('nome', 'like', '%'.$request->busca.'%')
                                              ->orWhere('sobrenome', 'like', '%' .  $request->busca . '%')
                                              ->orWhere('endereco', 'like', '%' .  $request->busca . '%')
                                              ->orWhere('cpf', 'like', '%' .  $request->busca . '%')
                                              ->orWhere('cidade', 'like', '%' .  $request->busca . '%')
                                              ->orWhere('uf', 'like', '%' .  $request->busca . '%')
                                              ->orWhere('bairro', 'like', '%' .  $request->busca . '%')
                                              ->orWhere('cep', 'like', '%' .  $request->busca . '%');
                                      });
                }
    if ($request->apenas_filial=="TRUE"){
      $a = "Filial";
      $contatos = $contatos->whereHas('from', function ($query) use ($a){
                        $query->where('from_text', 'like', '%'.$a.'%');
                      })->get();
      $matriz = Contatos::where('id', '1')->paginate(100);
      $apenas_filial = TRUE;
      $contatos = $matriz->merge($contatos);
    } else {
      $contatos = $contatos->select('id', 'active', 'sociabilidade', 'nome', 'sobrenome', 'tipo', 'cpf', 'created_at');
      $contatos = $contatos->paginate(100);

      $apenas_filial = FALSE;

    }
    if ((is_array(Auth::user()->perms) and Auth::user()->perms["admin"]==1) and $request->deletados){
        $deletados = Contatos::onlyTrashed()->get();
    } else {
      $deletados = 0;
    }
    $empresas = contatos::where('tipo', '0')->count();
    $pessoas = contatos::where('tipo', '1')->count();
    $total= contatos::count();
    $comboboxes = comboboxes::where('combobox_textable_type', 'App\Relacionamento')->get();


    return $contatos;

    // return view('contatos.lista')->with('contatos', $contatos)->with('deletados', $deletados)
    // ->with('total', $total)
    // ->with('empresas', $empresas)
    // ->with('pessoas', $pessoas)
    // ->with('apenas_filial', $apenas_filial)
    // ->with('comboboxes', $comboboxes);
  }

  public function showId( $id )
  {
    if (!isset(Auth::user()->perms["contatos"]["edicao"]) or Auth::user()->perms["contatos"]["edicao"]!=1){
      return back()->withErrors([__('messages.perms.edicao')]);
    }
    $contato = contatos::with('user.trabalho', 'funcionario', 'enderecos', 'telefones', 'attachsToo')->find($id);

    return $contato;


    //Antes de VueJS
    // $comboboxes = comboboxes::where('combobox_textable_type', 'App\Relacionamento')->get();
    // $comboboxes_telefones = comboboxes::where('combobox_textable_type', 'App\Telefones')->get();
    // $field_codigo = Configs::where('field', 'field_codigo')->first();
    // $a = "Filial";
    // $is_filial = Contatos::whereHas('from', function ($query) use ($a){
    //                   $query->where('from_text', 'like', '%'.$a.'%')->where('to_id', '1');
    //                 })->find($id);
    // if ($is_filial==""){
    //   $is_filial=FALSE;
    // }
    // if($contato->funcionario){
    //   $is_funcionario = 1;
    //   $a = "Filial";
    //   $filiais = Contatos::whereHas('from', function ($query) use ($a){
    //                     $query->where('from_text', 'like', '%'.$a.'%');
    //                   })->get();
    //   return view('contatos.new')->with('contato', $contato)
    //                              ->with('comboboxes', $comboboxes)
    //                              ->with('comboboxes_telefones', $comboboxes_telefones)
    //                              ->with('field_codigo', $field_codigo)
    //                              ->with('is_funcionario', $is_funcionario)
    //                              ->with('filiais', $filiais)
    //                              ->with('is_filial', $is_filial);
    // }
    // Log::info('Detalhes de contato -> "'.$contato.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    //
    // return view('contatos.new')->with('contato', $contato)
    //                            ->with('comboboxes', $comboboxes)
    //                            ->with('field_codigo', $field_codigo)
    //                            ->with('comboboxes_telefones', $comboboxes_telefones)
    //                            ->with('is_filial', $is_filial);
  }

  public function novo( Request $request )
  {
    if (!isset(Auth::user()->perms["contatos"]["adicao"]) or Auth::user()->perms["contatos"]["adicao"]!=1){
      return back()->withErrors([__('messages.perms.adicao')]);
    }
    $this->validate($request, [
        'nome' => 'required|min:3|max:200',
        'sobrenome' => 'required|min:3|max:200',
        'cpf' => 'unique:contatos',
    ]);
    $contato = new Contatos;
    $contato->nome = $request->nome;
    $contato->cpf = $request->cpf;
    $contato->rg = $request->rg;
    $contato->sobrenome = $request->sobrenome;
    $contato->sociabilidade = $request->sociabilidade;
    $contato->tipo = $request->tipo;
    $contato->obs = $request->obs;
    $contato->cod_prefeitura = $request->cod_prefeitura;
    $contato->codigo = $request->codigo;
    $contato->nascimento = $request->nascimento;
    if ($request->active){
        $contato->active = "4";
    } else {
      $contato->active="1";
    }
    $contato->save();

    //Salvando enderecos vinculados
    foreach ($request->enderecos as $key => $end) {
      $endereco = new Enderecos;
      $endereco->contatos_id = $contato->id;
      $endereco->tipo = $end['tipo'];
      $endereco->cep = $end['cep'];
      $endereco->endereco = $end['endereco'];
      $endereco->numero = $end['numero'];
      $endereco->complemento = $end['complemento'];
      $endereco->bairro = $end['bairro'];
      $endereco->cidade = $end['cidade'];
      $endereco->uf = $end['uf'];
      $endereco->save();
    }

    //Salvando telefones vinculados
    foreach ($request->telefones as $key => $tel) {
      $telefone = new Telefones;
      $telefone->contatos_id = $contato->id;
      $telefone->tipo = $tel['tipo'];
      $telefone->numero = $tel['numero'];
      $telefone->contato = $tel['contato'];
      $telefone->setor = $tel['setor'];
      $telefone->ramal = $tel['ramal'];
      $telefone->save();
    }


    // Checa se é empresa, e se é filial está marcada
    if ($request->tipo=="0"){
      if ($request->tipo_filial=="1"){
        $data = [
          $request->from_id =>
          [
            'from_text' => 'Filial',
            'to_id' => 1,
            'to_text' => 'Matriz'
          ]
        ];
        $contato->from()->sync($data, true);
      }
    }

    // CASO FOR CADASTRO DE FUNCIONARIO
    if ($request->tipo=="2"){
      $data = [
        $request->filial =>
        [
          'from_text' => "Funcionario",
          'to_id' => 1,
          'to_text' => "Trabalho"
        ]
      ];
      $contato->from()->sync($data, false);

      $func = new Funcionarios;
      $func->contatos_id = $contato->id;
      $func->cargo = $request->funcionario['cargo'];
      $func->data_adm = $request->funcionario['data_adm'];
      $func->data_dem = $request->funcionario['data_dem'];
      $func->sal = $request->funcionario['sal'];
      $func->sal_real = $request->funcionario['sal_real'];
      $func->vt = $request->funcionario['vt'];
      $func->vt_percentual = $request->funcionario['vt_percentual'];
      $func->va = $request->funcionario['va'];
      $func->vr = $request->funcionario['vr'];
      $func->peri = $request->funcionario['peri'];
      $func->peri_percentual = $request->funcionario['peri_percentual'];
      $func->cart_trab_num = $request->funcionario['cart_num_trab'];
      $func->pis = $request->funcionario['pis'];
      $func->pis_banco = $request->funcionario['pis_banco'];
      $func->inss = $request->funcionario['inss'];
      $func->sal_inss = $request->funcionario['sal_inss'];
      $func->cnh = $request->funcionario['cnh'];
      $func->cnh_venc = $request->funcionario['cnh_venc'];
      $func->eleitor = $request->funcionario['eleitor'];
      $func->eleitor_sessao = $request->funcionario['eleitor_sessao'];
      $func->eleitor_zona = $request->funcionario['eleitor_zona'];
      $func->eleitor_exp = $request->funcionario['eleitor_data'];
      $func->reservista = $request->funcionario['reservista'];
      $func->rg_mae = $request->funcionario['rg_mae'];
      $func->rg_pai = $request->funcionario['rg_pai'];
      $func->save();

      $user = new User;
      $user->email = $request->user['email'];
      $user->password = bcrypt($request->user['password']);
      $user->ativo = $request->user['ativo'];
      if (isset($request->funcionario['filiais_id']) and $request->funcionario['filiais_id']!=""){
        $user->trabalho_id = $request->funcionario['filiais_id'];
      } else {
        $user->trabalho_id = 1;
      }
      $user->contatos_id = $contato->id;
      $user->perms='{"contatos":{"leitura":"1","adicao":"1","edicao":"0"}';
      $user->save();
    }

    Log::info('Busca de contatos usando -> "'.$request.'", resultando em -> "'.$contato.'" para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());

    return redirect()->action('ContatosController@show');
  }

  public function update( Request $request, $id )
  {
    if (!isset(Auth::user()->perms["contatos"]["edicao"]) or Auth::user()->perms["contatos"]["edicao"]!=1){
      return back()->withErrors([__('messages.perms.edicao')]);
    }
    $this->validate($request, [
        'nome' => 'required|max:50',
    ]);
    $contato = contatos::find($id);

    Log::info('Atualizar contato de -> "'.$contato.'" novo -> "'.$request.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());

    $contato->nome = $request->nome;
    $contato->cpf = $request->cpf;
    $contato->rg = $request->rg;
    $contato->sobrenome = $request->sobrenome;
    $contato->sociabilidade = $request->sociabilidade;
    $contato->tipo = $request->tipo;
    $contato->codigo = $request->codigo;
    $contato->nascimento = $request->nascimento;
    $contato->cod_prefeitura = $request->cod_prefeitura;
    $contato->obs = $request->obs;
    if ($request->active ){
        $contato->active = "4";
    } else {
      $contato->active="1";
    }
    $contato->save();

    //Atualizando e salvando enderecos vinculados
    foreach ($request->enderecos as $key => $end) {
      if (isset($end['id'])){
        $endereco = Enderecos::find($end['id']);
      } else {
        $endereco = new Enderecos;
        $endereco->contatos_id = $contato->id;
      }
      $endereco->tipo = $end['tipo'];
      $endereco->cep = $end['cep'];
      $endereco->endereco = $end['endereco'];
      $endereco->numero = $end['numero'];
      $endereco->complemento = $end['complemento'];
      $endereco->bairro = $end['bairro'];
      $endereco->cidade = $end['cidade'];
      $endereco->uf = $end['uf'];
      $endereco->save();
    }

    //Atualizando e salvando telefones vinculados
    foreach ($request->telefones as $key => $tel) {
      if (isset($tel['id'])){
        $telefone = Telefones::find($tel['id']);
      } else {
        $telefone = new Telefones;
        $telefone->contatos_id = $contato->id;
      }
      $telefone->tipo = $tel['tipo'];
      $telefone->numero = $tel['numero'];
      $telefone->contato = $tel['contato'];
      $telefone->setor = $tel['setor'];
      $telefone->ramal = $tel['ramal'];
      $telefone->save();
    }

    // CASO FOR CADASTRO DE FUNCIONARIO
    if ($request->tipo=='2'){
      $data = [
        $request->contatos_id =>
        [
          'from_text' => "Funcionario",
          'to_id' => 1,
          'to_text' => "Trabalho"
        ]
      ];
      $contato->from()->sync($data, true);

      $func = Funcionarios::where('contatos_id', $id)->first();
      $func->cargo = $request->funcionario['cargo'];
      $func->data_adm = $request->funcionario['data_adm'];
      $func->data_dem = $request->funcionario['data_dem'];
      $func->sal = $request->funcionario['sal'];
      $func->sal_real = $request->funcionario['sal_real'];
      $func->vt = $request->funcionario['vt'];
      $func->vt_percentual = $request->funcionario['vt_percentual'];
      $func->va = $request->funcionario['va'];
      $func->vr = $request->funcionario['vr'];
      $func->peri = $request->funcionario['peri'];
      $func->peri_percentual = $request->funcionario['peri_percentual'];
      $func->cart_trab_num = $request->funcionario['cart_trab_num'];
      $func->pis = $request->funcionario['pis'];
      $func->pis_banco = $request->funcionario['pis_banco'];
      $func->inss = $request->funcionario['inss'];
      $func->sal_inss = $request->funcionario['sal_inss'];
      $func->cnh = $request->funcionario['cnh'];
      $func->cnh_venc = $request->funcionario['cnh_venc'];
      $func->eleitor = $request->funcionario['eleitor'];
      $func->eleitor_sessao = $request->funcionario['eleitor_sessao'];
      $func->eleitor_zona = $request->funcionario['eleitor_zona'];
      $func->eleitor_exp = $request->funcionario['eleitor_exp'];
      $func->reservista = $request->funcionario['reservista'];
      $func->rg_mae = $request->funcionario['rg_mae'];
      $func->rg_pai = $request->funcionario['rg_pai'];
      $func->save();

      $user = User::where('contatos_id', $id)->first();
      $user->email = $request->user['email'];
      $user->password = bcrypt($request->user['password']);
      $user->ativo = $request->user['ativo'];
      $user->trabalho_id = $request->funcionario['trabalho_id'];
      $user->save();
    }

    // $combobox = Comboboxes::where('text', $request->relacao)->first();
    // #return $combobox;
    // if ($combobox){
    //
    //   $data = [
    //     $request->from_id =>
    //     [
    //       'from_text' => $combobox->text,
    //       'to_id' => 1,
    //       'to_text' => $combobox->value
    //     ]
    //   ];
    //   $contato->from()->sync($data, false);
    // }

    $contato->save();
    return $user;
    return redirect()->action('ContatosController@show');
  }


  public function filiais_busca(request $request)
  {
    Log::info('Selecionar de filais para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    if (!isset(Auth::user()->perms["contatos"]["leitura"]) or Auth::user()->perms["contatos"]["leitura"]!=1){
      return response()->json([__('messages.perms.leitura')], 403);
    }
    $a ="Filial";
    $contatos = contatos::orderBy('nome', 'asc')->whereHas('from', function ($query) use ($a){
                      $query->where('from_text', 'like', '%'.$a.'%');
                    })->paginate(15);
    $matriz = contatos::find(1);

    $contatos->push($matriz);
    return $contatos;

    // return view('contatos.selecionar')
    //             ->with('contatos', $contatos)
    //             ->with('matriz', $matriz)
    //             ->with('apenas_filial', $apenas_filial);
  }










  public function selecionar()
  {
    Log::info('Selecionar de contatos para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    if (!isset(Auth::user()->perms["contatos"]["leitura"]) or Auth::user()->perms["contatos"]["leitura"]!=1){
      return response()->json([__('messages.perms.leitura')], 403);
    }
    $apenas_filial = FALSE;
    $contatos = contatos::orderBy('nome', 'asc')->paginate(15);
    return view('contatos.selecionar')
                ->with('apenas_filial', $apenas_filial)
                ->with('contatos', $contatos);
  }

  public function selecionar_busca(request $request)
  {
    #return $request;
    Log::info('Selecionar de contatos para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    if (!isset(Auth::user()->perms["contatos"]["leitura"]) or Auth::user()->perms["contatos"]["leitura"]!=1){
      return response()->json([__('messages.perms.leitura')], 403);
    }
    $contatos = Contatos::query();
    if (!empty($request->busca)){
      $contatos = $contatos->orWhere('nome', 'like', '%' .  $request->busca . '%');
      $contatos = $contatos->orWhere('sobrenome', 'like', '%' .  $request->busca . '%');
      $contatos = $contatos->orWhere('endereco', 'like', '%' .  $request->busca . '%');
      $contatos = $contatos->orWhere('cpf', 'like', '%' .  $request->busca . '%');
      $contatos = $contatos->orWhere('cidade', 'like', '%' .  $request->busca . '%');
      $contatos = $contatos->orWhere('uf', 'like', '%' .  $request->busca . '%');
      $contatos = $contatos->orWhere('bairro', 'like', '%' .  $request->busca . '%');
      $contatos = $contatos->orWhere('cep', 'like', '%' .  $request->busca . '%');
    }
    if ($request->apenas_filial==TRUE){
      $a = "Filial";
      $contato = $contatos->whereHas('from', function ($query) use ($a){
                        $query->where('from_text', 'like', '%'.$a.'%');
                      })->paginate(15);
    } else {
      $contatos = $contatos->orderBy('nome', 'asc')->get();
    }
    return view('contatos.selecionarbusca')
                ->with('contatos', $contatos)
                ->with('apenas_filial', $apenas_filial);
  }
  public function selecionar_novo()
  {
    Log::info('Novo contatos em modal para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    if (!isset(Auth::user()->perms["contatos"]["leitura"]) or Auth::user()->perms["contatos"]["leitura"]!=1){
      return response()->json([__('messages.perms.leitura')], 403);
    }
    $comboboxes = comboboxes::where('combobox_textable_type', 'App\Relacionamento')->get();
    $comboboxes_telefones = comboboxes::where('combobox_textable_type', 'App\Telefones')->get();
    $field_codigo = Configs::where('field', 'field_codigo')->first();

    return view('contatos.parte_novo')->with('comboboxes', $comboboxes)
                                ->with('comboboxes_telefones', $comboboxes_telefones)
                                ->with('field_codigo', $field_codigo);
  }
  public function selecionar_salva(request $request)
  {
    Log::info('Saçvando contatos em modal para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    if (!isset(Auth::user()->perms["contatos"]["adicao"]) or Auth::user()->perms["contatos"]["adicao"]!=1){
      return response()->json([__('messages.perms.adicao')], 403);
    }
    $this->validate($request, [
        'nome' => 'required|max:50',
        'cpf'  => 'unique:contatos'
    ]);
    $contato = new Contatos;
    $contato->nome = $request->nome;
    $contato->cpf = $request->cpf;
    $contato->rg = $request->rg;
    $contato->sobrenome = $request->sobrenome;
    $contato->endereco = $request->endereco;
    $contato->numero = $request->numero;
    $contato->complemento = $request->complemento;
    $contato->bairro = $request->bairro;
    $contato->uf = $request->uf;
    $contato->cidade = $request->cidade;
    $contato->cep = $request->cep;
    $contato->sociabilidade = $request->sociabilidade;
    $contato->tipo = $request->tipo;
    $contato->obs = $request->obs;
    $contato->cod_prefeitura = $request->cod_prefeitura;
    $contato->codigo = $request->codigo;
    $contato->nascimento = $request->nascimento;
    if ($request->active){
        $contato->active = "4";
    } else {
      $contato->active="1";
    }
    $contato->save();
    if ($request->tipo=="0"){
      if ($request->tipo_filial=="1"){
        $data = [
          $request->from_id =>
          [
            'from_text' => 'Filial',
            'to_id' => 1,
            'to_text' => 'Matriz'
          ]
        ];
        $contato->from()->sync($data, true);
      }
    }
    if (isset($request->tipo_tel[0])){
      foreach ($request->tipo_tel as $key => $tipo) {
        $telefone = new Telefones;
        $telefone->contatos_id = $contato->id;
        $telefone->tipo = $request->tipo_tel[$key];
        $telefone->numero = $request->numero_tel[$key];
        $telefone->contato = $request->contato_tel[$key];
        $telefone->setor = $request->setor_tel[$key];
        $telefone->ramal = $request->ramal_tel[$key];
        $telefone->save();
      }
    }
    if (isset($request->cep_end [0])){
      foreach ($request->cep_end as $key => $cep) {
        $endereco = new Enderecos;
        $endereco->tipo = $request->tipo_end[$key];
        $endereco->cep = $request->cep_end[$key];
        $endereco->endereco = $request->end_end[$key];
        $endereco->numero = $request->numero_end[$key];
        $endereco->complemento = $request->complemento_end[$key];
        $endereco->bairro = $request->bairro_end[$key];
        $endereco->cidade = $request->cidade_end[$key];
        $endereco->uf = $request->uf_end[$key];
        $endereco->contatos_id = $contato->id;
        $endereco->save();
      }
    }
    if ($request->is_funcionario!="1"){
      $combobox = Comboboxes::where('text', $request->relacao)->first();
      if ($combobox){
        $data = [
          $request->from_id =>
          [
            'from_text' => $combobox->text,
            'to_id' => 1,
            'to_text' => $combobox->value
          ]
        ];
        $contato->from()->sync($data, false);
      }
    }
    return response('', 200);
  }
  public function show()
  {
    Log::info('Lista de contatos para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    if (!isset(Auth::user()->perms["contatos"]["leitura"]) or Auth::user()->perms["contatos"]["leitura"]!=1){
      return redirect()->action('HomeController@index')
                       ->withErrors([__('messages.perms.leitura')]);
    }
    $contatos = contatos::orderBy('nome', 'asc')->paginate(100);
    return $contatos;

    $total= contatos::count();
    $empresas = contatos::where('tipo', '0')->count();
    $pessoas = contatos::where('tipo', '1')->count();
    $deletados = 0;
    $comboboxes = comboboxes::where('combobox_textable_type', 'App\Relacionamento')->get();
    return view('contatos.list')
                ->with('contatos', $contatos)
                ->with('deletados', $deletados)
                ->with('total', $total)
                ->with('empresas', $empresas)
                ->with('pessoas', $pessoas)
                ->with('comboboxes', $comboboxes);
  }


  public function consulta_cpf(request $request)
  {
    Log::info('Selecionar de contatos para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    if (!isset(Auth::user()->perms["contatos"]["leitura"]) or Auth::user()->perms["contatos"]["leitura"]!=1){
      return response()->json([__('messages.perms.leitura')], 403);
    }
    $contato = contatos::where('cpf', 'like', $request->cpf)->get();
    #return $contato;
    if($contato=="[]"){
      return response()->json([__('messages.sucessos.cpf')], 404);
    } else {
      return response()->json([__('messages.erros.cpf')], 302);
    }
  }
  public function detalhes($id){
    if (!isset(Auth::user()->perms["contatos"]["leitura"]) or Auth::user()->perms["contatos"]["leitura"]!=1){
      return response()->json([__('messages.perms.leitura')], 403);
    }
    $contato = contatos::with('user.trabalho', 'funcionario', 'enderecos', 'telefones', 'attachsToo')->find($id);
    $comboboxes_telefones = comboboxes::where('combobox_textable_type', 'App\Telefones')->get();

    $resposta=[];
    $resposta['contato'] = $contato;
    $resposta['comboboxes_telefones'] = $comboboxes_telefones;
    return $resposta;

    return view('contatos.detalhes')->with('contato', $contato)->with('comboboxes_telefones', $comboboxes_telefones);
  }

  public function funcionarios_novo()
  {
    Log::info('Criando novo contato, para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    if (!isset(Auth::user()->perms["contatos"]["adicao"]) or Auth::user()->perms["contatos"]["adicao"]!=1){
      return response()->json([__('messages.perms.adicao')], 403);
    }
    $comboboxes = comboboxes::where('combobox_textable_type', 'App\Relacionamento')->get();
    $comboboxes_telefones = comboboxes::where('combobox_textable_type', 'App\Telefones')->get();
    $a = "Filial";
    $filiais = Contatos::whereHas('from', function ($query) use ($a){
                      $query->where('from_text', 'like', '%'.$a.'%');
                    })->get();
    $is_funcionario = 1;
    $field_codigo = Configs::where('field', 'field_codigo')->first();
    return view('contatos.new')->with('is_funcionario', $is_funcionario)
                               ->with('filiais', $filiais)
                               ->with('field_codigo', $field_codigo)
                               ->with('comboboxes', $comboboxes)
                               ->with('comboboxes_telefones', $comboboxes_telefones);
  }

  public function showNovo()
  {
    Log::info('Criando novo contato, para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    if (!isset(Auth::user()->perms["contatos"]["adicao"]) or Auth::user()->perms["contatos"]["adicao"]!=1){
      return back()->withErrors([__('messages.perms.adicao')]);
    }
    $comboboxes = comboboxes::where('combobox_textable_type', 'App\Relacionamento')->get();
    $comboboxes_telefones = comboboxes::where('combobox_textable_type', 'App\Telefones')->get();
    $field_codigo = Configs::where('field', 'field_codigo')->first();
    return view('contatos.new')->with('comboboxes', $comboboxes)
                                ->with('comboboxes_telefones', $comboboxes_telefones)
                                ->with('field_codigo', $field_codigo);
  }





  public function telefones_delete( $id, $id_telefone )
  {
    if (!isset(Auth::user()->perms["contatos"]["edicao"]) or Auth::user()->perms["contatos"]["edicao"]!=1){
      return response()->json([__('messages.perms.edicao')], 403);
    }
    $telefone = Telefones::find($id_telefone);

    Log::info('Deletando telefone para contato(id'.$id.') refente -> "'.$telefone.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());

    $telefone->delete();

    return redirect()->action('ContatosController@show');
  }

  public function enderecos_delete( $id_endereco )
  {
    if (!isset(Auth::user()->perms["contatos"]["edicao"]) or Auth::user()->perms["contatos"]["edicao"]!=1){
      return response()->json([__('messages.perms.edicao')], 403);
    }

    $endereco = Enderecos::find($id_endereco);
    $endereco->delete();
    return 302;
  }

  public function relacoes( $id)
  {
    $contato = Contatos::find($id);
    Log::info('Ver relacionamentos do contato -> "'.$contato.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    return view('contatos.relacoes')->with('contato', $contato);
  }

  public function relacoes_novo( $id)
  {
    $contato = Contatos::find($id);

    Log::info('Novo relacionamento para contato -> "'.$contato.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());

    $contatos = Contatos::paginate(15);
    $comboboxes = comboboxes::where('combobox_textable_type', 'App\Relacionamento')->get();
    return view('contatos.relacoesnovo')->with('contato', $contato)->with('contatos', $contatos)->with('comboboxes', $comboboxes);
  }

  public function relacoes_busca( Request $request, $id)
  {
    $contato = Contatos::find($id);
    if (!empty($request->busca)){
      $contatos = Contatos::where('nome', 'like', '%' .  $request->busca . '%')
                            ->orWhere('sobrenome', 'like', '%' .  $request->busca . '%')
                            ->paginate(15);
    } else {
      $contatos = Contatos::paginate(15);
    }
    Log::info('Novo relacionamento com busca -> "'.$request->busca.'", para contato -> "'.$contato.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());

    return view('contatos.relacoesnovo')->with('contato', $contato)->with('contatos', $contatos);
  }

  public function relacoes_post( Request $request, $id)
  {
    $combobox = comboboxes::find($request->combobox_id);
    $contato = contatos::Find($id);
    $data = [
      $id =>
      [
        'from_text' => $combobox->text,
        'to_id' => $request->to_id,
        'to_text' => $combobox->value
      ]
    ];
    $contato->from()->sync($data, false);

    Log::info('Salvando relacionamento do contato -> "'.$contato.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());

    return redirect()->action('ContatosController@show');
  }


  public function relacoes_delete( $id, $relacao_id){
    $relation = DB::table('contatos_pivot')->where('id', '=', $relacao_id)->delete();

    $contato = Contatos::find($id);

    Log::info('Deletar relacionamento do contato -> "'.$contato.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());

    return redirect()->action('ContatosController@show');
  }
  public function delete($id){
    if ($id!=1){
      $contato = Contatos::withTrashed()->find($id);
      if ($contato->trashed()) {
        $contato->restore();
        Log::info('Restaurando contato -> "'.$contato.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
      } else {
        Log::info('Deletando contato -> "'.$contato.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
        $contato->delete();
      }
    }
    return redirect()->action('ContatosController@show');
  }
  public function attachs_detalhes($id){
    if (!isset(Auth::user()->perms["contatos"]["leitura"]) or Auth::user()->perms["contatos"]["leitura"]!=1){
      return response()->json([__('messages.perms.leitura')], 403);
    }
    $contato = Contatos::find($id);
    return view('contatos.attachs')->with('contato', $contato)->with('contato_id', $id);

  }
  public function attach(request $request, $id){
    $attach = new Attachs;
    $attach->attachmentable_id = $id;
    $attach->attachmentable_type = "App\Contatos";
    $attach->name = $request->name;
    $attach->path = $request->file->store('public');
    $attach->contatos_id = $id;
    $attach->save();

    $path = storage_path() . '/' .'app/'. $attach->path;
    $file = Image::make($path);
    Log::info('Anexando arquivo para contato -> "'.$id.'", anexo -> "'.$attach.'", para -> ID:'.Auth::user()->contato->id.' nome:'.Auth::user()->contato->nome.' Usuario ID:'.Auth::user()->id.' ip:'.request()->ip());
    return redirect()->action('ContatosController@show');
  }
}
