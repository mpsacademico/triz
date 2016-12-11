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
	return $app['twig']->render('page_projeto_d.html',array("p"=>$rs));
})
->before($protector);

$projeto->match('/{dominio}/membros', function($dominio) use($app) {
	$rs = rproj($dominio);
	$rsu = 'null';
	$ts = array();
	$email = '';
	if(isset($_GET['email'])){
		try {
			$conn = nconn();		
			$sql = "SELECT c.nome, c.sobrenome, c.email, p.* FROM tz_conta_usuario AS c, tz_perfil AS p WHERE c.id_conta_usuario = p.id_conta_usuario AND c.estado = 1 AND c.email = :email;";
			$stmt = $conn->prepare($sql);		
			$stmt->bindParam(':email', $_GET['email']);
			$stmt->execute();
			$rsu = $stmt->fetch(PDO::FETCH_ASSOC);
			$email = $_GET['email'];
		}catch(PDOException $ex){
			echo "Erro: " . $ex->getMessage();
		}		
	}
	try {
		$conn = nconn();	
		//membros (time)
		$sql = "SELECT * FROM tz_time WHERE id_projeto = :id_projeto;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':id_projeto', $rs['id_projeto']);
		$stmt->execute();
		$rst = $stmt->fetch(PDO::FETCH_ASSOC);		
		//convite
		$sql = "SELECT con.id_convite, con.ts_realizacao, con.estado, cou.id_conta_usuario, cou.nome, cou.sobrenome, uco.id_conta_usuario AS id_convidado, uco.nome AS nomec, uco.sobrenome AS sobrenomec, uco.email AS emailc FROM tz_convite AS con , tz_conta_usuario AS cou, tz_conta_usuario AS uco WHERE con.id_conta_usuario = cou.id_conta_usuario AND con.id_convidado = uco.id_conta_usuario AND con.estado = 1 AND con.id_projeto = :id_projeto ORDER BY con.ts_realizacao DESC;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':id_projeto', $rs['id_projeto']);
		$stmt->execute();
		$cs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		//time de desenvolvimento
		$sql = "SELECT t.id_integrante, t.id_time, t.funcao, t.estado, t.papel, c.id_convite, c.ts_realizacao, c.ts_resposta, cu.id_conta_usuario, cu.nome, cu.sobrenome, cu.email FROM tz_integrante AS t, tz_convite AS c, tz_conta_usuario AS cu WHERE t.id_convite = c.id_convite AND c.id_convidado = cu.id_conta_usuario AND t.papel = 3 AND c.id_projeto = ".$rs["id_projeto"].";";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$ts = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app['twig']->render('page_projeto_d_membros.html',array("p"=>$rs,"m"=>$rst,"u"=>$rsu,"cs"=>$cs,"ts"=>$ts,"email"=>$email));
})
->before($protector);

$projeto->match('/{dominio}/membros/convidar/{id}', function($dominio, $id) use($app) {
	$p = rproj($dominio);
	$usuario = $app['session']->get('conta_usuario');
	$id_conta_usuario = $usuario['id_conta_usuario'];
	try {
		$conn = nconn();
		$sql = "INSERT INTO tz_convite (id_conta_usuario, id_convidado, id_projeto, ts_realizacao, estado) VALUES (:id_conta_usuario, :id_convidado, :id_projeto, CURRENT_TIMESTAMP, 1);";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':id_conta_usuario', $id_conta_usuario);	
		$stmt->bindParam(':id_convidado', $id);	
		$stmt->bindParam(':id_projeto', $p['id_projeto']);
		$e = $stmt->execute();
		return $app->redirect("/projeto/$dominio/membros");
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	return $e;
})
->before($protector);

$projeto->match('/{dominio}/membros/time/{id}', function($dominio, $id) use($app) {
	$p = rproj($dominio);
	if(isset($_POST['nome'])){
		try {
			$conn = nconn();		
			$sql = "UPDATE tz_time SET nome = :nome WHERE id_time = :id_time;";
			$stmt = $conn->prepare($sql);		
			$stmt->bindParam(':nome', $_POST['nome']);
			$stmt->bindParam(':id_time', $id);
			$stmt->execute();		
		}catch(PDOException $ex){
			echo "Erro: " . $ex->getMessage();
		}	
	}
	try {
		$conn = nconn();		
		$sql = "SELECT * FROM tz_time WHERE id_time = :id_time;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':id_time', $id);
		$stmt->execute();
		$rst = $stmt->fetch(PDO::FETCH_ASSOC);			
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app['twig']->render('page_projeto_d_membros_time.html',array("p"=>$p,"t"=>$rst));
})
->before($protector);

$projeto->match('/{dominio}/membros/integrante/{id}', function($dominio, $id) use($app) {
	$p = rproj($dominio);	
	if(isset($_POST['funcao'])){
		try {
			$conn = nconn();		
			$sql = "UPDATE tz_integrante SET funcao = :funcao WHERE id_integrante = :id_integrante;";
			$stmt = $conn->prepare($sql);		
			$stmt->bindParam(':funcao', $_POST['funcao']);	
			$stmt->bindParam(':id_integrante', $id);			
			$stmt->execute();		
		}catch(PDOException $ex){
			echo "Erro: " . $ex->getMessage();
		}	
	}
	try {
		$conn = nconn();		
		$sql = "SELECT t.id_integrante, t.id_time, t.funcao, t.estado, t.papel, c.id_convite, c.ts_realizacao, c.ts_resposta, cu.id_conta_usuario, cu.nome, cu.sobrenome, cu.email FROM tz_integrante AS t, tz_convite AS c, tz_conta_usuario AS cu WHERE t.id_convite = c.id_convite AND c.id_convidado = cu.id_conta_usuario AND t.papel = 3 AND t.id_integrante = :id_integrante;";		
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':id_integrante', $id);
		$stmt->execute();
		$rs = $stmt->fetch(PDO::FETCH_ASSOC);		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app['twig']->render('page_projeto_d_membros_integrante.html',array("p"=>$p,"i"=>$rs));
})
->before($protector);

$projeto->match('/{dominio}/membros/convite/{resposta}', function($dominio, $resposta) use($app) {
	$p = rproj($dominio);	
	$usuario = $app['session']->get('conta_usuario');
	$id_conta_usuario = $usuario['id_conta_usuario'];
	if($resposta=="aceitar"){
		$resposta = 2;
	}elseif($resposta=="recusar"){
		$resposta = 3;
	}else{
		return $app->redirect("/");
	}
	try {
		$conn = nconn();		
		$sql = "SELECT id_convite FROM tz_convite WHERE estado = 1 AND id_projeto = :id_projeto AND id_convidado = :id_convidado;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':id_projeto', $p['id_projeto']);
		$stmt->bindParam(':id_convidado', $id_conta_usuario);
		$stmt->execute();
		$c = $stmt->fetch(PDO::FETCH_ASSOC);
		if($c!=false){
			if($resposta==2){
				$sql = "SELECT * FROM tz_time WHERE id_projeto = :id_projeto;";
				$stmt = $conn->prepare($sql);		
				$stmt->bindParam(':id_projeto', $p['id_projeto']);
				$stmt->execute();
				$t = $stmt->fetch(PDO::FETCH_ASSOC);	
				$sql = "INSERT INTO tz_integrante (id_time, id_convite, estado, papel) VALUES (:id_time, :id_convite, 1, 3);";
				$stmt = $conn->prepare($sql);		
				$stmt->bindParam(':id_time', $t['id_time']);
				$stmt->bindParam(':id_convite', $c['id_convite']);
				$stmt->execute();
				$sql = "UPDATE tz_convite SET ts_resposta = CURRENT_TIMESTAMP, estado = 2 WHERE id_convite = ".$c['id_convite'].";";
				$stmt = $conn->prepare($sql);	
				$stmt->execute();
				return $app->redirect("/projeto/$dominio");
			}elseif($resposta==3){
				$sql = "UPDATE tz_convite SET ts_resposta = CURRENT_TIMESTAMP, estado = 3 WHERE id_convite = ".$c['id_convite'].";";
				$stmt = $conn->prepare($sql);	
				$stmt->execute();
			}
		}		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	return $app->redirect("/");
})
->before($protector);

$projeto->match('/{dominio}/relatorios', function($dominio) use($app) {	
	$rs = rproj($dominio);
	return $app['twig']->render('page_projeto_d_relatorios.html',array("p"=>$rs));
})
->before($protector);

$projeto->match('/{dominio}/relatorios/{tipo}', function($dominio, $tipo) use($app) {	
	require_once __DIR__ . '/../vendor/mpdf/mpdf/mpdf.php';
	$rs = rproj($dominio);	
	$mpdf = new Mpdf();
	$mpdf->WriteHTML($app['twig']->render("page_inicio.html"));
	$mpdf->Output();
	exit;	
})
->before($protector);

$projeto->match('/{dominio}/configuracoes/{secao}', function($dominio, $secao) use($app) {			
	$rs = rproj($dominio);
	return $app['twig']->render('page_projeto_d_configuracoes.html',array("p"=>$rs,"secao"=>$secao));
})
->value('secao', 'sobre')
->before($protector);

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
->before($protector);

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
->before($protector);

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

return $projeto;
?>