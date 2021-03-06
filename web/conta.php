﻿<?php
$conta = $app['controllers_factory'];

$conta->get('/criar', function() use($app) {
    return $app['twig']->render('form_conta_criar.html');
})
->before(function() use ($app){	
	if(!(null === $app['session']->get('conta_usuario'))){
        return $app->redirect('/');
    }
});

$conta->post('/criar', function() use($app) {

    $nome = $_POST['nome'];
    $sobrenome = $_POST['sobrenome'];
    $dia = $_POST['dia'];
    $mes = $_POST['mes'];
    $ano = $_POST['ano'];
	$genero = $_POST['genero'];
    $email = $_POST['email'];
    $email2 = $_POST['email2'];
	$senha = $_POST['senha'];
	
	if(is_numeric($dia) && is_numeric($ano)){	
		if(checkdate($mes, $dia, $ano)){		
			if($ano < 1900 || $ano > date('Y')){
				$e[] = "Apenas anos entre 1900 e ".date('Y')." são aceitos";
			}else{
				$dt_nascimento = sprintf('%s-%s-%s', $ano, $mes, $dia);    		
			} 
		}else{
			$e[] = "A data é inválida";			
		}
    }else{
    	$e[] = "A data contêm caracteres não permitidos";
    }
    
    if(strcmp($email, $email2) != 0){
    	$e[] = "Os e-mails não coincidem";
    }else{
		try {
			$conn = nconn();
			$stmt = $conn->prepare("SELECT id_conta_usuario FROM tz_conta_usuario WHERE email = :email;"); 
			$stmt->bindParam(':email', $email);
			$stmt->execute();			
			if($stmt->rowCount()!=0){	
				$e[] = "O e-mail informado não pode ser usado";
			}
		}
		catch(PDOException $e) {
			echo "Erro: " . $e->getMessage();
		}
		$conn = null;
	}
	
	if(count($e)>0){		
		return $app['twig']->render('form_conta_criar.html',array('erros'=>$e));
	}
	
    $ts_criacao = time();
	$dt_criacao = date("Y-m-d H:i:s", $ts_criacao);
    $salt_conta = md5($email.$ts_criacao);
    $salt_senha = md5(uniqid(rand(), true));
    $hash_senha = hash('sha512', $senha.$salt_senha);
	
	$estado = 0;
	
	try {
		$conn = nconn();
		$sql = "INSERT INTO tz_conta_usuario (nome,sobrenome,dt_nascimento,sexo,email,senha,salt_conta,salt_senha,ts_criacao,estado) VALUES(:nome, :sobrenome, :dt_nascimento, :sexo, :email, :senha, :salt_conta, :salt_senha, :ts_criacao, :estado);";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':nome', $nome);
		$stmt->bindParam(':sobrenome', $sobrenome);
		$stmt->bindParam(':dt_nascimento', $dt_nascimento);
		$stmt->bindParam(':sexo', $genero);
		$stmt->bindParam(':email', $email);
		$stmt->bindParam(':senha', $hash_senha);
		$stmt->bindParam(':salt_conta', $salt_conta);
		$stmt->bindParam(':salt_senha', $salt_senha);
		$stmt->bindParam(':ts_criacao', $dt_criacao);
		$stmt->bindParam(':estado', $estado);		
		$stmt->execute();
		
		$cha_ativacao = hash( 'sha512', $salt_conta.time() );
		
		$sql = "INSERT INTO tz_cha_ativacao (cha_ativacao, estado, id_conta_usuario) VALUES(:cha_ativacao, 0, :id_conta_usuario);";
		
		$stmt = $conn->prepare($sql);
		$id_conta_usuario = $conn->lastInsertId(); 
		$stmt->bindParam(':cha_ativacao', $cha_ativacao);
		$stmt->bindParam(':id_conta_usuario', $id_conta_usuario);
		$stmt->execute();
		
		$sql = "INSERT INTO tz_perfil (id_conta_usuario) VALUES (:id_conta_usuario);";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':id_conta_usuario', $id_conta_usuario);
		$stmt->execute();
		
		//envia e-mail de ativação de conta se estiver em operação
		if(strcmp($_SERVER['SERVER_NAME'],"localhost") != 0){
			require 'email_ativacao_conta.php';			
		}
		
		return $app->redirect('/ativacao');
		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	$conn = null;
    return $app->redirect('/');
});

