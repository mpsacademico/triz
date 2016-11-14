﻿<?php
$conta = $app['controllers_factory'];

$conta->get('/criar', function() use($app) {
    return $app['twig']->render('form_conta_criar.html');
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
	$dt_criacao = date("Y-m-d h:i:s", $ts_criacao);
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
		
		//envia e-mail de ativação de conta se estiver em operação
		if(strcmp($_SERVER['SERVER_NAME'],"localhost") != 0){
			require 'email_ativacao_conta.php';			
		}
		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	$conn = null;
    return $app->redirect('/');
});

return $conta;
?>