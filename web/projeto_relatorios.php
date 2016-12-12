<?php
$projeto->match('/{dominio}/relatorios', function($dominio) use($app) {	
	$rs = rproj($dominio);
	return $app['twig']->render('page_projeto_d_relatorios.html',array("p"=>$rs));
})
->before($protector)
->before($auzeitor);

$projeto->match('/{dominio}/relatorios/{tipo}', function($dominio, $tipo) use($app) {	
	require_once __DIR__ . '/../vendor/mpdf/mpdf/mpdf.php';
	$rs = rproj($dominio);	
	$mpdf = new Mpdf();
	$mpdf->WriteHTML($app['twig']->render("page_inicio.html"));
	$mpdf->Output();
	exit;	
})
->before($protector)
->before($auzeitor);
?>