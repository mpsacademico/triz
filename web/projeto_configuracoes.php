<?php

$projeto->match('/{dominio}/configuracoes/{secao}', function($dominio, $secao) use($app) {			
	$rs = rproj($dominio);
	return $app['twig']->render('page_projeto_d_configuracoes.html',array("p"=>$rs,"secao"=>$secao));
})
->value('secao', 'sobre')
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/configuracoes/visibilidade/editar/{estado}', function($dominio, $estado) use($app) {	
	if($estado=="privado"){
		$estado = 1;
	}else{
		$estado = 2;
	}	
	try {
		$conn = nconn();		
		$sql = "UPDATE tz_projeto SET visibilidade = :estado WHERE dominio = :dominio;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':estado', $estado);
		$stmt->bindParam(':dominio', $dominio);
		$e = $stmt->execute();		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	return $app->redirect("/projeto/$dominio/configuracoes/visibilidade");
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/configuracoes/desativacao/concluir', function($dominio) use($app) {	
	try {
		$conn = nconn();		
		$sql = "UPDATE tz_projeto SET situacao = 1 WHERE dominio = :dominio;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':dominio', $dominio);
		$e = $stmt->execute();		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	return $app->redirect("/projeto/$dominio/configuracoes");
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/configuracoes/cancelamento/cancelar', function($dominio) use($app) {	
	try {
		$conn = nconn();		
		$sql = "UPDATE tz_projeto SET situacao = 2 WHERE dominio = :dominio;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':dominio', $dominio);
		$e = $stmt->execute();		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	return $app->redirect("/projeto/$dominio/configuracoes");
})
->before($protector)
->before($auzeitor);
?>