<?php

date_default_timezone_set("America/Sao_Paulo");

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

$app['debug'] = true; //ativado apenas em ambiente de desenvolvimento

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app['twig']->addGlobal('static', 'http://localhost/triz/static'); //armazenamento de recursos estáticos
$app['twig']->addGlobal('ducs', 'http://localhost/triz/ducs'); //serviço de conteúdo de usuário

require_once __DIR__.'/../src/conexao.php';
require_once __DIR__.'/../src/autenticacao.php';

$app->mount('/conta', require 'conta.php');

$app->match('/', function () use ($app) {
    return $app['twig']->render('page_inicio.html');
});

$app->match('/entrar', function (Request $request) use ($app) {		
	if($_SERVER['REQUEST_METHOD']=='POST'){
		$email = $_POST['email'];
		$senha = $_POST['senha'];
		try {
			$conn = nconn();
			$stmt = $conn->prepare("SELECT salt_senha FROM tz_conta_usuario WHERE email = :email;");
			$stmt->bindParam(':email', $email);
			$stmt->execute();			
			if($stmt->rowCount()==0){	
				$e[] = "O e-mail não foi reconhecido";				
			}else{
				$rs = $stmt->fetch(PDO::FETCH_ASSOC);
				$hash_senha = hash('sha512', $senha.$rs['salt_senha']);
				$stmt = $conn->prepare("SELECT id_conta_usuario, nome, sobrenome, email, login, salt_conta FROM tz_conta_usuario WHERE email = :email AND senha = :senha;");
				$stmt->bindParam(':email', $email);
				$stmt->bindParam(':senha', $hash_senha);
				$stmt->execute();			
				if($stmt->rowCount()==1){	
					$rs = $stmt->fetch(PDO::FETCH_ASSOC);
					$npu = md5($rs['id_conta_usuario']);
					$app['session']->set('conta_usuario', array('id_conta_usuario' => $rs['id_conta_usuario'], 'nome'=> $rs['nome'], 'sobrenome' => $rs['sobrenome'], 'email' => $rs['email'], 'npu' => $npu));
					return $app->redirect('/mural');
				}else{
					$e[] = "O e-mail e a senha não coincidem";
				}
			}
		}
		catch(PDOException $e) {
			echo "Erro: " . $e->getMessage();
		}
		$conn = null;
		return $app['twig']->render('form_entrar.html',array('erros' => $e));
	}
	return $app['twig']->render('form_entrar.html');
})
->before(function() use ($app){
	if(!(null === $app['session']->get('conta_usuario'))){
        return $app->redirect('/mural');
    }
});

$app->match('/sair', function () use ($app) {
	$app['session']->clear();
	return $app->redirect('/');
});

$app->match('/mural', function () use ($app) {
	$usuario = $app['session']->get('conta_usuario');
    return $app['twig']->render('page_mural.html');
})
->before($protector);

$app->match('/feedback', function (Request $request) use ($app) {

	$to  = 'trizmps@gmail.com';
	$subject = 'Confirmação de cadastro no Triz';
	
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";	
	$headers .= 'From: Triz <noreply@trizdev.esy.es>' . "\r\n";
	
	mail($to, $subject, "Endereço para ativação: blablabla", $headers);

    return new Response('Thank you for your feedback!', 201);
});

$app->run();