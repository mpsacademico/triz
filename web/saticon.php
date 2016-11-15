<?php
$saticon = $app['controllers_factory'];

$saticon->match('/ativar/{chave}', function($chave) use($app) {
	
    return $app->redirect('/ativacao');
});

$saticon->match('/revogar/{chave}', function($chave) use($app) {
    return $app->redirect('/');
});

$app->match('/ativacao', function() use($app) {
    return $app['twig']->render('page_ativacao.html', array("teste"=>"teste"));
});

return $saticon;
?>