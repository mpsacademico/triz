<?php
$protector = function () use ($app) {
	if (null === $user = $app['session']->get('conta_usuario')) {
        return $app->redirect('/entrar');
    }	
};
$nosesius = function () use ($app) {
	if (!(null === $user = $app['session']->get('conta_usuario'))) {
        return $app->redirect('/');
    }	
};
$auzeitor = function ($request, $response) use ($app) {
	$dom = $request->get("dominio");
	$usuario = $app['session']->get('conta_usuario');
	$id = $usuario['id_conta_usuario'];
	try {
		$conn = nconn();
		$sql = "SELECT * FROM tz_integrante AS i, tz_convite AS c, tz_projeto AS p WHERE i.id_convite = c.id_convite AND c.id_projeto = p.id_projeto AND i.estado = 1 AND p.dominio = '$dom' AND c.id_convidado = $id AND c.estado = 2;";		
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$rs = $stmt->fetch(PDO::FETCH_ASSOC);
		if($rs == false){
			$app->abort(401, 'Acesso não autorizado!');
		}	
	}catch(PDOException $ex){
		echo "Erro: " . $ex->getMessage();
    }
};
?>