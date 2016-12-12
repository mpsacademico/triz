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
})
->before($nosesius);

$saticon->match('/revogar/{chave}', function($chave) use($app) {
    return $app->redirect('/');
})
->before($nosesius);

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

$saticon->get('/recuperar/senha/{chave}', function($chave) use($app) {	
    try {
		$conn = nconn(); //futuramente, fazer com que essa chave expire após 24 horas
		$sql = "SELECT * FROM tz_cha_recuperacao WHERE estado = 0 AND cha_recuperacao = :cha;";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':cha', $chave);
		$stmt->execute();
		$rs = $stmt->fetch(PDO::FETCH_ASSOC);		
		if($stmt->rowCount()==1){
			return $app['twig']->render('form_redefinir_senha.html',array("cha"=>$chave));
		}
		}catch(PDOException $ex){
			echo "Erro: " . $ex->getMessage();
    }	
    return $app->redirect('/');
})
->before($nosesius);

$saticon->post('/recuperar/senha/{chave}', function($chave) use($app) {	
	$msg = "";
	$tipo = "fatal";
    try {
		$conn = nconn(); //futuramente, fazer com que essa chave expire após 24 horas
		$sql = "SELECT * FROM tz_cha_recuperacao WHERE estado = 0 AND cha_recuperacao = :cha;";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':cha', $chave);
		$stmt->execute();
		$rs = $stmt->fetch(PDO::FETCH_ASSOC);			
		if($stmt->rowCount()==1){
			$id_cha =  $rs['id_cha_recuperacao'];	
			$id = $rs['id_conta_usuario'];	
			$cha = $_POST['cha'];
			$nova = $_POST['nova'];
			$confirma = $_POST['confirma'];
			if($cha==$chave){
				if($nova==$confirma){
					try {		
						$sql = "SELECT salt_senha FROM tz_conta_usuario WHERE id_conta_usuario = $id;";
						$stmt = $conn->prepare($sql);
						$stmt->execute();
						$rs = $stmt->fetch(PDO::FETCH_ASSOC);
						$salt = $rs['salt_senha'];
						$sql = "UPDATE tz_conta_usuario SET senha = '".hash('sha512',$nova.$salt)."' WHERE id_conta_usuario = $id;";
						$stmt = $conn->prepare($sql);
						$stmt->execute();
						$sql = "UPDATE tz_cha_recuperacao SET estado = 1 WHERE id_cha_recuperacao = $id_cha;";
						$stmt = $conn->prepare($sql);
						$stmt->execute();						
						return $app->redirect('/entrar');
					}catch(PDOException $ex){
						echo "Erro: " . $ex->getMessage();
					}
				}else{
					$msg = "As senhas precisam ser iguais";
					$tipo = "normal";						
				}
			}
		}
	}catch(PDOException $ex){
			echo "Erro: " . $ex->getMessage();
    }	
    return $app['twig']->render('form_redefinir_senha.html',array("cha"=>$chave,"msg"=>$msg,"tipo"=>$tipo));
})
->before($nosesius);

$app->match('/ativacao', function() use($app) {
    return $app['twig']->render('page_ativacao.html', array("teste"=>"teste"));
})
->before($nosesius);

return $saticon;
?>