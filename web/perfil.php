<?php
$perfil = $app['controllers_factory'];

$perfil->get('/', function() use($app) {
	$user = $app['session']->get('conta_usuario');
	$id = $user['id_conta_usuario'];
	try {
		$conn = nconn();
		$sql = "SELECT c.nome, c.sobrenome, p.* FROM tz_conta_usuario AS c, tz_perfil AS p WHERE c.id_conta_usuario = p.id_conta_usuario AND p.id_conta_usuario = :id;";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		$qt = $stmt->rowCount();
		$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);			
		return $app['twig']->render('page_perfil.html', $rs[0]);		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	$conn = null;	
    return $app['twig']->render('page_perfil.html');
})
->before($protector);

$perfil->get('/editar', function() use($app) {
	$user = $app['session']->get('conta_usuario');
	$id = $user['id_conta_usuario'];
	try {
		$conn = nconn();
		$sql = "SELECT c.nome, c.sobrenome, p.* FROM tz_conta_usuario AS c, tz_perfil AS p WHERE c.id_conta_usuario = p.id_conta_usuario AND p.id_conta_usuario = :id;";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		$qt = $stmt->rowCount();
		$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);			
		return $app['twig']->render('form_perfil_editar.html', $rs[0]);		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	$conn = null;    
    return $app['twig']->render('form_perfil_editar.html', array("nome"=>$user['nome'], "sobrenome"=>$user['sobrenome']));
})
->before($protector);

$perfil->match('/{id}', function($id) use($app) {	
	try {
		$conn = nconn();
		$sql = "SELECT c.nome, c.sobrenome, p.* FROM tz_conta_usuario AS c, tz_perfil AS p WHERE c.id_conta_usuario = p.id_conta_usuario AND p.id_conta_usuario = :id;";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		$qt = $stmt->rowCount();
		$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);	
		if($qt == 1){		
			return $app['twig']->render('page_perfil.html', $rs[0]);		
		}		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	$conn = null;	
    return $app->redirect('/perfil');
})
->before($protector);

$perfil->post('/editar', function() use($app) {

	$ocupacao = $_POST['ocupacao'];
	$biografia = $_POST['biografia'];				
	$cor1 = $_POST['cor1'];
	$cor2 = $_POST['cor2'];	

	$user = $app['session']->get('conta_usuario');
	$id = $user['id_conta_usuario'];

	if ( isset( $_FILES[ 'imagem' ][ 'name' ] ) && $_FILES[ 'imagem' ][ 'error' ] == 0 ) {	
		/*
		echo 'Você enviou o arquivo: <strong>' . $_FILES[ 'imagem' ][ 'name' ] . '</strong><br />';
		echo 'Este arquivo é do tipo: <strong > ' . $_FILES[ 'imagem' ][ 'type' ] . ' </strong ><br />';
		echo 'Temporáriamente foi salvo em: <strong>' . $_FILES[ 'imagem' ][ 'tmp_name' ] . '</strong><br />';
		echo 'Seu tamanho é: <strong>' . $_FILES[ 'imagem' ][ 'size' ] . '</strong> Bytes<br /><br />';
		*/	 
		$arquivo_tmp = $_FILES[ 'imagem' ][ 'tmp_name' ];
		$nome = $_FILES[ 'imagem' ][ 'name' ];	 
		$extensao = pathinfo ( $nome, PATHINFO_EXTENSION );	 
		$extensao = strtolower ( $extensao );	 
		if ( strstr ( '.jpg;.jpeg;.gif;.png', $extensao ) ) {	
			$t = time(); //tempo atual
			$u = uniqid ( $t ); //valor único gerado com base no tempo
			$a = md5($u+$t); //valor aleatório para manter o arquivo ainda mais único
			$i = md5($id); //md5 do ID do usuário (conferência posterior de autoria de upload
			$nomeImagem = $u."_".$a."_".$i."_".$t;
			$novoNome = $nomeImagem.".". $extensao; 			
			$destino = $app['destino-ducs']. $novoNome;	 
			if ( @move_uploaded_file ( $arquivo_tmp, $destino ) ) {
				//echo 'Arquivo salvo com sucesso em : <strong>' . $destino . '</strong><br />';	
				try {				
					$conn = nconn();
					$sql = "UPDATE tz_perfil SET ocupacao = :ocupacao, biografia = :biografia, imagem = :imagem, ext_imagem = :ext, cor1 = :cor1, cor2 = :cor2 WHERE id_conta_usuario = :id;";
					$stmt = $conn->prepare($sql);
					$stmt->bindParam(':ocupacao', $ocupacao);
					$stmt->bindParam(':biografia', $biografia);
					$stmt->bindParam(':imagem', $nomeImagem);
					$stmt->bindParam(':ext', $extensao);
					$stmt->bindParam(':cor1', $cor1);
					$stmt->bindParam(':cor2', $cor2);
					$stmt->bindParam(':id', $id);
					$stmt->execute();
					return $app->redirect('/perfil');  
				}catch(PDOException $ex){
					echo "Erro: " . $ex->getMessage();
				}
				$conn = null;	
			}else{
				//echo 'Erro ao salvar o arquivo. Aparentemente você não tem permissão de escrita.<br />';
			}
		}else{
			//echo 'Você poderá enviar apenas arquivos "*.jpg;*.jpeg;*.gif;*.png"<br />';
		}		
	}else{
		//echo 'Você não enviou nenhum arquivo!';	
	}	
	try {				
		$conn = nconn();
		$sql = "UPDATE tz_perfil SET ocupacao = :ocupacao, biografia = :biografia, cor1 = :cor1, cor2 = :cor2 WHERE id_conta_usuario = :id;";
		$stmt = $conn->prepare($sql);
		$stmt->bindParam(':ocupacao', $ocupacao);
		$stmt->bindParam(':biografia', $biografia);		
		$stmt->bindParam(':cor1', $cor1);
		$stmt->bindParam(':cor2', $cor2);
		$stmt->bindParam(':id', $id);
		$stmt->execute();					
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
	}
	$conn = null;	
	return $app->redirect('/perfil');    
})
->before($protector);

return $perfil;
?>