<?php
$dev = $app['controllers_factory'];

$dev->match('/', function() use($app) {
	return $app['twig']->render('dev/page_inicio.html');
});

$dev->match('/chaves/{estado}/{ordem}', function($estado, $ordem) use($app) {	
	try {
		$conn = nconn();
		$sql = "SELECT c.id_cha_ativacao, c.cha_ativacao, c.estado, c.ts_acao, u.nome, u.sobrenome, u.email, u.ts_criacao, TIMESTAMPDIFF(day,u.ts_criacao,NOW()) AS intervalo FROM tz_cha_ativacao AS c, tz_conta_usuario AS u WHERE c.id_conta_usuario = u.id_conta_usuario AND c.estado = :estado ORDER BY c.id_cha_ativacao $ordem";
		$stmt = $conn->prepare($sql);		
		$stmt->bindParam(':estado', $estado);		
		$stmt->execute();		
		$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);		
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
	$conn = null;	
	return $app['twig']->render('dev/page_chaves.html', array("rs" => $rs, "estado" => $estado, "ordem" => $ordem));
})
->value('estado', '0')
->value('ordem', 'DESC');

return $dev;
?>