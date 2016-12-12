<?php

$projeto->get('/{dominio}/configuracoes/identidade', function($dominio) use($app) {	
	$rs = rproj($dominio);
	$ts = array();
	try {
		$conn = nconn();		
		$sql = "SELECT * FROM tz_tag_projeto WHERE id_projeto = ".$rs['id_projeto']." ORDER BY id_tag_projeto DESC;";
		$stmt = $conn->prepare($sql);	
		$e = $stmt->execute();		
		$ts = $stmt->fetchAll(PDO::FETCH_ASSOC);		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	return $app['twig']->render('page_projeto_d_configuracoes.html',array("p"=>$rs,"secao"=>"identidade","ts"=>$ts));
})
->before($protector)
->before($auzeitor);

$projeto->post('/{dominio}/configuracoes/identidade', function($dominio) use($app) {	
	$rs = rproj($dominio);	
	$usuario = $app['session']->get('conta_usuario');
	$id_conta_usuario = $usuario['id_conta_usuario'];
	try {
		$conn = nconn();		
		$sql = "INSERT INTO tz_tag_projeto (tag, id_projeto, id_conta_usuario) VALUES (:tag, ".$rs['id_projeto'].", ".$id_conta_usuario.");";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':tag', $_POST['tag']);
		$e = $stmt->execute();		
		$ts = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	return $app->redirect("/projeto/$dominio/configuracoes/identidade");
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/configuracoes/identidade/tag/remover/{id}', function($dominio, $id) use($app) {	
	$rs = rproj($dominio);		
	try {
		$conn = nconn();		
		$sql = "DELETE FROM tz_tag_projeto WHERE id_tag_projeto = :id;";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':id', $id);
		$e = $stmt->execute();		
		$ts = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	return $app->redirect("/projeto/$dominio/configuracoes/identidade");
})
->before($protector)
->before($auzeitor);

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