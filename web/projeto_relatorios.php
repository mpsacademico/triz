<?php
$projeto->match('/{dominio}/relatorios', function($dominio) use($app) {	
	$rs = rproj($dominio);
	return $app['twig']->render('page_projeto_d_relatorios.html',array("p"=>$rs));
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/relatorios/{tipo}', function($dominio, $tipo) use($app) {	
	require_once __DIR__ . '/../vendor/mpdf/mpdf/mpdf.php';
	$p = rproj($dominio);	
	$mpdf = new Mpdf();		
	$ts = array();
	$is = array();
	try {
		$conn = nconn();		
		$sql = "SELECT * FROM tz_tag_projeto WHERE id_projeto = ".$p['id_projeto'].";";
		$stmt = $conn->prepare($sql);		
		$stmt->execute();
		$ts = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$sql = "SELECT t.id_integrante, t.id_time, t.funcao, t.estado, t.papel, c.id_convite, c.ts_realizacao, c.ts_resposta, cu.id_conta_usuario, cu.nome, cu.sobrenome, cu.email, p.* FROM tz_integrante AS t, tz_convite AS c, tz_conta_usuario AS cu, tz_perfil AS p WHERE t.id_convite = c.id_convite AND c.id_convidado = cu.id_conta_usuario AND c.id_convidado = p.id_conta_usuario AND c.id_projeto = ".$p["id_projeto"]." ORDER BY cu.nome ASC;";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$is = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }	
	$mpdf->WriteHTML($app['twig']->render("page_relatorios_$tipo.html",array("p"=>$p,"ts"=>$ts,"is"=>$is)));
	$mpdf->Output();
	exit;	
})
->before($protector)
->before($auzeitor);
?>