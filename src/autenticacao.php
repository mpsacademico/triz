<?php
$protector = function () use ($app) {
	if (null === $user = $app['session']->get('conta_usuario')) {
        return $app->redirect('/entrar');
    }
}
?>