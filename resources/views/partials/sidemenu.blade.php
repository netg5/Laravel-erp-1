<div class="brand">WebGS - ERP</div>
<i class="fa fa-bars fa-2x toggle-btn" data-toggle="collapse" data-target="#menu-content"></i>

    <div class="menu-list">

        <ul id="menu-content" class="menu-content collapse out">
            <li class="{{{ Request::path()=='/' ? "active" : "" }}}">
              <a href="{{ url('/') }}">
                <i class="fa fa-dashboard fa-lg"></i> Painel
              </a>
            </li>

            <li  data-toggle="collapse" data-target="#cadastros" class="{{{ Request::is('novo*') ? "active" : "collapsed" }}}" aria-expanded="">
              <a href="#"><i class="fa fa-file-text fa-lg"></i> Cadastros<span class="arrow"></span></a>
            </li>
            <ul class="sub-menu collapse {{{ Request::is('novo*') ? "in" : "" }}}" aria-expanded="{{{ Request::is('novo*') ? "true" : "false" }}}" id="cadastros">
                <li class="{{{ Request::path()=='novo/contatos' ? "active" : "" }}}"><a href="{{ url('novo/contatos') }}"><i class="fa fa-user"></i> Contato</a></li>
                <li class="{{{ Request::path()=='novo/atendimentos' ? "active" : "" }}}"><a href="{{ url('/novo/atendimentos') }}"><i class="fa fa-list"></i> Atendimento</a></li>
                <li class="{{{ Request::path()=='novo/contas' ? "active" : "" }}}"><a href="{{ url('/novo/contas') }}"><i class="fa fa-usd"></i> Conta</a></li>
                <li class="{{{ Request::path()=='novo/estoque' ? "active" : "" }}}"><a href="{{ url('/novo/estoque') }}"><i class="fa fa-bell"></i> Estoque</a></li>
                <li class="{{{ Request::path()=='novo/caixa' ? "active" : "" }}}"><a href="{{ url('/novo/caixa') }}"><i class="fa fa-money"></i> Caixa</a></li>
                <li class="{{{ Request::path()=='novo/vendas' ? "active" : "" }}}"><a href="{{ url('/novo/vendas') }}"><i class="fa fa-shopping-cart"></i> Vendas</a></li>
            </ul>
            <li  data-toggle="collapse" data-target="#listas" class="{{{ Request::is('lista*') ? "active" : "collapsed" }}}" aria-expanded="">
              <a href="#"><i class="fa fa-list fa-lg"></i> Listas<span class="arrow"></span></a>
            </li>
            <ul class="sub-menu collapse {{{ Request::is('lista*') ? "in" : "" }}}" aria-expanded="{{{ Request::is('lista*') ? "true" : "false" }}}" id="listas">
                <li class="{{{ Request::path()=='lista/contatos' ? "active" : "" }}}"><a href="{{ url('/lista/contatos') }}"><i class="fa fa-user"></i> Contato</a></li>
                <li class="{{{ Request::path()=='lista/atendimentos' ? "active" : "" }}}"><a href="{{ url('/lista/atendimentos') }}"><i class="fa fa-list"></i> Atendimento</a></li>
                <li class="{{{ Request::path()=='lista/contas' ? "active" : "" }}}"><a href="{{ url('/lista/contas') }}"><i class="fa fa-usd"></i> Conta</a></li>
                <li class="{{{ Request::path()=='lista/estoque' ? "active" : "" }}}"><a href="{{ url('/lista/estoque') }}"><i class="fa fa-bell"></i> Estoque</a></li>
                <li class="{{{ Request::path()=='lista/caixa' ? "active" : "" }}}"><a href="{{ url('/lista/caixa') }}"><i class="fa fa-money"></i> Caixa</a></li>
                <li class="{{{ Request::path()=='lista/vendas' ? "active" : "" }}}"><a href="{{ url('/lista/vendas') }}"><i class="fa fa-shopping-cart"></i> Vendas</a></li>
            </ul>

            @if (isset(Auth::user()->perms["admin"]) and Auth::user()->perms["admin"]==1)
              <li data-toggle="collapse" data-target="#admin" class="collapsed {{{ Request::is('admin*') ? "active" : "" }}}">
                <a href="#"><i class="fa fa-wrench fa-lg"></i> Controle <span class="arrow"></span></a>
              </li>
              <ul class="sub-menu collapse {{{ Request::is('admin*') ? "in" : "" }}}" aria-expanded="{{{ Request::is('admin*') ? "true" : "false" }}}" id="admin">
                <li class="{{{ Request::path()=='admin' ? "active" : "" }}}"><a href="{{ url('/admin') }}">Controle de Usuarios</a></li>
                <li class="{{{ Request::path()=='admin/update' ? "active" : "" }}}"><a href="{{ url('/admin/update') }}">Atualizar</a></li>
                <li class="{{{ Request::path()=='admin/backup' ? "active" : "" }}}"><a href="{{ url('/admin/backup') }}">Backup</a></li>
              </ul>
            @endif


        </ul>
 </div>
