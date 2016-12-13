<?php
$projeto->match('/{dominio}/releases', function($dominio) use($app) {	
	$p = rproj($dominio);
	$r = array();
	try {
		$conn = nconn();			
		$sql = "SELECT * FROM tz_release WHERE id_projeto = :id_projeto ORDER BY dt_entrega DESC;";
		$stmt = $conn->prepare($sql);				
		$stmt->bindParam(':id_projeto', $p['id_projeto']);
		$stmt->execute();			
		$r = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
	}	
	return $app['twig']->render('page_projeto_d_releases.html',array("p"=>$p,"r"=>$r));
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/releases/criar', function($dominio) use($app) {	
	$p = rproj($dominio);
	if(isset($_POST['titulo'])){
		$titulo = $_POST['titulo'];
		$descricao = $_POST['descricao'];		
		$dt_entrega = date('Y-m-d', strtotime($_POST['dt_entrega']));
		try {
			$conn = nconn();			
			$sql = "INSERT INTO tz_release (titulo, descricao, dt_entrega, id_projeto) VALUES (:titulo, :descricao, :dt, :id_projeto);";
			$stmt = $conn->prepare($sql);		
			$stmt->bindParam(':titulo', $titulo);
			$stmt->bindParam(':descricao', $descricao);
			$stmt->bindParam(':dt', $dt_entrega);		
			$stmt->bindParam(':id_projeto', $p['id_projeto']);
			$stmt->execute();			
			return $app->redirect("/projeto/$dominio/releases");
		}catch(PDOException $ex){
			echo "Erro: " . $ex->getMessage();
		}	
	}
	return $app['twig']->render('form_projeto_d_releases_criar.html',array("p"=>$p));
})
->before($protector)
->before($auzeitor);
?>