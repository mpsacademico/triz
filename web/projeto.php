<?php
$projeto = $app['controllers_factory'];

$projeto->match('/', function() use($app) {
	return $app->redirect('/projeto/listar/andamento');	
})
->before($protector);

$projeto->get('/criar', function() use($app) {	
	return $app['twig']->render('form_projeto_criar.html');
})
->before($protector);

$projeto->post('/criar', function() use($app) {
	
	$titulo = $_POST['titulo'];
	$resumo = $_POST['resumo'];
	$descricao = $_POST['descricao'];
	$dominio = $_POST['dominio'];
	$visibilidade = $_POST['visibilidade'];
	
	try {
		$conn = nconn();
		$sql = "SELECT * FROM tz_projeto WHERE dominio = :dominio;";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':dominio', $dominio);
		$stmt->execute();
		$qt = $stmt->rowCount();		
		if($qt == 0){
			$user = $app['session']->get('conta_usuario');
			$id = $user['id_conta_usuario'];
			$sql = "INSERT INTO tz_projeto (titulo, resumo, descricao, dominio, situacao, visibilidade, estado, ts_criacao, id_conta_usuario) VALUES (:titulo, :resumo, :descricao, :dominio, 0, :visibilidade, 0, CURRENT_TIMESTAMP, :id);";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':titulo', $titulo);
			$stmt->bindParam(':resumo', $resumo);
			$stmt->bindParam(':descricao', $descricao);
			$stmt->bindParam(':dominio', $dominio);
			$stmt->bindParam(':visibilidade', $visibilidade);
			$stmt->bindParam(':id', $id);
			$e = $stmt->execute();		
			return $app->redirect("/projeto/$dominio/geral");
		}else{
			return $app['twig']->render('form_projeto_criar.html', array("post" => $_POST, "erro" => 1));	
		}		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app['twig']->render('form_projeto_criar.html');
})
->before($protector);

$projeto->match('/{dominio}', function($dominio) use($app) {
	try {
		$conn = nconn();		
		$sql = "SELECT p.*, c.nome, c.sobrenome FROM tz_projeto AS p, tz_conta_usuario AS c WHERE p.id_conta_usuario = c.id_conta_usuario AND p.dominio = :dominio;";
		$stmt = $conn->prepare($sql);		
		//$stmt->bindParam(':id', $id);
		$stmt->bindParam(':dominio', $dominio);
		$stmt->execute();
		$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);	
			var_dump($rs);
			//return $app->redirect("membros");
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app['twig']->render('page_projeto_v-dominio.html',array("p"=>$rs[0]));
})
->before($protector);

$projeto->match('membros', function() use($app) {
	
	return "teste";
})
->before($protector);

$projeto->match('/{dominio}/configuracoes/{secao}', function($dominio, $secao) use($app) {
	
	try {
		$conn = nconn();		
		$sql = "SELECT p.*, c.nome, c.sobrenome FROM tz_projeto AS p, tz_conta_usuario AS c WHERE p.id_conta_usuario = c.id_conta_usuario AND p.dominio = :dominio;";
		$stmt = $conn->prepare($sql);		
		//$stmt->bindParam(':id', $id);
		$stmt->bindParam(':dominio', $dominio);
		$stmt->execute();
		$rs = $stmt->fetch(PDO::FETCH_ASSOC);		
		if($rs["visibilidade"]==1){
			$cha_v = "pr";
			$v = "privado";
		}else{
			$cha_v = "pu";
			$v = "público";
		}
		if($rs["situacao"]==0){
			$s = "em andamento";
		}else if($rs["situacao"]==1){
			$s = "desativado";
		}else if($rs["situacao"]==2){
			$s = "cancelado";
		}			
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }

	return $app['twig']->render('page_projeto_d_configuracoes.html',array("p"=>$rs,"secao"=>$secao,"$cha_v"=>"vi","v"=>$v,"s"=>$s));
})
->value('secao', 'sobre')
->before($protector);

$projeto->match('/{dominio}/configuracoes/visibilidade/editar/{estado}', function($dominio, $estado) use($app) {
	try {
		$conn = nconn();		
		$sql = "SELECT p.*, c.nome, c.sobrenome FROM tz_projeto AS p, tz_conta_usuario AS c WHERE p.id_conta_usuario = c.id_conta_usuario AND p.dominio = :dominio;";
		$stmt = $conn->prepare($sql);		
		//$stmt->bindParam(':id', $id);
		$stmt->bindParam(':dominio', $dominio);
		$stmt->execute();
		$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);	
			
			//return $app->redirect("membros");
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
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
->before($protector);

$projeto->match('/listar/{situacao}', function($situacao) use($app) {	
	$user = $app['session']->get('conta_usuario');
	$id = $user['id_conta_usuario'];
	$s = "";
	switch ($situacao) {
		case "andamento":
			$s = "AND p.situacao = 0";
			break;
		case "concluidos":
			$s = "AND p.situacao = 1";
			break;
		case "cancelados":
			$s = "AND p.situacao = 2";
			break;
	}
	try {
		$conn = nconn();
		/*$sql = "SELECT situacao, COUNT(*) AS qt FROM tz_projeto WHERE id_conta_usuario = :id GROUP BY situacao ORDER BY situacao ASC;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		$rsc = $stmt->fetchAll(PDO::FETCH_ASSOC);*/
		$sql = "SELECT p.*, c.nome, c.sobrenome FROM tz_projeto AS p, tz_conta_usuario AS c WHERE p.id_conta_usuario = c.id_conta_usuario AND c.id_conta_usuario = :id $s ORDER BY p.ts_criacao ASC;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app['twig']->render('page_projeto_todos.html',array("projetos"=>$rs));
})
->before($protector);

return $projeto;
?>