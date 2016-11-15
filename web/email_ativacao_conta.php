<?php
$to  = $email;
$subject = 'Confirmação de Conta do Triz';

$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";	
$headers .= 'From: Triz <noreply@trizdev.esy.es>' . "\r\n";

$primeiro_nome = $nome;
$url_ativacao = "http://".$_SERVER["SERVER_NAME"]."/saticon/ativar/".$cha_ativacao;

$data = date("d/m/Y", $ts_criacao);
$hora = date("H:i:s", $ts_criacao);;

$m = '<!DOCTYPE html><html><head></head><body>';
$m .= '<h1 style="background-color:#DC143C; color: white; padding: 25px 10px">Triz</h1>';
$m .= '<h2>Confirmação de Conta</h2><br>';
$m .= '<p>Olá '.$primeiro_nome.',</p>';
$m .= '<p>Obrigado por cadastrar-se no Triz. Clique no link para ativar sua conta!</p><br>';
$m .= '<h3><a href="'.$url_ativacao.'" target="_blank" style="background-color:#008B8B; color:white; padding:10px; text-decoration:none">Confirmar sua conta</a></h3><br>';
$m .= '<p>Se o link não funcionar, copie e cole o endereço abaixo em seu navegador:<br>';
$m .= '<a href="'.$url_ativacao.'" target="_blank">'.$url_ativacao.'</a></p><br>';
$m .= '<p style="color:#696969">Você recebeu esse e-mail porque se cadastrou no Triz em '.$data.' às '.$hora.'.<br>';
$m .= '&copy; '.date("Y").' MPS. Todos os direitos reservados.</p>';
$m .= '</body></html>';

$m = wordwrap($m, 70, "\r\n");

mail($to, $subject, $m, $headers);	
?>