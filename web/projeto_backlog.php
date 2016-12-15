<?php
$projeto->match('/{dominio}/backlog', function($dominio) use($app) {	
	$hs = array();
	$p = rproj($dominio);	
	try {
		$conn = nconn();
		$sql = "SELECT h.*, c.nome, c.sobrenome FROM tz_historia AS h, tz_conta_usuario AS c WHERE h.id_conta_usuario = c.id_conta_usuario AND id_projeto = ".$p['id_projeto']." ORDER BY h.ts_criacao DESC;";
		$stmt = $conn->prepare($sql);		
		$e = $stmt->execute();
		$hs = $stmt->fetchAll(PDO::FETCH_ASSOC);			
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app['twig']->render('page_projeto_d_backlog.html',array("p"=>$p,"hs"=>$hs));
})
->before($protector)
->before($auzeitor);

$projeto->get('/{dominio}/backlog/historia/criar', function($dominio) use($app) {	
	$p = rproj($dominio);
	return $app['twig']->render('form_projeto_d_backlog_criar.html',array("p"=>$p));
})
->before($protector)
->before($auzeitor);

$projeto->post('/{dominio}/backlog/historia/criar', function($dominio) use($app) {	
	$p = rproj($dominio);
	$id_projeto = $p['id_projeto'];
	$descricao = $_POST['descricao'];
	$complexidade = $_POST['complexidade'];
	$estimativa = $_POST['estimativa'];
	$estado = 0;
	$usuario = $app['session']->get('conta_usuario');
	$id_conta_usuario = $usuario['id_conta_usuario'];
	try {
		$conn = nconn();
		$sql = "INSERT INTO tz_historia (descricao, complexidade, estimativa, id_projeto, id_conta_usuario) VALUES (:descricao, :complexidade, :estimativa, :id_projeto, :id_conta_usuario);";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':descricao', $descricao);
		$stmt->bindParam(':complexidade', $complexidade);
		$stmt->bindParam(':estimativa', $estimativa);
		$stmt->bindParam(':id_projeto', $p['id_projeto']);
		$stmt->bindParam(':id_conta_usuario', $id_conta_usuario);	
		$e = $stmt->execute();		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
    return $app->redirect("/projeto/$dominio/backlog");
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/backlog/historia/{id}', function($dominio, $id) use($app) {	
	$p = rproj($dominio);
	$hs = array();	
	$ts = array();
	$is = array();
	try {
		$conn = nconn();
		$sql = "SELECT h.*, c.nome, c.sobrenome FROM tz_historia AS h, tz_conta_usuario AS c WHERE h.id_conta_usuario = c.id_conta_usuario AND id_projeto = ".$p['id_projeto']." AND id_historia = :id;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':id', $id);
		$e = $stmt->execute();
		$h = $stmt->fetch(PDO::FETCH_ASSOC);
		$sql = "SELECT t.id_integrante, t.id_time, t.funcao, t.estado, t.papel, c.id_convite, c.ts_realizacao, c.ts_resposta, cu.id_conta_usuario, cu.nome, cu.sobrenome, cu.email FROM tz_integrante AS t, tz_convite AS c, tz_conta_usuario AS cu WHERE t.id_convite = c.id_convite AND c.id_convidado = cu.id_conta_usuario AND c.id_projeto = ".$p["id_projeto"]." AND cu.id_conta_usuario NOT IN (SELECT id_conta_usuario FROM tz_historia_usuario WHERE id_historia = $id);";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$ts = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$sql = "SELECT * FROM tz_historia_usuario AS hu, tz_conta_usuario AS cu, tz_historia AS h, tz_perfil AS p WHERE hu.id_conta_usuario = cu.id_conta_usuario AND hu.id_historia = h.id_historia AND cu.id_conta_usuario = p.id_conta_usuario AND hu.id_historia = $id;";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$is = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app['twig']->render('page_projeto_d_backlog_historia.html',array("p"=>$p,"h"=>$h,"ts"=>$ts,"id"=>$id,"is"=>$is));
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/backlog/historia/{id}/concluir', function($dominio, $id) use($app) {	
	$p = rproj($dominio);	
	try {
		$conn = nconn();
		$sql = "UPDATE tz_historia SET estado = 1 WHERE id_historia = :id;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':id', $id);
		$e = $stmt->execute();		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app->redirect("/projeto/$dominio/backlog/historia/$id");
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/backlog/historia/{id}/{u}', function($dominio, $id, $u) use($app) {	
	$p = rproj($dominio);	
	$usuario = $app['session']->get('conta_usuario');
	$id_conta_usuario = $usuario['id_conta_usuario'];
	try {
		$conn = nconn();
		$sql = "INSERT INTO tz_historia_usuario (id_conta_usuario, id_historia, id_atribuidor) VALUES ($u, $id, $id_conta_usuario);";
		$stmt = $conn->prepare($sql);		
		$e = $stmt->execute();		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app->redirect("/projeto/$dominio/backlog/historia/$id");
})
->before($protector)
->before($auzeitor);
?>