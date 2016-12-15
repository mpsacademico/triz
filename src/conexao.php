<?php
function nconn(){
	$conn = null;
	try {
		$conn = new PDO("mysql:host=localhost;dbname=triz;charset=utf8", "root", "");	
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}catch(PDOException $ex){
		echo "<h1>Erro 'conexao.php': " . $ex->getMessage() . "</h1>";		
		/*echo "<script>alert('Desculpe-nos. Serviço temporariamente indisponível!');</script>";*/
		exit;
    }	
	return $conn;	
}
?>
