<?php
session_start();
include('../../../conecta_db.php');

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../../../index.php");
    exit();
}

if (isset($_POST['senha'])) {

    $senha = trim($_POST['senha']);
    $id_usuario = $_SESSION['id_usuario'];

    if (strlen($senha) < 6) {
        $_SESSION['error_message'] =
            "A senha deve possuir pelo menos 6 caracteres.";

        header("Location: home_cliente.php");
        exit();
    }

    $obj = conecta_db();

    if (!$obj) {
        $_SESSION['error_message'] =
            "Erro de conexão com o banco de dados.";

        header("Location: home_cliente.php");
        exit();
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $query = "
        UPDATE usuario
        SET senha = ?
        WHERE id_usuario = ?
    ";

    $stmt = $obj->prepare($query);
    $stmt->bind_param("si", $senha_hash, $id_usuario);

    if ($stmt->execute()) {

        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] =
                "Senha alterada com sucesso!";
        } else {
            $_SESSION['error_message'] =
                "Nenhuma alteração foi realizada.";
        }

    } else {

        $_SESSION['error_message'] =
            "Erro ao alterar senha.";

    }
}

header("Location: home_cliente.php");
exit();
?>