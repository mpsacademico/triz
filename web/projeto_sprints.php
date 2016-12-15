<?php
$projeto->match('/{dominio}/sprints', function($dominio) use($app) {	
	$p = rproj($dominio);
	$sa = array();
	$s = array();
	try {
		$conn = nconn();
		$sql = "SELECT *, TIMESTAMPDIFF(DAY , ts_inicio, NOW()) AS dias FROM tz_sprint WHERE estado = 0 AND id_projeto = ".$p['id_projeto'].";";
		$stmt = $conn->prepare($sql);		
		$e = $stmt->execute();		
		$sa = $stmt->fetch(PDO::FETCH_ASSOC);	
		$sql = "SELECT *, TIMESTAMPDIFF(DAY , ts_inicio, ts_termino) AS dias FROM tz_sprint WHERE estado = 1 AND id_projeto = ".$p['id_projeto']." ORDER BY id_sprint DESC;";
		$stmt = $conn->prepare($sql);		
		$e = $stmt->execute();		
		$s = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }

	return $app['twig']->render('page_projeto_d_sprints.html',array("p"=>$p,"sa"=>$sa,"s"=>$s));
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/sprints/iniciar', function($dominio) use($app) {	
	$p = rproj($dominio);
	try {
		$conn = nconn();
		$sql = "INSERT INTO tz_sprint (ts_inicio, estado, id_projeto) VALUES (CURRENT_TIMESTAMP, 0, ".$p['id_projeto'].");";
		$stmt = $conn->prepare($sql);		
		$e = $stmt->execute();		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	return $app->redirect("/projeto/$dominio/sprints");
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/sprints/encerrar', function($dominio) use($app) {	
	$p = rproj($dominio);
	try {
		$conn = nconn();
		$sql = "UPDATE tz_sprint SET ts_termino = CURRENT_TIMESTAMP, estado = 1 WHERE estado = 0 AND id_projeto = ".$p['id_projeto'].";";
		$stmt = $conn->prepare($sql);		
		$e = $stmt->execute();		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	return $app->redirect("/projeto/$dominio/sprints");
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/sprints/atribuir/{id}', function($dominio, $id) use($app) {	
	$p = rproj($dominio);
	$hs = array();
	$hsprint = array();
	try {
		$conn = nconn();
		$sql = "SELECT h.*, c.nome, c.sobrenome FROM tz_historia AS h, tz_conta_usuario AS c WHERE h.id_conta_usuario = c.id_conta_usuario AND id_projeto = ".$p['id_projeto']." AND id_historia NOT IN (SELECT id_historia FROM tz_historia_sprint) ORDER BY h.ts_criacao DESC;";
		$stmt = $conn->prepare($sql);		
		$e = $stmt->execute();
		$hs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$sql = "SELECT hs.*, h.*, h.estado AS e, s.*, c.nome, c.sobrenome FROM tz_historia_sprint AS hs, tz_historia AS h, tz_sprint AS s, tz_conta_usuario AS c WHERE hs.id_historia = h.id_historia AND hs.id_sprint = s.id_sprint AND hs.id_atribuidor = c.id_conta_usuario AND hs.id_sprint = $id;";
		$stmt = $conn->prepare($sql);		
		$e = $stmt->execute();
		$hsprint = $stmt->fetchAll(PDO::FETCH_ASSOC);		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
    return $app['twig']->render('page_projeto_d_sprints_atribuir.html',array("p"=>$p,"hs"=>$hs,"sprint"=>$id,"hsprint"=>$hsprint));	
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/sprints/atribuir/{sprint}/{historia}', function($dominio, $sprint, $historia) use($app) {	
	$p = rproj($dominio);
	$hs = array();
	$usuario = $app['session']->get('conta_usuario');
	$id_conta_usuario = $usuario['id_conta_usuario'];
	try {
		$conn = nconn();
		$sql = "INSERT INTO tz_historia_sprint (id_sprint, id_historia, id_atribuidor) VALUES ($sprint, $historia, $id_conta_usuario);";
		$stmt = $conn->prepare($sql);		
		$e = $stmt->execute();					
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
    return $app->redirect("/projeto/$dominio/sprints/atribuir/$sprint");
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/sprints/ver/{sprint}', function($dominio, $sprint) use($app) {	
	$p = rproj($dominio);
	
	$hsprint = array();
	try {
		$conn = nconn();		
		$sql = "SELECT hs.*, h.*, s.*, c.nome, c.sobrenome FROM tz_historia_sprint AS hs, tz_historia AS h, tz_sprint AS s, tz_conta_usuario AS c WHERE hs.id_historia = h.id_historia AND hs.id_sprint = s.id_sprint AND hs.id_atribuidor = c.id_conta_usuario AND hs.id_sprint = $sprint;";
		$stmt = $conn->prepare($sql);		
		$e = $stmt->execute();
		$hsprint = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
    return $app['twig']->render('page_projeto_d_sprints_ver.html',array("p"=>$p,"sprint"=>$sprint,"hsprint"=>$hsprint));	
})
->before($protector)
->before($auzeitor);
?>