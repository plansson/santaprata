<?php

error_reporting(E_ALL);

class Cliente extends PHPFrodo
{

    public $config = array();
    public $menu;
    public $proccess_msg = null;
    public $msg_error = null;
    public $message_login;
    public $login = null;
    public $view = null;
    public $sid = null;
    public $cliente_cpf = null;
    public $cliente_email = null;
    public $cliente_id = null;
    public $cliente_nome = null;
    public $cliente_ie = null;
    public $cliente_tipo  = null;
    public $cliente_graduacao = null;
    public $cliente_curso = null;
    public $status_pat = array('/1/', '/2/', '/3/', '/4/', '/5/', '/6/', '/7/');
    public $status_rep = array('Aguardando pagamento', 'Pedido em análise, aguarde a aprovação', 'Autorizada', 'Disponível', 'Em disputa', 'Devolvida', 'Não autorizada');

    public function __construct()
    {
        parent:: __construct();
        $this->view = new TemplateFy;
        $this->login = null;
        $this->sid = new Session;
        $this->sid->start();
        if ($this->sid->check() && $this->sid->getNode('cliente_id') >= 1) {
            $this->cliente_cep = (string)$this->sid->getNode('cliente_cep');
            $this->cliente_email = (string)$this->sid->getNode('cliente_email');
            $this->cliente_id = (string)$this->sid->getNode('cliente_id');
            $this->cliente_nome = (string)$this->sid->getNode('cliente_nome');
            $this->cliente_fullnome = (string)$this->sid->getNode('cliente_fullnome');
            $this->assign('cliente_nome', $this->cliente_nome);
            $this->assign('cliente_email', $this->cliente_email);
            $this->assign('cliente_msg', 'acesse aqui sua conta.');
            $this->assign('cliente_nome', $this->cliente_nome);
            $this->assign('cliente_email', $this->cliente_email);
            $this->assign('cliente_msg', 'acesse aqui sua conta.');
            $this->login = array(
                'cliente_email' => "$this->cliente_email",
                'cliente_nome' => "$this->cliente_nome",
                'cliente_id' => "$this->cliente_id",
            );
            $this->assign('logged', 'true');
            $this->assign('logged', 'true');
        } else {
            $this->assign('cliente_nome', 'visitante');
            $this->assign('cliente_msg', 'faça seu login ou cadastre-se.');
            $this->assign('logged', 'false');
            $this->assign('cliente_nome', 'visitante');
            $this->assign('cliente_msg', 'faça seu login ou cadastre-se.');
            $this->assign('logged', 'false');
        }
        $this->select()
            ->from('config')
            ->execute();
        if ($this->result()) {
            $this->map($this->data[0]);
            $this->config = (object)$this->data[0];
            $this->assignAll();
            $this->assignAll($this->data);
        }

        $this->select()
            ->from('social')
            ->execute();
        if ($this->result()) {
            $this->social = (object)$this->data[0];
            if ($this->social->social_fb == "") {
                $this->assign('faceSH', 'hide');
            } else {
                $pl = '<div class="fb-page" data-href="' . $this->social->social_fb . '" data-width="500" data-small-header="true" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true" data-show-posts="false"><div class="fb-xfbml-parse-ignore"><blockquote cite="https://www.facebook.com/clares.lab"><a href="https://www.facebook.com/clares.lab">PHPStaff</a></blockquote></div></div>
                      <div id="fb-root"></div>';
                $this->assign('social_plug_fb', $pl);
            }
            if ($this->social->social_tw == "") {
                $this->assign('twSH', 'hide');
            }
            if ($this->social->social_yt == "") {
                $this->assign('ytSH', 'hide');
            }
            if ($this->social->social_in == "") {
                $this->assign('inSH', 'hide');
            }
            if ($this->config->config_site_cnpj == "") {
                $this->assign('cnpjSH', 'hide');
            }
            $this->assignAll();
        }
        $this->getCarrinho();
    }

    public function welcome()
    {
        if ($this->login == null || $this->cliente_id < 1) {
            $this->tpl('public/cliente_login.html');
        } else {
            $this->tpl('public/cliente_area.html');
        }
        $this->getMenu();
        $this->render();
    }

