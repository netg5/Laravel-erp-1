@extends('main')
@section('content')
  <div class="row">
    <div class="col-md-11">
      <form method="POST" action="{{ url('/contatos') }}/{{$contato->id}}/telefones/">
        <div class="form-group">
        {{ csrf_field() }}
        <input type="hidden" name="id" value="">
        <div class="panel panel-default">
          <div class="panel-heading"><i class="fa fa-users fa-1x"></i> Adicionar telefone para {{$contato->nome}}</div>
          <div class="panel-body">
            <div class="row text-right">
              <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-success">Salvar</button>
              </div>
            </div>
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label for="text">Tipo</label>
                  <input type="text" class="form-control" value="" name="tipo" id="tipo" placeholder="">
                </div>
              </div>
              <div class="col-md-9">
                <div class="form-group">
                  <label for="text">Numero</label>
                  <input type="text" class="form-control" value="" name="numero" id="numero" placeholder="">
                </div>
              </div>
            </div>
        </div>
      </form>
    </div>
  </div>
@endsection
