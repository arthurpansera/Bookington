<?php
session_start();
include('../../../conecta_db.php');

if(isset($_POST['email'])){

    $email = $_POST['email'];
    $id_usuario = $_SESSION['id_usuario'];

    $obj = conecta_db();

    $query = "UPDATE usuario
              SET email = ?
              WHERE id_usuario = ?";

    $stmt = $obj->prepare($query);
    $stmt->bind_param("si", $email, $id_usuario);

    if($stmt->execute()){
        $_SESSION['success_message'] = "E-mail alterado com sucesso!";
    }else{
        $_SESSION['error_message'] = "Erro ao alterar e-mail!";
    }
}

header("Location: home_cliente.php");
exit();
?>