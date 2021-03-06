@extends('main')
@section('content')
  <div class="row">
    <div class="col-md-12">
      <div class="panel panel-default">
        <div class="panel-heading ">
          <i class="fa fa-money fa-1x"></i> Nova movimentação da filial : <span class="label label-info">{{ Auth::user()->trabalho->nome }}</span>
        </div>
        <form method="POST" action="{{ url('/novo/caixa') }}">
          <div class="panel-body">
            {{ csrf_field() }}
            <input type="hidden" name="caixa_id" value="{{ Auth::user()->trabalho->id }}">
            <div class="row text-right">
              <div class="col-sm-offset-2 col-sm-10">
                <a class="btn btn-warning" href="{{ url('lista/caixa')}}" ><i class="fa fa-money"></i> Voltar a Lista</a>
                <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Salvar</button>
              </div>
            </div>
            <div class="row">
              @if (isset($caixa))
              <div class="col-md-3">
                <div class="form-group">
                    <label for="contato">Caixa numero:</label>
                    <input type="text" class="form-control" value="{{$caixa->id}} (na {{$caixa->filial->nome}})" id="id" disabled>
                    <input type="hidden" class="form-control" name="caixa" value="{{$caixa->id}}" id="id" disabled>
                </div>
              </div>
            @endif
              <div class="col-md-3">
                <div class="form-group">
                  <label for="tipo">Tipo de movimentação:</label>
                  <select class="form-control" id="tipo" name="tipo" onchange="abrirCaixa()">
                    <option value=""> - Escolha uma opção - </option>
                    <option value="0" selected>Entrada de valor</option>
                    <option value="1" >Saida de valor</option>
                    <option value="99" >Abrir o caixa</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3" id="a">
                <div class="form-group">
                  <label for="tipo">Estado da movimentação:</label>
                  <select class="form-control" id="estado" name="estado">
                    <option value=""> - Escolha uma opção - </option>
                    <option value="0" selected>Esperando retorno</option>
                    <option value="1" >Já prestou contas</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="valor" id="valor_label">Valor:</label>
                  <div class="input-group">
                    <span class="input-group-addon" id="basic-addon1">R$</span>
                    <input type="text" class="form-control" name="valor" id="valor" placeholder="Valor de custo">
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3" id="b">
                <div class="form-group">
                  <label for="contato">Descriçao:</label>
                  <select class="form-control" id="nome" name="nome">
                    <option value="" selected> - Escolha uma opção - </option>
                    @foreach($comboboxes as $key => $combobox)
                      <option value="{{$combobox->value}}">{{$combobox->text}}</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12" id="c">
                <div class="form-group">
                  <label for="contato">Observações:</label>
                  <textarea name="obs"></textarea>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
<script language="javascript">
function abrirCaixa() {
  var a = $("#tipo").val();
  if ( a == "99"){
    $("#a").hide();
    $("#b").hide();
    $("#c").hide();
    $("#valor_label").text('Valor inicial:');
  } else {
    $("#a").show();
    $("#b").show();
    $("#c").show();
    $("#valor_label").text('Valor:');
  }
}
</script>
@endsection
