<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$primeiro_nome = 'Usuário';

if (isset($_SESSION['user_logged_in'], $_SESSION['id_usuario'])) {
    $obj = conecta_db();

    $stmt = $obj->prepare("SELECT nome FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $_SESSION['id_usuario']);
    $stmt->execute();

    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user) {
        $primeiro_nome = explode(' ', $user['nome'])[0];
    }
}
?>