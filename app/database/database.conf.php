<?php
# Configuração dos bancos de dados suportados no PDO
global $databases;
$databases = array(
    # MYSQL
    'default' => array
        (
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'santapra_loja',
        'user' => 'santapra_awilie',
        'password' => '20202020SP$',
        'limite_produto' => 1500, //limite de produtos cadastrados
        'emailAdmin' => 'awiliecosta@gmail.com'
    )
);
/* end file */
