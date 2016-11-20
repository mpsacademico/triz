<?php
$saticon = $app['controllers_factory'];

$saticon->match('/ativar/{chave}', function($chave) use($app) {
	try {
		$conn = nconn();
		$sql = "SELECT * FROM tz_cha_ativacao WHERE cha_ativacao = :chave AND estado = 0;";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':chave', $chave);
		$stmt->execute();
		if($stmt->rowCount()==1){
			$rs = $stmt->fetch(PDO::FETCH_ASSOC);
			$id_cha_ativacao = $rs['id_cha_ativacao'];
			$ts_acao = date("Y-m-d H:i:s");
			$id_conta_usuario = $rs['id_conta_usuario'];		
			$sql = "UPDATE tz_cha_ativacao SET estado = 1, ts_acao = :ts_acao WHERE id_cha_ativacao = :id_cha_ativacao; UPDATE tz_conta_usuario SET estado = 1 WHERE id_conta_usuario = :id_conta_usuario;";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':id_cha_ativacao', $id_cha_ativacao);
			$stmt->bindParam(':ts_acao', $ts_acao);
			$stmt->bindParam(':id_conta_usuario', $id_conta_usuario);
			$stmt->execute();		
			if($stmt->rowCount()>0){
				return $app->redirect('/entrar');
			}
		}
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	$conn = null;	
	return $app->redirect('/');
});

$saticon->match('/revogar/{chave}', function($chave) use($app) {
    return $app->redirect('/');
});

$app->match('/ativacao/reenviar', function() use($app) {
	if (!(null === $user = $app['session']->get('ativacao'))) {
		$ativacao = $app['session']->get('ativacao');		
		$id_conta_usuario = $ativacao['id_conta_usuario'];
		$nome = $ativacao['nome'];
		$email = $ativacao['email'];
		$ts_criacao = $ativacao['ts_criacao'];
		$cha_ativacao = $ativacao['cha_ativacao'];
		if(strcmp($_SERVER['SERVER_NAME'],"localhost") != 0){
			require 'email_ativacao_conta.php';			
		}
		$app['session']->clear();
		return $app['twig']->render('page_ativacao.html', array('msgs' => array("E-mail enviado com sucesso!")));
	}		
	return $app->redirect('/');
});


$app->match('/ativacao', function() use($app) {
    return $app['twig']->render('page_ativacao.html', array("teste"=>"teste"));
});

return $saticon;
?>