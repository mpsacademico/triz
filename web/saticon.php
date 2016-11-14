<?php
$conta = $app['controllers_factory'];

$conta->match('/ativar/{chave}', function($chave) use($app) {
    return $app->redirect('/ativacao');
});

$conta->match('/revogar/{chave}', function($chave) use($app) {
    return $app->redirect('/');
});

$app->match('/ativacao', function() use($app) {
    return $app['twig']->render('page_ativacao.html', array("teste"=>"teste"));
});

return $conta;
?>