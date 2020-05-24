<?php


class Mensagem
{

    private $_msg = null;

    public function __construct(ITemplate $t)
    {
        $this->setMsg($t->getMensagem());
    }

    /**
     * @return null
     */
    public function getMsg()
    {
        return $this->_msg;
    }

    /**
     * @param null $msg
     */
    public function setMsg($msg)
    {
        $this->_msg = $msg;
    }



}