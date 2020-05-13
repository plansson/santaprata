<?php

class Notificacao extends PHPFrodo
{
    public $config = array();
    public $pay = array();
    public $pReq = null;
    public $pedido_id = null;
    public $pedido_status = null;
    public $status_pat = array('/1/', '/2/', '/3/', '/4/', '/5/', '/6/', '/7/');
    public $status_rep = array('Aguardando pagamento', 'Em análise', 'Autorizada', 'Disponível', 'Em disputa', 'Devolvida', 'Não autorizada');

    public function __construct(){
        parent:: __construct();
    }

    public function welcome(){
    }

    public function pagSeguro()
    {
        $this->select()->from( 'pay' )->where( 'pay_name = "PagSeguro"' )->execute();
        if ( !$this->result() )
        {
            $body = "<p>Retorno PagSeguro: Módulo não configurado! <br/>";
            $body .= "Hora: " . date( 'd/m/Y H:i:s' ) . " <br />";
            $body .= "Url: " . $this->baseUri;
            $body .= "</p>";
            $this->notificarErro( $body );
            exit;
        }
        $this->map( $this->data[0] );

        if($this->pay_c5 == "SANDBOX"){
            $notificationCode = $_REQUEST['notificationCode'];
            $url = "https://ws.sandbox.pagseguro.uol.com.br/v3/transactions/notifications/$notificationCode?email=$this->pay_user&token=$this->pay_key";
        }else{
            $notificationCode = $_REQUEST['notificationCode'];
            $url = "https://ws.pagseguro.uol.com.br/v3/transactions/notifications/$notificationCode?email=$this->pay_user&token=$this->pay_key";
        }

        if(!isset($_REQUEST['notificationCode'])){
            echo 'Notificacao vazia';
            exit;
        }


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($final, '', '&'));
        //curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $erro = curl_error($ch);
            $this->notificarErro( $erro );
        }else {
            $result = simplexml_load_string($result);

            $pedido_status = $result->status;
            $pedido_id = $result->reference;

            $this->update('pedido')
                ->set(array('pedido_pay_situacao', 'pedido_status'), array($pedido_status, $pedido_status))
                ->where("pedido_id = $pedido_id")
                ->execute();
            if ($this->pedido_status != 4) {
                $this->notificarAdmin();
                $this->notificarCliente();
            }
        }

    }

    public function cielo()
    {
        if (isset($this->uri_segment[2])) {
            $pedido_id = $this->uri_segment[2];
            $this->select()->from('pay')->where('pay_name = "Cielo"')->execute();
            $this->map($this->data[0]);

            $id = $pedido_id; //ID a compra, geralmente alguma chave primaria.
            $this->select()->from('pedido')->where("pedido_id = $pedido_id")->execute();
            $this->map($this->data[0]);

            $this->pedido_id = $pedido_id;
            $tid = $this->data[0]['pedido_pay_code']; //TID que retornou quando a transacao foi criada.
            $cielo_numero = "$this->pay_user"; //Número de filiação da cielo, neste caso e o exemplo da homologacao
            $chave_cielo = "$this->pay_key"; // Chave de filiaçãoo da cielo exemplo da homologacao

$string = <<<XML
<?xml version="1.0" encoding="ISO-8859-1"?> 
<requisicao-consulta id="$id" versao="1.1.1">
<tid>$tid</tid>
<dados-ec>
<numero>$cielo_numero</numero>
<chave>$chave_cielo</chave>
</dados-ec>
</requisicao-consulta>
XML;

            if ($this->pay_pass == '2') {
                $url = 'https://qasecommerce.cielo.com.br/servicos/ecommwsec.do'; // url homologação
            } else {
                $url = 'https://ecommerce.cbmp.com.br/servicos/ecommwsec.do'; // url producao
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'mensagem=' . $string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 40);
            curl_setopt($ch, CURLOPT_CAINFO, "app/helpers/cielo/ssl/vericert.crt");
            curl_setopt($ch, CURLOPT_SSLVERSION, 4);

            $string = curl_exec($ch);
            curl_close($ch);
            $xml = @simplexml_load_string($string);          
 
            if ($xml->tid AND $xml->captura->codigo == '6' AND $xml->autorizacao->codigo == '6') {
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
                $total = $visa->moeda( $this->pedido_total_frete );
                $visa->valor_parcela =  $visa->moeda( $total / $node->{'parcelas'} );
                $bandeira = ucfirst($node->{'bandeira'}[0]);

                $obs = "<strong>Forma de pagamento:</strong> $bandeira <Br>";
                $obs .= "<strong>Parcelas:</strong> " . $node->{'parcelas'} . " x " . ($visa->valor_parcela) . " = ".  $total  ." ***<Br>";
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
		if($this->pedido_status <> 3){
	        $this->update('pedido')
	            ->set(array('pedido_status'), array(7))
	            ->where("pedido_id = $pedido_id")
	            ->execute();
		}
            }
        } else {
            echo 'Pedido expirado';
        }
                $this->notificarAdmin();
		$this->notificarCliente();
        //echo '<pre>', print_r($xml), '<br>';
       echo  "<script> window.parent.location = '$this->baseUri/cliente/pedido/$pedido_id/'; </script>";
    }

    public function notificarAdmin()
    {
        $this->select()
            ->from('pedido')
            ->join('cliente', 'cliente_id = pedido_cliente', 'INNER')
            ->join('lista', 'lista_pedido = pedido_id', 'INNER')
            ->where("pedido_id = $this->pedido_id")
            ->groupby('pedido_id')
            ->execute();
        if ($this->result()) {
            $this->cut('lista_title', 60, '');
            $cliente_email = $this->data[0]['cliente_email'];
            $cliente_nome = $this->data[0]['cliente_nome'];
            $this->lista_title = $this->data[0]['lista_title'];
            $this->pedido_status = preg_replace($this->status_pat, $this->status_rep, $this->pedido_status);
            $body = '<html><body>';
            $body .= '<h1 style="font-size:15px;">Status do pedido ' . $this->pedido_id . ' foi atualizado!</h1>';
            $body .= '<table style="border-color: #666; font-size:11px" cellpadding="10">';
            $body .= '<tr style="background: #fff;"><td><strong>Data:</strong> </td><td>' . date('d/m/Y h:s') . '</td></tr>';
            $body .= '<tr style="background: #eee;"><td><strong>Número do Pedido:</strong> </td><td>' . $this->pedido_id . '</td></tr>';
            $body .= '<tr style="background: #fff;"><td><strong>Status do Pedido:</strong> </td><td>' . $this->pedido_status . '</td></tr>';
            $body .= '<tr style="background: #eee;"><td><strong>Resumo do Pedido:</strong> </td><td>' . $this->lista_title . '...</td></tr>';
            $body .= '<tr style="background: #fff;"><td><strong>Cliente:</strong> </td><td>' . $cliente_nome . '...</td></tr>';
            $body .= '</table>';
            $body .= '<br/><br/>';
            $body .= '</body></html>';
            $m = new sendmail;
            $n = array(
                'subject' => utf8_decode( "Status do Pedido Nº $this->pedido_id Atualizado"),
                'body' => $body);
            $m->sender($n);
        }
    }

    public function notificarCliente()
    {
        $this->select()
            ->from('pedido')
            ->join('cliente', 'cliente_id = pedido_cliente', 'INNER')
            ->join('lista', 'lista_pedido = pedido_id', 'INNER')
            ->where("pedido_id = $this->pedido_id")
            ->groupby('pedido_id')
            ->execute();
        if ($this->result()) {
            $this->cut('lista_title', 60, '');
            $cliente_email = $this->data[0]['cliente_email'];
            $cliente_nome = $this->data[0]['cliente_nome'];
            $this->lista_title = $this->data[0]['lista_title'];
            $this->pedido_status = preg_replace($this->status_pat, $this->status_rep, $this->pedido_status);
            $body = '<html><body>';
            $body .= '<h1 style="font-size:15px;">Olá ' . $cliente_nome . ', o status do seu pedido foi atualizado!</h1>';
            $body .= '<table style="border-color: #666; font-size:11px" cellpadding="10">';
            $body .= '<tr style="background: #fff;"><td><strong>Data:</strong> </td><td>' . date('d/m/Y h:s') . '</td></tr>';
            $body .= '<tr style="background: #eee;"><td><strong>Número do Pedido:</strong> </td><td>' . $this->pedido_id . '</td></tr>';
            $body .= '<tr style="background: #fff;"><td><strong>Status do Pedido:</strong> </td><td>' . $this->pedido_status . '</td></tr>';
            $body .= '<tr style="background: #eee;"><td><strong>Resumo do Pedido:</strong> </td><td>' . $this->lista_title . '...</td></tr>';
            $body .= '</table>';
            $body .= '<br/><br/>';
            $body .= "<a href='$this->baseUri/cliente/'>Acesse área do cliente em nosso site para ver mais detalhes.</a>";
            $body .= '<br/><br/>';
            $body .= '</body></html>';
            $m = new sendmail;
            $n = array(
                'email' => "$cliente_email",
                'subject' => utf8_decode( "Status do Pedido Nº $this->pedido_id Atualizado"),
                'body' => utf8_decode($body)
                    );
            $m->sender($n);
        }
    }

    public function notificarErro($body){
        $m = new sendmail;
        $n = array(
            'subject' => "Erro no retorno de dados",
            'body' => $body
        );
        $m->sender($n);
    }

    public function _money($val) {
        return @number_format($val, 2, ",", ".");
    }
}
/* end file */