    public function cadastro()
    {
        if ($this->login == null || $this->cliente_id < 1) {
            $this->tpl('public/cliente_cadastro.html');
            if (isset($_SESSION['email_cadastro'])) {
                $cliente_email = trim(strtolower($_SESSION['email_cadastro']));
                $this->assign('cliente_email', "$cliente_email");
            }
            $this->getMenu();
            $this->render();
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
    }

    public function dados()
    {
        if ($this->login != null) {
            empty($_SESSION['node']['cpf'])? $this->tpl('public/cliente_dados_pjuridica.html') : $this->tpl('public/cliente_dados.html');
            $this->select()
                ->from('cliente')
                ->where("cliente_id = $this->cliente_id")
                ->execute();
            if ($this->result()) {
                $this->assignAll();
            }
            if (isset($this->uri_segment) && in_array('atualizado', $this->uri_segment)) {
                $this->assign('message_default', '<p class="well well-small">DADOS ATUALIZADOS COM SUCESSO!</p>');
            }
            $this->getMenu();
            $this->render();
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
    }

    public function enderecoAdd()
    {
        if ($this->login != null) {
            $valid = array(
                'endereco_cep' => 'string',
                'endereco_rua' => 'string',
                'endereco_num' => 'string',
                'endereco_bairro' => 'string',
                'endereco_cidade' => 'string',
                'endereco_uf' => 'string',
                'endereco_title' => 'string'
            );
            if ($this->postIsValid($valid)) {
                $this->postIndexAdd('endereco_cliente', $this->cliente_id);
                $this->postIndexAdd('endereco_tipo', 2);

                $this->postValueChange('endereco_rua', addslashes($this->postGetValue('endereco_rua')));
                $this->postValueChange('endereco_bairro', addslashes($this->postGetValue('endereco_bairro')));
                $this->postValueChange('endereco_title', addslashes($this->postGetValue('endereco_title')));
                $this->postValueChange('endereco_cidade', addslashes($this->postGetValue('endereco_cidade')));

                $this->insert('endereco')->fields()->values()->execute();
                if (isset($_SESSION['referer'])) {
                    $url_retorno = $_SESSION['referer'];
                    unset($_SESSION['referer']);
                    $this->redirect("$url_retorno");
                } else {
                    $this->redirect("$this->baseUri/cliente/endereco/cadastrado/");
                }
            }
        }
    }

    public function enderecoVSpedido()
    {
        $this->endereco_id = $_POST['eid'];
        $this->select()
            ->from('endereco')
            ->join('pedido', 'pedido_endereco = endereco_id', 'INNER')
            ->where("endereco_id = $this->endereco_id")
            ->execute();
        if ($this->result()) {
            echo 0;
        } else {
            echo 1;
        }
    }

    public function endereco()
    {
        if ($this->login != null) {
            $this->tpl('public/cliente_endereco.html');
            $this->select()
                ->from('endereco')
                ->where("endereco_cliente = $this->cliente_id AND endereco_tipo = 1")
                ->execute();
            if ($this->result()) {
                $this->fetch('addr', $this->data);
                $this->assignAll();
            }
            $this->select()
                ->from('endereco')
                ->where("endereco_cliente = $this->cliente_id AND endereco_tipo = 2")
                ->execute();
            if ($this->result()) {
                $this->fetch('baddr', $this->data);
                $this->assignAll();
            }
            if (isset($this->uri_segment) && in_array('atualizado', $this->uri_segment)) {
                $this->assign('message_default', '<p class="well well-small">ENDEREÇO ATUALIZADO COM SUCESSO!</p>');
            }
            if (isset($this->uri_segment) && in_array('removido', $this->uri_segment)) {
                $this->assign('message_default', '<p class="well well-small">ENDEREÇO REMOVIDO COM SUCESSO!</p>');
            }
            if (isset($this->uri_segment) && in_array('cadastrado', $this->uri_segment)) {
                $this->assign('message_default', '<p class="well well-small">ENDEREÇO CADASTRADO COM SUCESSO!</p>');
            }
            $this->getMenu();
            $this->render();
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
    }

    public function enderecoNovo()
    {
        if ($this->login != null) {
            $this->tpl('public/cliente_endereco_novo.html');
            if (isset($this->uri_segment) && in_array('cadastrado', $this->uri_segment)) {
                $this->assign('message_default', '<p class="well well-small">ENDEREÇO CADASTRADO COM SUCESSO!</p>');
            }
            $this->getMenu();
            $this->render();
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
    }

    public function enderecoRemove()
    {
        if ($this->login != null) {
            $this->endereco_id = $this->uri_segment[2];
            $this->delete()
                ->from('endereco')
                ->where("endereco_id = $this->endereco_id AND endereco_cliente = $this->cliente_id")
                ->execute();
            $this->redirect("$this->baseUri/cliente/endereco/removido/");
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
    }

    public function fillDados()
    {
        $this->select()
            ->from('cliente')
            ->where("cliente_id = $this->cliente_id")
            ->execute();
        if ($this->result()) {
            $this->assignAll();
        }
    }

    public function getMenu()
    {
        $this->menu = new Menu;
        $menu = $this->menu->getAll();
        $this->fetch('cat', $menu[0]);
        if (!$this->check_agent('mobile')) {
            //$this->fetch('cat', $menu[0]);
        } else {
            //$this->fetch('depto', $menu[1]);
        }
        $this->fetch('f', $this->menu->getFooter());
    }

    public function getCarrinho()
    {
        $qtdeITem = 0;
        if (isset($_SESSION['cart']) && count($_SESSION['cart']) >= 1) {
            $qtdeITem = count($_SESSION['cart']);
            $cart = new Carrinho;
            $cart->getTotal();
            $cart->total_compra = @number_format($cart->total_compra, 2, ",", ".");
            $this->assign('cartTotal', "R$ " . $cart->total_compra);
        }
        $this->assign('qtdeItem', $qtdeITem);
    }

    public function enderecoAtualizar()
    {
        if ($this->login != null || $this->cliente_id < 1) {
            $valid = array(
                'endereco_cep' => 'string',
                'endereco_rua' => 'string',
                'endereco_num' => 'string',
                'endereco_bairro' => 'string',
                'endereco_cidade' => 'string',
                'endereco_uf' => 'string'
            );
            if ($this->postIsValid($valid)) {
                $this->endereco_id = $this->uri_segment[2];

                $this->postValueChange('endereco_rua', addslashes($this->postGetValue('endereco_rua')));
                $this->postValueChange('endereco_bairro', addslashes($this->postGetValue('endereco_bairro')));
                $this->postValueChange('endereco_title', addslashes($this->postGetValue('endereco_title')));
                $this->postValueChange('endereco_cidade', addslashes($this->postGetValue('endereco_cidade')));

                $this->update('endereco')
                    ->set()
                    ->where("endereco_id = $this->endereco_id AND endereco_cliente = $this->cliente_id")
                    ->execute();
                $this->redirect("$this->baseUri/cliente/endereco/atualizado/");
            } else {
                $this->pageError();
            }
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
    }

    public function atualizarDados()
    {
        if ($this->login != null || $this->cliente_id < 1) {
            $valid = array(
                'cliente_nome' => 'string',
                'cliente_telefone' => 'string'
            );
            if ($this->postIsValid($valid)) {
                $this->cliente_nome = $this->postGetValue('cliente_nome');
                $this->cliente_cpf = $this->postGetValue('cliente_cpf');
                $pass = $this->postGetValue('cliente_password');
                if ($pass == "") {
                    $this->postIndexDrop('cliente_password');
                } else {
                    $this->postValueChange('cliente_password', md5($pass));
                }
                $this->postIndexDrop('cliente_passwordr');
                $this->postIndexDrop('cliente_email');
                /*
                  if ( $this->checkCPF() )
                  {
                  $this->msg_error = "CPF já cadastrado!";
                  }
                 */
                if ($this->msg_error == "") {
                    $this->update('cliente')->set()->where("cliente_id = $this->cliente_id")->execute();
                    $this->redirect("$this->baseUri/cliente/dados/atualizado/");
                } else {
                    $this->assign('msg_error', $this->msg_error);
                    $this->pageError();
                }
            } else {
                $this->pageError();
            }
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
    }

    public function cadastrar()
    {
        $this->tpl('public/cliente_cadastro.html');
        if ($this->login == null) {
            $valid = array(
                'cliente_password' => 'password',
                'cliente_telefone' => 'string',
                'cliente_cep' => 'string',
                'cliente_rua' => 'string',
                'cliente_num' => 'string',
                'cliente_bairro' => 'string',
                'cliente_cidade' => 'string',
                'cliente_uf' => 'string',
            );


            $this->postIndexAdd('cliente_nome', '');
            if(empty($_POST['cliente_nome_fisico']) && !empty($_POST['cliente_nome_juridico'])){
                $this->postValueChange('cliente_nome', $_POST['cliente_nome_juridico']);
            }else{$this->postValueChange('cliente_nome', $_POST['cliente_nome_fisico']);}


            $cpf = $_POST['cliente_cpf'];
            if(!$cpf){
                $this->postIndexDrop('cliente_cpf');
            }else{$this->cliente_cpf = $this->postGetValue('cliente_cpf');}

            if ($this->postIsValid($valid)) {
                $this->postIndexDrop('cliente_passwordr');
                $this->postValueChange('cliente_password', md5($this->postGetValue('cliente_password')));

                $this->cliente_email = $this->postGetValue('cliente_email');
                $this->cliente_nome = $this->postGetValue('cliente_nome');
                $tipo = $this->postGetValue('cliente_tipo');

                if($tipo == 1){
                    $this->postValueChange('cliente_tipo', 'Pessoa Física');
                }elseif ($tipo == 2){
                    $this->postValueChange('cliente_tipo', 'Pessoa Jurídica');
                }
                $this->cliente_tipo = $this->postGetValue('cliente_tipo');
                empty($this->postGetValue('cliente_ie'))? $this->postIndexDrop('cliente_ie') : $this->cliente_ie = $this->postGetValue('cliente_ie');

//                if ($this->checkCPF()) {
//                    $this->msg_error = ' * CPF já cadastrado!';
//                }
                if ($this->checkMail()) {
                    $this->msg_error = ' * E-mail já cadastrado!';
                }

                $this->postValueChange('cliente_rua', addslashes($this->postGetValue('cliente_rua')));
                $this->postValueChange('cliente_complemento', addslashes($this->postGetValue('cliente_complemento')));
                $this->postValueChange('cliente_bairro', addslashes($this->postGetValue('cliente_bairro')));
                $this->postValueChange('cliente_cidade', addslashes($this->postGetValue('cliente_cidade')));

                if ($this->msg_error == "") {

                    $this->cliente_graduacao = $this->postGetValue('cliente_graduacao');
                    $this->cliente_curso = $this->postGetValue('cliente_curso');

                    //endereco
                    $rua = $this->postGetValue('cliente_rua');
                    $num = $this->postGetValue('cliente_num');
                    $com = $this->postGetValue('cliente_complemento');
                    $bai = $this->postGetValue('cliente_bairro');
                    $cid = $this->postGetValue('cliente_cidade');
                    $uf = $this->postGetValue('cliente_uf');
                    $cep = $this->postGetValue('cliente_cep');
                    $this->postIndexDrop('cliente_rua');
                    $this->postIndexDrop('cliente_num');
                    $this->postIndexDrop('cliente_complemento');
                    $this->postIndexDrop('cliente_bairro');
                    $this->postIndexDrop('cliente_cidade');
                    $this->postIndexDrop('cliente_uf');
                    $this->postIndexDrop('cliente_cep');
                    $this->postIndexDrop('cliente_nome_fisico');
                    $this->postIndexDrop('cliente_nome_juridico');
                    $this->postIndexAdd('cliente_datacad', date('d/m/Y h:s'));

                    //add cliente
                    $this->insert('cliente')->fields()->values()->execute();
                    $this->cliente_id = $this->objBanco->lastId();
                    $f = array('endereco_rua', 'endereco_num',
                        'endereco_complemento', 'endereco_bairro',
                        'endereco_cidade', 'endereco_uf',
                        'endereco_cep', 'endereco_cliente',
                        'endereco_title');
                    $v = array("$rua", "$num", "$com", "$bai", "$cid", "$uf", "$cep",
                        "$this->cliente_id", "Endereço de Correspondência");
                    //add endereco
                    $this->insert('endereco')->fields($f)->values($v)->execute();
                    //sessao cadastro
                    //sessao cadastro
                    $this->select('*')
                        ->from('cliente')
                        ->where("cliente_id = $this->cliente_id")
                        ->execute();
                    if ($this->result()) {
                        $this->preg('/\s+/', ' ', 'cliente_nome');
                        $this->sid = new Session;
                        $this->sid->start();
                        $this->sid->init(36000);
                        $this->sid->addNode('start', date('d/m/Y - h:i'));
                        $this->sid->addNode('cliente_id', $this->data[0]['cliente_id']);
                        $this->sid->addNode('cliente_email', $this->data[0]['cliente_email']);
                        $this->sid->addNode('cliente_nome', $this->data[0]['cliente_nome']);
                        $this->sid->addNode('cliente_cep', $cep);
                        $this->sid->check();
                        $this->login_status = true;
                        if (isset($_SESSION['cart'])) {
                            $this->redirect("$this->baseUri/finalizar/entrega/");
                        } else {
                            $this->redirect("$this->baseUri/cliente/");
                        }
                    } else {
                        $this->redirect("$this->baseUri/cliente/");
                    }
                } else {
                    $this->assign('msg_error', $this->msg_error);
                    $this->pageError();
                }
            } else {
                $this->pageError();
            }
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
    }

    public function cadastroPass()
    {
        $this->tpl('public/cliente_cadastro_pass.html');
        $this->getMenu();
        $this->render();
    }

    public function checkCPF()
    {
//        if ($this->login != null) {
//            $cond = "cliente_cpf = '$this->cliente_cpf' AND cliente_id <> $this->cliente_id";
//        } else {
//            $cond = "cliente_cpf = '$this->cliente_cpf'";
//        }
//        $this->select()
//            ->from('cliente')
//            ->join('endereco', 'cliente_id = endereco_cliente', 'INNER')
//            ->where("$cond AND endereco_tipo = 1")
//            ->execute();
//
//        if ($this->result()) {
//            return true;
//        } else {
//            return false;
//        }
        return true;
    }

    public function checkMail()
    {
        if ($this->login != null) {
            $cond = "cliente_email = '$this->cliente_email' AND cliente_id <> $this->cliente_id";
        } else {
            $cond = "cliente_email = '$this->cliente_email'";
        }
        $this->select()
            ->from('cliente')
            ->where("$cond")
            ->execute();
        if ($this->result()) {
            return true;
        } else {
            return false;
        }
    }

    public function checkPreExistCPF()
    {
        $valid = array(
            'cliente_cpf' => 'cpf'
        );
        if ($this->postIsValid($valid)) {
            $this->cliente_cpf = $this->postGetValue('cliente_cpf');
//            if ($this->checkCPF()) {
//                //CPF existe
//                echo 1;
//            } else {
//                //CPF nao existe
//                echo 0;
//            }
        } else {
            //CPF invalido
            echo 2;
        }
    }

    public function checkPreExistEmail()
    {
        $valid = array(
            'cliente_email' => 'email'
        );
        if ($this->postIsValid($valid)) {
            $this->cliente_email = $this->postGetValue('cliente_email');
            if ($this->checkMail()) {
                //MAIL existe
                echo 1;
            } else {
                //MAIL nao existe
                echo 0;
            }
        } else {
            //MAIL invalido
            echo 2;
        }
    }

    public function checkNome()
    {

    }

    public function login()
    {
        $this->tpl('public/cliente_login.html');
        if ($this->postIsValid(array('cliente_cadastrado' => 'string'))) {
            $cadastrado = $this->postGetValue('cliente_cadastrado');
            if ($cadastrado == 'nao') {
                $_SESSION['email_cadastro'] = $this->postGetValue('cliente_email');
                $this->redirect("$this->baseUri/cliente/cadastro/");
            } else {
                $this->proccess();
                $this->assign('message_login', "$this->message_login");
                $this->getMenu();
                $this->render();
            }
        } else {
            $this->redirect("$this->baseUri/cliente/cadastro/");
        }
    }

    public function logout()
    {
        unset($_SESSION['LAST_ACTIVITY']);
        /* preversa carrinho
          $this->sid = new Session;
          @$this->sid->start();
          $this->sid->destroy();
          $this->sid->check();
         */
        $this->redirect("$this->baseUri/");
    }

    public function proccess()
    {
        if ($this->postIsValid(array('cliente_email' => 'email', 'cliente_password' => 'string'))) {
            $cliente_email = $this->postGetValue('cliente_email');
            $cliente_password = md5($this->postGetValue('cliente_password'));
            $this->select('*')
                ->from('cliente')
                ->join('endereco', 'cliente_id = endereco_cliente', 'INNER')
                ->where("cliente_email = '$cliente_email' and cliente_password = '$cliente_password'")
                ->execute();
            if ($this->result()) {
                $this->preg('/\s+/', ' ', 'cliente_nome');
                $this->sid = new Session;
                $this->sid->start();
                $this->sid->init(36000);
                $this->sid->addNode('start', date('d/m/Y - h:i'));
                $this->sid->addNode('cliente_id', $this->data[0]['cliente_id']);
                $this->sid->addNode('cliente_email', $this->data[0]['cliente_email']);
                $this->sid->addNode('cliente_nome', $this->data[0]['cliente_nome']);
                $this->sid->addNode('cliente_sobrenome', $this->data[0]['cliente_sobrenome']);
                $this->sid->addNode('cliente_fullnome', $this->data[0]['cliente_nome'] . " " . $this->data[0]['cliente_sobrenome']);
                $this->sid->addNode('cliente_cep', $this->data[0]['endereco_cep']);
                $this->sid->addNode('cpf', $this->data[0]['cliente_cpf']); // caso não tenha cpf é pessoa juridica
                $this->sid->check();
                $url_retorno = "$this->baseUri/cliente/";
                if (isset($_POST['url_retorno'])) {
                    $url_retorno = $_POST['url_retorno'];
                }
                $this->login_status = true;
                $this->redirect("$url_retorno");
            } else {
                $this->login_status = false;
                $this->message_login .= "<p class=\"alert alert-danger\">e-mail ou senha incorretos!</p>";
            }
        } else {
            $this->login_status = false;
            $this->message_login = "<p class=\"alert alert-danger\">e-mail e senha requeridos!</p>";
        }
    }

    public function novasenha()
    {
        if ($this->postIsValid(array('cliente_email' => 'string'))) {
            $cliente_email = $this->postGetValue('cliente_email');
            $chars = 'abcdefghijlmnopqrstuvxwzABCDEFGHIJLMNOPQRSTUVXYWZ0123456789';
            $max = strlen($chars) - 1;
            $pass = "";
            $width = 8;
            for ($i = 0; $i < $width; $i++) {
                $pass .= $chars{mt_rand(0, $max)};
            }
            $this->select('*')
                ->from('cliente')
                ->where("cliente_email = '$cliente_email'")
                ->execute();
            if (!$this->result()) {
                $this->tpl('public/cliente_login.html');
                $this->message_login = "<p class=\"alert alert-danger\">O e-mail informado não está cadastrado!</p>";
                $this->assign('message_login', "$this->message_login");
                $this->getMenu();
                $this->render();
                exit;
            }
            $this->update('cliente')
                ->set(array('cliente_password'), array(md5($pass)))
                ->where("cliente_email = '$cliente_email'");
            if ($this->execute()) {
                $body = '<html><body>';
                $body .= '<h1 style="font-size:15px;">Sua nova senha foi gerada!</h1>';
                $body .= '<table style="border-color: #666; font-size:11px" cellpadding="10">';
                $body .= '<tr style="background: #eee;"><td><strong>IP Solicitante:</strong> </td><td>' . $_SERVER['REMOTE_ADDR'] . '</td></tr>';
                $body .= '<tr style="background: #fff;"><td><strong>Data:</strong> </td><td>' . date('d/m/Y h:s') . '</td></tr>';
                $body .= '<tr style="background: #eee;"><td><strong>Nova Senha:</strong> </td><td>' . $pass . '</td></tr>';
                $body .= '</table>';
                $body .= '<br/><br/>';
                $body .= '</body></html>';
                $m = new sendmail;
                $n = array(
                    'email' => "$cliente_email",
                    'subject' => utf8_decode("$this->config_site_title - Recuperação de senha"),
                    'body' => $body);
                if ($m->sender($n)) {
                    $this->tpl('public/cliente_login.html');
                    $this->message_login = "<p class=\"alert alert-success\">Sua nova senha foi enviada por e-mail! Verifique sua caixa de entrada.</p>";
                    $this->assign('message_login', "$this->message_login");
                    $this->getMenu();
                    $this->render();
                    exit;
                } else {
                    $this->tpl('public/cliente_login.html');
                    $this->message_login = "<p class=\"alert alert-danger\">Houve um erro ao enviar o e-mail! Entre em contato com suporte!</p>";
                    $this->assign('message_login', "$this->message_login");
                    $this->getMenu();
                    $this->render();
                    exit;
                }
            } else {
                $this->tpl('public/cliente_login.html');
                $this->message_login = "<h3 class=\"alert alert-danger\">Houve um erro ao enviar o e-mail! Entre em contato com suporte!</h3>";
                $this->assign('message_login', "$this->message_login");
                $this->getMenu();
                $this->render();
                exit;
            }
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
    }

    public function pedidos()
    {
        if ($this->login != null) {
            $this->tpl('public/pedido.html');
            $this->select()
                ->from('pedido')
                ->join('lista', 'lista_pedido = pedido_id', 'INNER')
                ->where("pedido_cliente = $this->cliente_id")
                ->groupby('pedido_id')
                ->orderby('pedido_id desc')
                ->execute();
            if ($this->result()) {
                $this->preg(array('/1/', '/2/', '/3/', '/4/'), array('warning', 'info', 'success', 'error'), 'pedstatus');
                $this->preg($this->status_pat, $this->status_rep, 'pedido_status');
                $this->cut('lista_title', 50, '...');
                //$this->money( 'pedido_total_frete' );

                $data = $this->data;
                //remove pedidos abandonados a mais de 10 minutos
                foreach ($data as $k => $v) {
                    $data[$k]['lista_total'] = $data[$k]['lista_preco'] * $data[$k]['lista_qtde'];

                    //total produtos - descontos cupom + frete
                    $data[$k]['pedido_total_frete'] = ($data[$k]['pedido_total_produto'] - $data[$k]['pedido_cupom_desconto']) + $data[$k]['pedido_frete'];
                    if ($this->data[0]['pedido_cupom_desconto'] != 0) {
                        $data[$k]['pedido_total_produto_desconto'] = ($data[$k]['pedido_total_produto'] - $data[$k]['pedido_cupom_desconto']);
                        $showCupomDesconto = 'showin';
                        //$this->money( 'pedido_cupom_desconto' );
                        //$this->money( 'pedido_total_produto_desconto' );
                    }
                }
                $this->data = $data;
                $this->money('pedido_total_frete');
                $this->fetch('cart', $this->data);
            } else {
                $this->assign('showHide', 'hide');
                $this->assign('msg_pedido', '<h5 class="alert alert-info">Nenhum pedido em sua lista.</h5>');
            }
            $this->getMenu();
            $this->render();
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
    }

    public function rastrear($__codigo = null, $ret = null){
    	return null;
         // //@var string - URL dos correios para obter dados
         // $__wsdl = "http://webservice.correios.com.br/service/rastro/Rastro.wsdl";
         // //@var array - a ser usado com parametro para 1 objeto
         // $_buscaEventos = array(
         //    'usuario'   => 'ECT',
         //    'senha'     => 'SRO',
         //    'tipo'      => 'L',
         //    'resultado' => 'T',
         //    'lingua'    => '101'
         // );
         // $_buscaEventos['objetos'] = $__codigo;
         // // criando objeto soap a partir da URL
         // $client = new SoapClient( $__wsdl );
         // $r = $client->buscaEventos( $_buscaEventos );
         // // sempre retorna objeto por padrao
         // $obj = $r->return->objeto;
         // $body = "<pre>";
         // if(! isset( $obj->erro ) ):
         //   # Visualizando dados basicos do objeto
         //   $body .= "NUMERO: "    . $obj -> numero . "<br>" ;
         //   $body .= "SIGLA: "     . $obj -> sigla . "<br>" ;
         //   $body .= "NOME: "      . $obj -> nome . "<br>" ;
         //   $body .= "CATEGORIA: " . $obj -> categoria . "<br><br>" ;
         //   // NOTA: Caso objeto rastreado possua apenas 1 evento,
         //   // Correios retorna o evento dentro de um Object e não um Array.
         //   if( is_object($obj->evento) ):
         //       $tmp = Array();
         //       $tmp[] = $obj->evento ;
         //       $obj->evento = $tmp;
         //   endif;
         //   # percorrendo os eventos ocorridos com o objeto
         //   foreach( $obj->evento as $ev ):
         //       $body .= "TIPO: "   . $ev -> tipo   . "<br>" ;
         //       $body .= "STATUS: " . $ev -> status . "<br>" ;
         //       $body .= "DATA: "   . $ev -> data   . "<br>" ;
         //       $body .= "HORA: "   . $ev -> hora   . "<br>" ;
         //       $body .= "DESCRICAO: " . $ev -> descricao . "<br>" ;
         //       if( isset( $ev -> detalhe ) )
         //           $body .= "DETALHE: " . $ev -> detalhe . "<br>" ;
         //       $body .= "LOCAL: "  . $ev -> local  . "<br>" ;
         //       $body .= "CODIGO: " . $ev -> codigo . "<br>" ;
         //       $body .= "CIDADE: " . $ev -> cidade . "<br>" ;
         //       $body .= "UF: "     . $ev -> uf     . "<br>" ;

         //       if( isset( $ev -> destino ) ):
         //           $body .= " DESTINO (LOCAL): "  . $ev -> destino -> local . "<br>" ;
         //           $body .= " DESTINO (CODIGO): " . $ev -> destino -> codigo . "<br>" ;
         //           $body .= " DESTINO (CIDADE): " . $ev -> destino -> cidade . "<br>" ;
         //           $body .= " DESTINO (BAIRRO): " . $ev -> destino -> bairro . "<br>" ;
         //           $body .= " DESTINO (UF): "     . $ev -> destino -> uf . "<br>" ;
         //       endif;
         //       $body .= "<hr>";
         //   endforeach;
         //   $body .= "</pre>";
         //   return $body;
         // else:
         //    return  $obj->numero . ": ". $obj->erro;
         // endif;
    }

    public function pedido()
    {
        //$this->tpldir = VIEWSDIR;
        //$this->baseApp = HTTPURL . APP;
        if ($this->login != null) {
            if (isset($this->uri_segment[2])) {
                $pedido_id = $this->uri_segment[2];
                //cancela pedido
                if (isset($this->uri_segment[3])) {
                    if ($this->uri_segment[3] == 'cancelar') {
                        $this->update('pedido')
                            ->set(array('pedido_status'), array(7))
                            ->where("pedido_id = $pedido_id")
                            ->execute();
                        $this->redirect("$this->baseUri/cliente/pedido/$pedido_id/");
                    }
                }

                $this->select()
                    ->from('pedido')
                    ->join('lista', 'lista_pedido = pedido_id', 'INNER')
                    ->where("pedido_cliente = $this->cliente_id and pedido_id = $pedido_id")
                    ->execute();
                if ($this->result()) {
                    if ( $this->data[0]['pedido_codigo_rastreio'] != "" )                    {
                       $rastreio_code =  $this->data[0]['pedido_codigo_rastreio'];
                    }
                    $this->map($this->data[0]);
                    /* DEFINE TPL GATEWAY */
                    switch ($this->pedido_pay_gw) {
                        case 1:
                            $this->tpl('public/pedido_detalhes_pagseguro.html');
                            break;
                        case 2:
                            $this->tpl('public/pedido_detalhes_paypal.html');
                            break;
                        case 3:
                            $this->tpl('public/pedido_detalhes_cielo.html');
                            break;
                        case 4:
                            $this->tpl('public/pedido_detalhes_deposito.html');
                            break;
                        case 5:
                            $this->tpl('public/pedido_detalhes_boleto.html');
                            break;
                        default:
                            $this->tpl('public/pedido_detalhes.html');
                            break;
                    }
                    $this->cut('lista_title', 40, '...');

                    $this->addkey('status_pedido', '', 'pedido_status');
                    $this->addkey('pedstatus', '', 'pedido_status');
                    $this->preg(array('/1/', '/2/', '/3/', '/4/', '/6/', '/7/'), array('warning', 'warning', 'success', 'success', 'warning', 'warning'), 'pedstatus');
                    $this->preg($this->status_pat, $this->status_rep, 'pedido_status');
                    foreach ($this->data as $k => $v) {
                        $this->data[$k]['lista_total'] = $this->data[$k]['lista_preco'] * $this->data[$k]['lista_qtde'];
                    }
                    $showCupomDesconto = 'hide';
                    //total produtos - descontos cupom + frete
                    $this->data[0]['pedido_total_frete'] = ($this->data[0]['pedido_total_produto'] - $this->data[0]['pedido_cupom_desconto']) + $this->data[0]['pedido_frete'];
                    if ($this->data[0]['pedido_cupom_desconto'] != 0) {
                        $this->data[0]['pedido_total_produto_desconto'] = ($this->data[0]['pedido_total_produto'] - $this->data[0]['pedido_cupom_desconto']);
                        $showCupomDesconto = 'showin';
                        $this->data[0]['pedido_cupom_desconto'] = -$this->data[0]['pedido_cupom_desconto'];
                        $this->money('pedido_cupom_desconto');
                    } else {
                        $this->data[0]['pedido_total_produto_desconto'] = $this->data[0]['pedido_total_produto'];
                    }
                    $this->money('pedido_total_produto_desconto');
                    $this->data[0]['pedido_acrescimos'] = ($this->data[0]['pedido_total_frete'] - $this->data[0]['pedido_frete']) - $this->data[0]['lista_total'];
                    $this->assign('showCupomDesconto', $showCupomDesconto);
                    $this->money('pedido_total_desconto');
                    $this->money('lista_total');
                    $this->money('lista_preco');
                    $this->money('pedido_total_produto');
                    $this->money('pedido_total_frete');
                    $this->money('pedido_frete');
                    $this->money('pedido_com_frete');
                    $this->money('pedido_acrescimos');
                    $this->assignAll($this->data);
                    $this->fetch('cart', $this->data);
                    $pedido_entrega = $this->data[0]['pedido_entrega'];
                    $endereco_id = $this->data[0]['pedido_endereco'];
                    if ($pedido_entrega == 1) {
                        $this->assign('tipo_local', 'Entrega');
                        $this->select()->from('endereco')->where("endereco_cliente = $this->cliente_id AND endereco_id = $endereco_id")->execute();
                        $this->assignAll($this->data);
                    } else {
                        $this->assign('tipo_local', 'Retirada');
                        $this->select()->from('retirada')->where("retirada_id = $endereco_id")->execute();
                        $this->assign('endereco_title', $this->data[0]['retirada_local']);
                        $this->assign('endereco_rua', $this->data[0]['retirada_rua']);
                        if (strlen($this->data[0]['retirada_complemento']) >= 2) {
                            $this->data[0]['retirada_num'] = $this->data[0]['retirada_num'] . ", " . $this->data[0]['retirada_complemento'];
                        }
                        $this->assignAll($this->data);
                        $this->assign('endereco_num', $this->data[0]['retirada_num']);
                        $this->assign('endereco_bairro', $this->data[0]['retirada_bairro']);
                        $this->assign('endereco_cidade', $this->data[0]['retirada_cidade']);
                        $this->assign('endereco_uf', $this->data[0]['retirada_uf']);
                        $this->assign('endereco_cep', $this->data[0]['retirada_cep']);
                        $this->assign('endereco_telefone', $this->data[0]['retirada_telefone']);
                        $this->assign('endereco_horario', $this->data[0]['retirada_horario']);
                    }
                } else {
                    $this->redirect("$this->baseUri/cliente/pedidos/");
                }
            } else {
                $this->redirect("$this->baseUri/cliente/pedidos/");
            }
            $this->getMenu();
            //DEPOSITO transf
            if ($this->pedido_pay_gw == 4 && $this->pedido_status == 1) {
                $this->select()->from('pay')->where("pay_name = 'Deposito'")->execute();
                $this->data[0]['pay_texto'] = nl2br($this->data[0]['pay_texto']);
                $txtDep = $this->data[0]['pay_texto'];
                $this->assign('dadosDeposito', $txtDep);
            }
            if ($this->pedido_pay_gw == 4 && $this->pedido_status < 3 && $this->pedido_comprovante == 0) {
                $this->assign('btnComprovante', 'hide');
            } elseif ($this->pedido_pay_gw == 4 && $this->pedido_comprovante <> 0) {
                $this->assign('btnComprovante', 'show');
            }
            if (in_array("erroComprovante", $this->uri_segment)) {
                $textError = "<br><br><p class='alert alert-danger'><strong><i class='glyphicon glyphicon-remove'></i> Extensão do comprovante não permitida! Você pode enviar PDF ou Imagens.</strong></p>";
                $this->assign('TextOnError', $textError);
            }

            //CIELO
            $obs_forma_pagto_cielo = '';
            if ($this->pedido_pay_gw == 3) {
                $obs_forma_pagto_cielo = explode("***", $this->pedido_pay_obs);
                $obs_forma_pagto_cielo = (isset($obs_forma_pagto_cielo[0])) ? $obs_forma_pagto_cielo[0] : '';
                $obs_forma_pagto_cielo = explode("=", $obs_forma_pagto_cielo);
                $obs_forma_pagto_cielo = (isset($obs_forma_pagto_cielo[0])) ? $obs_forma_pagto_cielo[0] : '';
                if (isset($_SESSION['__CIELO_MSG__'])) {
                    $this->assign('MSG_CIELO', $_SESSION['__CIELO_MSG__']);
                } else {
                    $this->assign('MSG_CIELO', '');
                }
                $this->assign('forma-pagto-cielo', $obs_forma_pagto_cielo);
            }
        } else {
            $this->redirect("$this->baseUri/cliente/");
        }
        if(isset($rastreio_code)){
            $rastreio = $this->rastrear($rastreio_code);
            $this->assign('rastreio', utf8_encode($rastreio));
        } else {
            $this->assign('show-rastreio', 'hide');
        }
        $this->render();
    }

    public function cieloCheck()
    {
        $pedido_id = 9;
        if (isset($pedido_id) && $pedido_id >= 1) {
            $this->select()->from('pay')->where('pay_name = "Cielo"')->execute();
            $this->map($this->data[0]);

            $this->select()->from('pedido')->where("pedido_id = $pedido_id")->execute();
            $this->map($this->data[0]);

            $this->pedido_id = $pedido_id;
            $tid = trim($this->data[0]['pedido_pay_code']); //TID que retornou quando a transacao foi criada.
            $cielo_numero = "$this->pay_user"; //Número de filiação da cielo, neste caso e o exemplo da homologacao
            $chave_cielo = "$this->pay_key"; // Chave de filiaçãoo da cielo exemplo da homologacao

            $string = <<<XML
<?xml version="1.0" encoding="ISO-8859-1"?>
<requisicao-consulta id="$pedido_id" versao="1.1.1">
<tid>$tid</tid>
<dados-ec>
<numero>$cielo_numero</numero>
<chave>$chave_cielo</chave>
</dados-ec>
</requisicao-consulta>
XML;
            if ($this->pay_pass == 2) {
                $cielo_numero = '1006993069'; //Número de filiação da cielo
                $chave_cielo = '25fbb99741c739dd84d7b06ec78c9bac718838630f30b112d033ce2e621b34f3';
                $url = 'https://qasecommerce.cielo.com.br/servicos/ecommwsec.do';
            } else {
                $cielo_numero = '1060475917'; //Número de filiação da cielo
                $chave_cielo = '2b3df3a6a324550d779dd8424adbbf841c4520463583b75250a648768398dd66';
                $url = 'https://ecommerce.cbmp.com.br/servicos/ecommwsec.do';
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'mensagem=' . $string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 40);
            curl_setopt($ch, CURLOPT_CAINFO, "app/helpers/cielo/ssl/VeriSignClass3PublicPrimaryCertificationAuthority-G5.crt");
            curl_setopt($ch, CURLOPT_SSLVERSION, 4); //alterar para 1

            $string = curl_exec($ch);
            curl_close($ch);
            $xml = @simplexml_load_string($string);

            if (isset($xml->tid) AND $xml->captura->codigo == '6' AND $xml->autorizacao->codigo == '6') {
                $node = 'forma-pagamento';
                $node = $xml->$node;

                $node_1 = 'dados-pedido';
                $node_1 = $xml->$node_1;

                $this->helper('cielo');

                $visa = new Cielo;
                $visa->taxa(0);
                $visa->juros($this->pay_fator_juros);
                $visa->valor($this->pedido_total_frete);
                $visa->num_parcelas($this->pay_c3);
                $visa->desconto_avista($this->pay_c2);
                $visa->parcelas_sem_juros($this->pay_c1);
                $visa->parcelamento();
                $visa->add_bandeira_array($this->pay_c4);
                //$visa->add_bandeira('Visa');
                //$visa->add_bandeira('Mastercard');
                //$visa->add_bandeira('Elo');
                //$total = $node_1->{'valor'}[0];
                $total = $visa->moeda($this->pedido_total_frete);
                $visa->valor_parcela = $visa->moeda($total / $node->{'parcelas'});
                $bandeira = ucfirst($node->{'bandeira'}[0]);

                $obs = "<strong>Forma de pagamento:</strong> $bandeira <Br>";
                $obs .= "<strong>Parcelas:</strong> " . $node->{'parcelas'} . " x " . ($visa->valor_parcela) . " = " . $total . " ***<Br>";
                $obs .= utf8_decode("<strong>Autorização:</strong> " . ($xml->autorizacao->{'mensagem'}[0]) . "<Br>");
                $obs .= "<strong>Captura:</strong>  " . ($xml->captura->{'mensagem'}[0]) . "<Br>";
                $obs .= "<strong>TID:</strong>  $xml->tid ";

                $this->update('pedido')
                    ->set(array('pedido_status', 'pedido_pay_obs'), array(3, ($obs)))
                    ->where("pedido_id = $pedido_id")
                    ->execute();
            } else {
                $node = 'forma-pagamento';
                if (isset($xml->$node->bandeira)) {
                    $xml->$node->bandeira;
                }
                if ($this->pedido_status <> 3) {
                    $this->update('pedido')
                        ->set(array('pedido_status'), array(7))
                        ->where("pedido_id = $pedido_id")
                        ->execute();
                }
            }
            echo $xml->mensagem;
            $this->printr($xml);
        }
    }

    public function faturaPayPal()
    {

    }

    public function recuperar()
    {
        $action = $this->uri_segment[2];
        switch ($action) {
            case 'senha':
                $this->recuperarSenha();
                break;
            case 'email':
                $this->recuperarEmail();
                break;
            case 'emailmudou':
                $this->recuperarEmailMudou();
                break;
        }
    }

    public function comprovante()
    {
        $dir_dest = 'app/fotos/comprovantes/';
        $this->pedido_id = $_POST['pedido_id'];
        if (isset($_FILES) && !empty($_FILES)) {
            $file = $_FILES['filedata'];
            $handle = new Upload($file);
            $handle->allowed = array('application/pdf', 'application/msword', 'image/*');
            if ($handle->uploaded) {
                $handle->file_overwrite = true;
                $handle->file_new_name_body = $this->pedido_id . "_" . md5(uniqid($file['name']));
                $handle->Process($dir_dest);
                if ($handle->processed) {
                    $this->select()
                        ->from('pedido')
                        ->where("pedido_id = $this->pedido_id AND pedido_comprovante <> 0")
                        ->execute();
                    if ($this->result()) {
                        $file = "app/fotos/comprovantes/" . $this->data[0]['pedido_comprovante'];
                        if (file_exists($file)) {
                            @unlink($file);
                        }
                    }
                    $this->update('pedido')
                        ->set(array('pedido_comprovante'), array("$handle->file_dst_name"))
                        ->where("pedido_id = $this->pedido_id")
                        ->execute();
                    $this->notificarComprovante("app/fotos/comprovantes/$handle->file_dst_name");
                    $this->redirect("$this->baseUri/cliente/pedido/$this->pedido_id/");
                } else {
                    $this->redirect("$this->baseUri/cliente/pedido/$this->pedido_id/erroComprovante/");
                }
            } else {
                $this->redirect("$this->baseUri/cliente/pedido/$this->pedido_id/show/");
            }
        } else {
            $this->redirect("$this->baseUri/cliente/pedido/$this->pedido_id/show/");
        }
    }

    public function notificarComprovante($file)
    {
        $body = '<html><body>';
        $body .= '<h1 style="font-size:15px;">Novo Comprovante Enviado</h1>';
        $body .= "<p>Um comprovante de depósito foi enviado para o pedido #$this->pedido_id</p>";
        $body .= "<p>";
        $body .= "Cliente: $this->cliente_nome <br>";
        $body .= "Email: $this->cliente_email  <br>";
        $body .= "Pedido: $this->pedido_id <br>";
        $body .= "Data: " . date('d/m/Y H:i:s');
        $body .= '</p>';
        $body .= '</body></html>';
        $n = array(
            'subject' => utf8_decode("Novo comprovante enviado para pedido Nº $this->pedido_id"),
            'body' => utf8_decode($body),
            'files' => array("$file")
        );
        $m = new sendmail;
        $m->sender($n);
    }

    public function imprimir()
    {
        $this->tpl('admin/pedido_impressao.html');
        if (isset($this->uri_segment[2])) {
            $this->pedido_id = $this->uri_segment[2];
            $this->select()
                ->from('pedido')
                ->join('lista', 'lista_pedido = pedido_id', 'INNER')
                ->join('item', 'lista_item = item_id', 'LEFT')
                ->join('cliente', 'pedido_cliente = cliente_id', 'INNER')
                ->join('pay', 'pedido_pay_gw = pay_id', 'INNER')
                ->where("pedido_id = $this->pedido_id")
                ->execute();
            if ($this->result()) {

                if ($this->data[0]['pedido_codigo_rastreio'] != "") {
                    $rastreio = $this->rastrear($this->data[0]['pedido_codigo_rastreio'], 1);
                    $this->assign('rastreio', $rastreio);
                }
                $this->cut('lista_title', 65, '...');
                foreach ($this->data as $k => $v) {
                    $this->data[$k]['lista_total'] = $this->data[$k]['lista_preco'] * $this->data[$k]['lista_qtde'];
                    $this->data[$k]['pedido_total_sem_frete'] = $this->data[$k]['pedido_total_produto'] - $this->data[$k]['pedido_cupom_desconto'];
                }
                if ($this->data[0]['pedido_cupom_desconto'] <= 0) {
                    $this->assign("show-desconto", "hide");
                }

                $this->data[0]['pedido_total_frete'] = ($this->data[0]['pedido_total_produto'] - $this->data[0]['pedido_cupom_desconto']) + $this->data[0]['pedido_frete'];
                $this->data[0]['pedido_acrescimos'] = ($this->data[0]['pedido_total_frete'] - $this->data[0]['pedido_frete']) - $this->data[0]['lista_total'];
                $this->money('lista_total');
                $this->money('pedido_total_sem_frete');
                $this->money('pedido_total_frete');
                $this->money('pedido_cupom_desconto');
                $this->money('lista_preco');
                $this->money('pedido_total_produto');
                $this->money('pedido_frete');
                $this->money('pedido_acrescimos');

                $this->pedido_update = date('d/m/Y H:i:s', strtotime($this->data[0]['pedido_update']));
                $this->assign("pedido_last_update", "$this->pedido_update");
                if (isset($this->data[0]['pedido_comprovante']) && $this->data[0]['pedido_comprovante'] != 0) {
                    $this->assign('cupom_desconto_anexo', 'showin');
                } else {
                    $this->assign('cupom_desconto_anexo', 'hide hider');
                }

                $this->preg($this->status_pat, $this->status_rep, 'pedido_status');
                $this->assignAll();
                $this->fetch('cart', $this->data);
                $pedido_entrega = $this->data[0]['pedido_entrega'];
                $endereco_id = $this->data[0]['pedido_endereco'];

                if ($pedido_entrega == 1) {
                    $this->assign('tipo_local', 'Entrega');
                    $this->select()->from('endereco')->where("endereco_id = $endereco_id")->execute();
                    $this->assignAll();
                } else {
                    $this->assign('tipo_local', 'Retirada');
                    $this->select()->from('retirada')->where("retirada_id = $endereco_id")->execute();
                    $this->assign('endereco_title', $this->data[0]['retirada_local']);
                    $this->assign('endereco_rua', $this->data[0]['retirada_rua']);
                    if (strlen($this->data[0]['retirada_complemento']) >= 2) {
                        $this->data[0]['retirada_num'] = $this->data[0]['retirada_num'] . ", " . $this->data[0]['retirada_complemento'];
                    }
                    $this->assign('endereco_num', $this->data[0]['retirada_num']);
                    $this->assign('endereco_bairro', $this->data[0]['retirada_bairro']);
                    $this->assign('endereco_cidade', $this->data[0]['retirada_cidade']);
                    $this->assign('endereco_uf', $this->data[0]['retirada_uf']);
                    $this->assign('endereco_cep', $this->data[0]['retirada_cep']);
                    $this->assign('endereco_telefone', $this->data[0]['retirada_telefone']);
                    $this->assign('endereco_horario', $this->data[0]['retirada_horario']);

                    $this->assignAll();
                }
            }
        } else {
            $this->redirect("$this->baseUri/admin/pedido/");
        }

        $this->render();
    }

    public function recuperarSenha()
    {
        $this->tpl('public/cliente_login_recuperar_senha.html');
        $this->getMenu();
        $this->render();
    }

    public function recuperarEmail()
    {
        echo "recuperar e-mail";
    }

    public function recuperarEmailMudou()
    {
        echo "recuperar e-mail mudou";
    }

    public function pageError()
    {
        $this->tpl('public/page_error.html');
        $this->assign('msg_error', $this->msg_error);
        $this->getMenu();
        $this->render();
    }

    public function val2bd($str)
    {
        //$str = preg_replace( '/\./', '', $str );
        $str = preg_replace('/\,/', '.', $str);
        return $str;
    }

    public function _money($val)
    {
        return @number_format($val, 2, ",", ".");
    }

    public function _moneyUS($val)
    {
        return @number_format($val, 2, ".", "");
    }

    public function _double($val)
    {
        return @number_format($val, 2, ".", ",");
    }

}

/*end file*/