$conta->match('/acesso', function () use ($app) {
	$usuario = $app['session']->get('conta_usuario');
	$id_conta_usuario = $usuario['id_conta_usuario'];
	try {
		$conn = nconn();
		$sql = "SELECT ts_entrada, remote_addr FROM tz_log_acesso WHERE id_conta_usuario = :id_conta_usuario ORDER BY ts_entrada DESC LIMIT 10;";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':id_conta_usuario', $id_conta_usuario);
		$stmt->execute();
		$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	$conn = null;	
	return $app['twig']->render('page_acesso.html', array("rs" => $rs));
})
->before($protector);

$conta->match('/ver', function () use ($app) {
	$usuario = $app['session']->get('conta_usuario');
	$id_conta_usuario = $usuario['id_conta_usuario'];
	try {
		$conn = nconn();
		$sql = "SELECT id_conta_usuario, nome, sobrenome, dt_nascimento, sexo, email, ts_criacao, estado FROM tz_conta_usuario WHERE id_conta_usuario = :id_conta_usuario;";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':id_conta_usuario', $id_conta_usuario);
		$stmt->execute();
		$rs = $stmt->fetch(PDO::FETCH_ASSOC);		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	$conn = null;	
	return $app['twig']->render('page_conta_ver.html', array("rs" => $rs));
})
->before($protector);

$conta->match('/senha', function () use ($app) {
	$usuario = $app['session']->get('conta_usuario');
	$id_conta_usuario = $usuario['id_conta_usuario'];
	$msg = array();
	$tipo = "danger";
	if(isset($_POST['btn'])){
		$atual = $_POST['atual'];
		$nova = $_POST['nova'];
		$confirma = $_POST['confirma'];
		try {
			$conn = nconn();
			$stmt = $conn->prepare("SELECT salt_senha FROM tz_conta_usuario WHERE email = :email;");
			$stmt->bindParam(':email', $usuario['email']);
			$stmt->execute();			
			if($stmt->rowCount()==0){	
				$msg[] = "O e-mail não foi reconhecido";				
			}else{
				$rs = $stmt->fetch(PDO::FETCH_ASSOC);				
				$hash_senha = hash('sha512', $atual.$rs['salt_senha']);
				$salt = $rs['salt_senha'];
				$stmt = $conn->prepare("SELECT id_conta_usuario, nome, sobrenome, email, login, salt_conta, ts_criacao, estado FROM tz_conta_usuario WHERE email = :email AND senha = :senha;");
				$stmt->bindParam(':email', $usuario['email']);
				$stmt->bindParam(':senha', $hash_senha);
				$stmt->execute();			
				if($stmt->rowCount()==1){	
					if($nova==$confirma){
						$sql = "UPDATE tz_conta_usuario SET senha = '".hash('sha512',$nova.$salt)."' WHERE id_conta_usuario = $id_conta_usuario;";
						$stmt = $conn->prepare($sql);
						$stmt->execute();
						$msg[] = "A senha foi alterada com sucesso!";
						$tipo = "success";
					}else{
						$msg[]= "Verifique se as senhas digitadas são iguais";						
					}
				}else{
					$msg[] = "A senha atual está incorreta";
				}				
			}
		}catch(PDOException $ex){
			echo "Erro: " . $ex->getMessage();
		}
	}
	return $app['twig']->render('form_senha.html',array("msg"=>$msg,"tipo"=>$tipo));
})
->before($protector);

$conta->match('/recuperar/senha', function () use ($app) {	
	$msg = "";
	$t = "";
	if(isset($_POST['email'])){
		try {
			$conn = nconn();
			$sql = "SELECT id_conta_usuario FROM tz_conta_usuario WHERE estado = 1 AND email = :email;";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':email', $_POST['email']);
			$stmt->execute();
			$rs = $stmt->fetch(PDO::FETCH_ASSOC);			
			if($stmt->rowCount()==1){
				$p1 = hash('sha512',md5(uniqid(rand(), true)));
				$p2 = hash('sha512',$p1);
				$p3 = md5(uniqid(rand(), true));
				$cha_recuperacao = $p1."_".$p2."_".$p3;
				$id = $rs['id_conta_usuario'];
				$sql = "INSERT INTO tz_cha_recuperacao (cha_recuperacao, estado, ts_envio, id_conta_usuario) VALUES ('".$cha_recuperacao."', 0, CURRENT_TIMESTAMP, ".$id.");";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
				$msg = "Siga as instruções enviadas para o e-mail \"".$_POST['email']."\"";
				$t = "success";
			}else{
				$msg = "Não foi possível concluir o processo";
				$t = "danger";
			}	
			}catch(PDOException $ex){
				echo "Erro: " . $ex->getMessage();
	    }	
	}		
	return $app['twig']->render('form_recuperar_senha.html',array('msg'=>$msg,'t'=>$t));
})
->before($nosesius);

return $conta;
?>