<?php
    include('../../../conecta_db.php');
    session_start();

    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }

    if (!isset($_GET['id'])) {
        header("Location: home_funcionario.php");
        exit();
    }

    $id_reserva = (int) $_GET['id'];
    $id_usuario = $_SESSION['id_usuario'];

    $obj = conecta_db();

    $query_empresa = "SELECT id_empresa FROM funcionario WHERE id_usuario = ?";
    $stmt = $obj->prepare($query_empresa);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $empresa = $result->fetch_assoc();

    if (!$empresa) {
        $_SESSION['error_message'] = "Funcionário não encontrado!";
        header("Location: home_funcionario.php");
        exit();
    }

    $id_empresa = $empresa['id_empresa'];

    $query_check = "
        SELECT id_reserva 
        FROM reserva 
        WHERE id_reserva = ? AND id_empresa = ?
    ";

    $stmt_check = $obj->prepare($query_check);
    $stmt_check->bind_param("ii", $id_reserva, $id_empresa);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows === 0) {
        $_SESSION['error_message'] = "Reserva não encontrada ou sem permissão!";
        header("Location: home_funcionario.php");
        exit();
    }

    $query_update = "UPDATE reserva SET status_reserva = 'cancelado' WHERE id_reserva = ?";
    $stmt_update = $obj->prepare($query_update);
    $stmt_update->bind_param("i", $id_reserva);

    if ($stmt_update->execute()) {
        $_SESSION['success_message'] = "Reserva cancelada com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao cancelar reserva!";
    }

    header("Location: home_funcionario.php");
    exit();
?>