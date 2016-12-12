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
			$id_projeto = $conn->lastInsertId();
			$sql = "INSERT INTO tz_time (id_projeto, nome, ordem, visibilidade, ts_criacao) VALUES (:id_projeto, '1', '1', '1', CURRENT_TIMESTAMP)";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':id_projeto', $id_projeto);
			$stmt->execute();
			$id_time = $conn->lastInsertId();
			$sql = "INSERT INTO tz_convite (id_conta_usuario, id_convidado, id_projeto, ts_realizacao, estado) VALUES ($id, $id, $id_projeto, CURRENT_TIMESTAMP, 2);";
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			$id_convite = $conn->lastInsertId();
			$sql = "INSERT INTO tz_integrante (id_time, id_convite, estado, papel) VALUES ($id_time, $id_convite, 1, 3);";
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			return $app->redirect("/projeto/$dominio");
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
	$rs = rproj($dominio);
	if($rs==false){
		$app->abort(404, 'O projeto "'.$dominio.'" não existe!');
	}
	$dom = $dominio;
	$usuario = $app['session']->get('conta_usuario');
	$id = $usuario['id_conta_usuario'];	
	try {
		$conn = nconn();
		$sql = "SELECT * FROM tz_integrante AS i, tz_convite AS c, tz_projeto AS p WHERE i.id_convite = c.id_convite AND c.id_projeto = p.id_projeto AND i.estado = 1 AND p.dominio = '$dom' AND c.id_convidado = $id AND c.estado = 2;";		
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$r = $stmt->fetch(PDO::FETCH_ASSOC);
		if($r == false){
			if($rs['visibilidade']==2){
				return rpgeral($dominio, $app);
			}elseif($rs['visibilidade']==1){
				$app->abort(401, 'Acesso não autorizado!');
			}
		}	
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app['twig']->render('page_projeto_d_geral.html',array("p"=>$rs));
})
->before($protector);

$projeto->match('/{dominio}/visualizar', function($dominio) use($app) {
	return rpgeral($dominio, $app);
})
->before($protector)
->before($auzeitor);

function rpgeral($dominio, $app){
	$rs = rproj($dominio);	
	$ts = array();
	$is = array();
	try {
		$conn = nconn();		
		$sql = "SELECT * FROM tz_tag_projeto WHERE id_projeto = ".$rs['id_projeto'].";";
		$stmt = $conn->prepare($sql);		
		$stmt->execute();
		$ts = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$sql = "SELECT t.id_integrante, t.id_time, t.funcao, t.estado, t.papel, c.id_convite, c.ts_realizacao, c.ts_resposta, cu.id_conta_usuario, cu.nome, cu.sobrenome, cu.email, p.* FROM tz_integrante AS t, tz_convite AS c, tz_conta_usuario AS cu, tz_perfil AS p WHERE t.id_convite = c.id_convite AND c.id_convidado = cu.id_conta_usuario AND c.id_convidado = p.id_conta_usuario AND c.id_projeto = ".$rs["id_projeto"]." ORDER BY cu.nome ASC;";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$is = $stmt->fetchAll(PDO::FETCH_ASSOC);		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app['twig']->render('page_projeto_d_visualizar.html',array("p"=>$rs,"ts"=>$ts,"is"=>$is));
}

$projeto->match('/{dominio}/backlog', function($dominio) use($app) {	
	$p = rproj($dominio);
	return $app['twig']->render('page_projeto_d_backlog.html',array("p"=>$p));
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/releases', function($dominio) use($app) {	
	$p = rproj($dominio);
	return $app['twig']->render('page_projeto_d_releases.html',array("p"=>$p));
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/sprints', function($dominio) use($app) {	
	$p = rproj($dominio);
	return $app['twig']->render('page_projeto_d_sprints.html',array("p"=>$p));
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/quadro', function($dominio) use($app) {	
	$p = rproj($dominio);
	return $app['twig']->render('page_projeto_d_quadro.html',array("p"=>$p));
})
->before($protector)
->before($auzeitor);

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
		$sql = "SELECT p.*, c.nome, c.sobrenome FROM tz_projeto AS p, tz_conta_usuario AS c WHERE p.id_conta_usuario = c.id_conta_usuario AND c.id_conta_usuario = :id $s ORDER BY p.ts_criacao DESC;";
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

function rproj($dominio){
	try {
		$conn = nconn();		
		$sql = "SELECT p.*, c.nome, c.sobrenome FROM tz_projeto AS p, tz_conta_usuario AS c WHERE p.id_conta_usuario = c.id_conta_usuario AND p.dominio = :dominio;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':dominio', $dominio);
		$stmt->execute();
		$rs = $stmt->fetch(PDO::FETCH_ASSOC);
		if($rs==false) return $rs;
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
		$rs["$cha_v"] = "vi";
		$rs["v"] = $v;
		$rs["s"] = $s;
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	return $rs;
}

require("projeto_membros.php");
require("projeto_relatorios.php");
require("projeto_configuracoes.php");

return $projeto;
?>