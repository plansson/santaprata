<?php

class Frete extends PHPFrodo
{

    public $user_login;
    public $user_id;
    public $user_name;
    public $user_level;
    public $frete_param;
    public $linhas_afetadas = 0;

    public function __construct()
    {
        parent::__construct();
        $sid = new Session;
        $sid->start();
        if ( !$sid->check() || $sid->getNode( 'user_id' ) <= 0 )
        {
            $this->redirect( "$this->baseUri/admin/login/logout/" );
            exit;
        }
        $this->user_login = $sid->getNode( 'user_login' );
        $this->user_id = $sid->getNode( 'user_id' );
        $this->user_name = $sid->getNode( 'user_name' );
        $this->user_level = ( int ) $sid->getNode( 'user_level' );
        $this->assign( 'user_name', $this->user_name );
        $this->select()
                ->from( 'config' )
                ->execute();
        if ( $this->result() )
        {
            $this->config = ( object ) $this->data[0];
            $this->assignAll();
        }
        if ( isset( $this->uri_segment ) && in_array( 'process-ok', $this->uri_segment ) )
        {
            $this->assign( 'msgOnload', 'notify("<h1>Procedimento realizado com sucesso</h1>")' );
        }
        if ( $this->user_level == 1 ) {
            $this->assign('showhide','hide');
        }

    }

    public function welcome()
    {
        $this->tpl( 'admin/frete.html' );
        $this->select()
                ->from( 'frete' )
                ->execute();
        if ( $this->result() )
        {
            $this->money( 'frete_taxa' );
            $this->money( 'frete_apt' );
            $this->money( 'frete_sul' );
            $this->assignAll();
        }
        $this->render();
    }

    public function atualizar()
    {
        if ( $this->postIsValid( array( 'frete_cep_origem' => 'string' ) ) )
        {
            $this->postValueChange( 'frete_apt', preg_replace( array( '/\./', '/\,/' ), array( '', '.' ), $this->postGetValue( 'frete_apt' ) ) );
            $this->postValueChange( 'frete_sul', preg_replace( array( '/\./', '/\,/' ), array( '', '.' ), $this->postGetValue( 'frete_sul' ) ) );
            $this->postValueChange( 'frete_taxa', preg_replace( array( '/\./', '/\,/' ), array( '', '.' ), $this->postGetValue( 'frete_taxa' ) ) );

            if ( !$this->postGetValue( 'frete_sedex' ) )
            {
                $this->postIndexAdd( 'frete_sedex', '0' );
            }
            if ( !$this->postGetValue( 'frete_sedex10' ) )
            {
                $this->postIndexAdd( 'frete_sedex10', '0' );
            }
            if ( !$this->postGetValue( 'frete_sedexac' ) )
            {
                $this->postIndexAdd( 'frete_sedexac', '0' );
            }
            if ( !$this->postGetValue( 'frete_pac' ) )
            {
                $this->postIndexAdd( 'frete_pac', '0' );
            }
            $this->update( 'frete' )->set()->execute();
            $this->redirect( "$this->baseUri/admin/frete/process-ok/" );
        }
    }

    public function fillCategoria() {
        $this->select()
            ->from('categoria')
            ->orderby('categoria_title asc')
            ->execute();
        if ($this->result()) {
            $this->fetch('combo', $this->data);
        }
    }

    public function bulkupdate() {
        //$this->pagebase = "$this->baseUri/admin/item";
        if (isset($_POST['item_categoria'])) {
            $item_categoria = $_POST['item_categoria'];
            $where  = "item_categoria = $item_categoria";
        }
        if (isset($_POST['item_sub']) && $_POST['item_sub'] != 0) {
            $item_sub = (int) $_POST['item_sub'];
            $where .= " AND item_sub = $item_sub";
        }
        if (isset($_POST['item_calcula_frete'])) {
            $item_calcula_frete = $_POST['item_calcula_frete'];
        }
        if ($this->postIsValid(array(
            'item_categoria' => 'string'
        ))) {
            $this->select()->from('item')->where($where)->execute();
            if($this->result()){
                $this->assign('linhas_afetadas', count($this->data) . ' linha(s) atualizada(s).');
                $this->linhas_afetadas = count($this->data) . ' linha(s) atualizada(s).';
                $this->update('item')->set(array('item_calcula_frete'),array($item_calcula_frete))->where($where)->execute();
            }
            $this->redirect("$this->baseUri/admin/frete/bulkupdate/process-ok/");
        }
        //var_dump($this);
        $this->tpl('admin/freight_bulk_update.html');
        $this->fillCategoria();
        $this->render();
    }

}

/*end file*/