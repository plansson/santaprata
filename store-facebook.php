<?php

// Dados de conexao com o banco
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'santapra_awilie');
define('MYSQL_PASSWORD', '20202020SP$');
define('MYSQL_DB_NAME', 'santapra_loja');
define('HOME', 'https://santapratahome.com.br');

try
{
    // Realiza a conexao com o banco - PDO
    $PDO = new PDO('mysql:host=' . MYSQL_HOST . ';dbname=' . MYSQL_DB_NAME, MYSQL_USER, MYSQL_PASSWORD);
    $PDO->exec("set names utf8");
}
catch (PDOException $e)
    {
        echo 'Erro ao conectar com o banco: ' . $e->getMessage();
    }
    
    
// Realiza a consulta no banco de todas as postagens ativas
$sql = "SELECT
		  i.item_id 
		, i.item_title 
		, i.item_desc 
		, i.item_preco  
		, i.item_estoque  
		, i.item_categoria  
		, i.item_sub 
		, i.item_url 
		, f.foto_url 
		, f.foto_item 
		, c.categoria_id  as categoria_id
		, c.categoria_url  as categoria_url
		, s.sub_id  as sub_id
		, s.sub_url  as sub_url
    		FROM
		item i
    		INNER JOIN categoria c ON(i.item_categoria = c.categoria_id)
    		INNER JOIN sub s ON(i.item_sub = s.sub_id)
    		INNER JOIN foto f ON(i.item_id = f.foto_item)
         group by i.item_id  order by i.item_id ASC
     ";


$result = $PDO->query($sql);
$rows = $result->fetchAll(PDO::FETCH_ASSOC);





// Gera o arquivo XML do sitemap
$xml = '<?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
                <channel>
                    <title>Santa Prata</title>
                    <link>santapratahome.com.br</link>
                    <description>Decorações</description>';
                    
            foreach($rows as $v){
                $xml .='
                
                <item>
                    <g:id>'.$v['item_id'].'</g:id>
                    <g:title>'.ucfirst(strtolower($v['item_title'])).'</g:title>
                    <g:description>'.ucfirst(strtolower($v['item_title'])).'</g:description>
                    <g:link>'.HOME.'/produto/'.$v['categoria_url'].'/'.$v['sub_url'].'/'.$v['item_url'].'/'.$v['item_id'].'</g:link>
                    <g:image_link>'.HOME.'/app/fotos/'.$v['foto_url'].'</g:image_link>
                    <g:brand>santapratahome</g:brand>
                    <g:condition>new</g:condition>
                    <g:availability>in stock</g:availability>
                    <g:inventory>'.$v['item_estoque'].'</g:inventory>
                    <g:price>'.$v['item_preco'].'</g:price>
                </item>';
            }
        $xml .= '
            </channel>
            </rss>';
        
        

// Abre o arquivo ou tenta cria-lo se ele não exixtir
$arquivo = fopen('sitemap.xml', 'w');
if (fwrite($arquivo, $xml)) {
   /// echo "Arquivo sitemap.xml criado com sucesso";
} else {
  //   echo "Não foi possível criar o arquivo. Verifique as permissões do diretório.";
}
fclose($arquivo);



?>