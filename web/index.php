<?php

//configurações tempo de execução
ini_set('post_max_size', '8M');
ini_set('upload_max_filesize', '8M');

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

$app['destino-ducs'] = 'C:\\xampp\\htdocs\\ducs\\usuario\\perfil\\';
$app['twig']->addGlobal('static', 'http://'.$_SERVER["SERVER_NAME"].'/static'); //armazenamento de recursos estáticos
$app['twig']->addGlobal('ducs', 'http://'.$_SERVER["SERVER_NAME"].'/ducs'); //serviço de conteúdo de usuário

require_once __DIR__.'/../src/conexao.php';
require_once __DIR__.'/../src/autenticacao.php';

$app->mount('/dev', require 'dev.php'); //debug
$app->mount('/saticon', require 'saticon.php');
$app->mount('/conta', require 'conta.php');
$app->mount('/perfil', require 'perfil.php');
$app->mount('/projeto', require 'projeto.php');

$app->match('/', function () use ($app) { 
	if (null === $user = $app['session']->get('conta_usuario')){
		return $app['twig']->render('page_inicio.html');
	}else{	
		try {
			$usuario = $app['session']->get('conta_usuario');
			$id_conta_usuario = $usuario['id_conta_usuario'];
			$conn = nconn();
			$sql = "SELECT co.*, cu.nome, cu.sobrenome, pr.titulo, pr.resumo, pr.dominio FROM tz_convite AS co, tz_conta_usuario AS cu, tz_projeto AS pr WHERE co.id_conta_usuario = cu.id_conta_usuario AND co.id_projeto = pr.id_projeto AND co.estado = 1 AND pr.estado = 0 AND co.id_convidado = :id_conta_usuario ORDER BY co.ts_realizacao DESC;";
			$stmt = $conn->prepare($sql);
			$stmt->bindParam(':id_conta_usuario', $id_conta_usuario);			
			$e = $stmt->execute();	
			$cs = $stmt->fetchAll(PDO::FETCH_ASSOC);			
		}catch(PDOException $ex){
			echo "Erro: " . $ex->getMessage();
		}		
		return $app['twig']->render('page_mural.html',array("cs"=>$cs));
	}
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
				$stmt = $conn->prepare("SELECT id_conta_usuario, nome, sobrenome, email, login, salt_conta, ts_criacao, estado FROM tz_conta_usuario WHERE email = :email AND senha = :senha;");
				$stmt->bindParam(':email', $email);
				$stmt->bindParam(':senha', $hash_senha);
				$stmt->execute();			
				if($stmt->rowCount()==1){	
					$rs = $stmt->fetch(PDO::FETCH_ASSOC);
					if($rs['estado']==1){
					
						$id_conta_usuario = $rs['id_conta_usuario'];
						$npu = md5($rs['id_conta_usuario']);
						
						try {							
							$sql = "SELECT p.* FROM tz_conta_usuario AS c, tz_perfil AS p WHERE c.id_conta_usuario = p.id_conta_usuario AND p.id_conta_usuario = :id;";
							$stmt = $conn->prepare($sql);
							$stmt->bindParam(':id', $id_conta_usuario);
							$stmt->execute();
							$qt = $stmt->rowCount();
							$rsp = $stmt->fetch(PDO::FETCH_ASSOC);				
						}catch(PDOException $ex){
							echo "Erro: " . $ex->getMessage();
						}	  
	
						$app['session']->set('conta_usuario', array('id_conta_usuario' => $rs['id_conta_usuario'], 'nome'=> $rs['nome'], 'sobrenome' => $rs['sobrenome'], 'email' => $rs['email'], 'npu' => $npu, 'imagem' => $rsp['imagem'], 'ext_imagem' => $rsp['ext_imagem'], 'cor1' => $rsp['cor1'], 'cor2' => $rsp['cor2']));
						
						$ts_entrada = date("Y-m-d H:i:s");
						$remote_addr = $_SERVER['REMOTE_ADDR'];
						$js_http_server = json_encode($_SERVER, true);						
						
						$sql = "INSERT INTO tz_log_acesso (ts_entrada, remote_addr, js_http_server, id_conta_usuario) VALUES (:ts_entrada, :remote_addr, :js_http_server, :id_conta_usuario);";
						$stmt = $conn->prepare($sql);
						$stmt->bindParam(':ts_entrada', $ts_entrada);
						$stmt->bindParam(':remote_addr', $remote_addr);
						$stmt->bindParam(':js_http_server', $js_http_server);
						$stmt->bindParam(':id_conta_usuario', $id_conta_usuario);					
						$stmt->execute();	
						
						return $app->redirect('/');
					}else if($rs['estado']==0){
					
						$id_conta_usuario = $rs['id_conta_usuario'];
						$nome = $rs['nome'];
						$email = $rs['email'];
						$ts_criacao = $rs['ts_criacao'];
						
						$date = new DateTime($ts_criacao);
						$ts_criacao = $date->getTimestamp();
						
						$sql = "SELECT cha_ativacao FROM tz_cha_ativacao WHERE id_conta_usuario = :id_conta_usuario;";
						$stmt = $conn->prepare($sql);
						$stmt->bindParam(':id_conta_usuario', $id_conta_usuario);
						$stmt->execute();	
						$rs = $stmt->fetch(PDO::FETCH_ASSOC);
						
						$cha_ativacao = $rs['cha_ativacao'];

						$app['session']->set('ativacao', array('id_conta_usuario' => $id_conta_usuario, 'nome' => $nome, 'email' => $email, 'ts_criacao' => $ts_criacao, 'cha_ativacao' => $cha_ativacao));						
						
						return $app->redirect('/ativacao');
					}
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
        return $app->redirect('/');
    }
});

$app->match('/termos', function () use ($app) {
	return $app['twig']->render('page_termos.html');
});

$app->match('/sobre', function () use ($app) {
	return $app['twig']->render('page_sobre.html');
});

$app->match('/sair', function () use ($app) {
	$app['session']->clear();
	return $app->redirect('/');
});

$app->error(function (\Exception $e, Request $request, $code) use ($app) {	
	if($app['debug']==true){
		return;
	}
    switch ($code) {
        case 404:
            $msg = 'Página não encontrada!';			
            break;
        default:
            $msg = 'Oops! Um erro terrível aconteceu! :(';
    }
    return $app['twig']->render('page_erro_generico.html', array("msg"=>$msg, "code"=>$code));
});

$app->run();