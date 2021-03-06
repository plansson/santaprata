<?php

require_once 'ITemplate.php';

class T1 extends PHPFrodo implements ITemplate
{

    public function __construct()
    {
        $sid = new Session;
        $sid->start();
        $this->database();
        if ($sid->check() && $sid->getNode('cliente_id') >= 1) {
            $this->cliente_fullnome = (string)$sid->getNode('cliente_fullnome');
            $this->cupom = $_SESSION['FLUX_CUPOM_ID'];

            $this->select()
                ->from('cupom')
                ->where("cupom_alfa = '$this->cupom' AND cupom_status = 0")
                ->execute();
            if($this->result()) {
                $this->map($this->data[0]);
            }
        }
    }

    public function getMensagem()
    {
        $data = DateTime::createFromFormat('Y-m-d H:i:s',$this->cupom_validade);
        $htmlContent = '<div style="font-size:15px;font-family:Calibri,sans-serif;">
    <p><span>Parabéns ' . $this->cliente_fullnome .'</span></p>
    <p><span>Você acaba de receber 5% de comissão em cupom de desconto para compras futuras referente a compra efetuada em nosso site.</span></p>
    <div class="coupon" style="border: 5px dotted #bbb; width: 80%;border-radius: 15px;margin: 0 auto; max-width: 600px;">
        <div class="container" align="center" style="padding: 2px 16px;background-color: #f1f1f1;font-size: larger;">
            <p><span class="promo">' . $this->cupom_alfa . '</span></p>
        </div>
    </div>
    <p><u><span>Observações importantes:</span></u></p>
    <p>
        <em>
            <span style="font-size:13px;font-family:Calibri,sans-serif;color:black;">¹ O cupom terá validade até ' . $data->format('d/m/Y') .'</span>
        </em>
        <span style="font-size:13px;font-family:Calibri,sans-serif;color:black;">
        <br>
        <em>
            <span style="font-family:Calibri,sans-serif;">² Cupom não cumulativo e não aplicável aos nossos produtos em oferta.</span>
         </em>
     </span>
    </p>

    <p><em><span style="font-size:13px;font-family:Calibri,sans-serif;color:black;">Cordialmente,</span></em></p>
</div>
<div align="center" style="font-size:12px;font-family:Calibri, sans-serif;color:#979797;text-transform: uppercase;">
    <img src="cid:logo" alt="Logo">
    <p><strong><span>Rua Floriano Peixoto, 26</span></strong></p>
    <p><strong><span>Centro. Juazeiro-BA. Cep: 48.901-380</span></strong></p>
    <p><strong><span>CNPJ: 18.862.993/0001-76</span></strong></p>
    <p><strong><span>Todos os Direitos Reservados</span></strong></p>
    <p><strong><span><a href="https://www.instagram.com/santapratahomebr/">@santapratahomebr</a> - <a href="https://www.santapratahome.com.br/">santapratahome</a></span></strong></p>
</div>
';

        return $htmlContent;
    }
}