<?php
$projeto = $app['controllers_factory'];

$projeto->match('/', function() use($app) {
	return $app->redirect('/projeto/todos');
})
->before($protector);

$projeto->match('/criar', function($situacao) use($app) {
	return $app['twig']->render('form_projeto_criar.html');
})
->before($protector);

$projeto->match('/{situacao}', function($situacao) use($app) {
	return $app['twig']->render('page_projeto_todos.html');
})
->before($protector);

return $projeto;
?>