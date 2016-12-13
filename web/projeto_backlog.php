<?php
$projeto->match('/{dominio}/backlog', function($dominio) use($app) {	
	$p = rproj($dominio);
	return $app['twig']->render('page_projeto_d_backlog.html',array("p"=>$p));
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/backlog/historia/criar', function($dominio) use($app) {	
	$p = rproj($dominio);
	return $app['twig']->render('page_projeto_d_backlog.html',array("p"=>$p));
})
->before($protector)
->before($auzeitor);
?>