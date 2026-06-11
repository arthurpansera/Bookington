<?php
    include('../../../conecta_db.php');

    session_start();

    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }

    if (!isset($_GET['id'])) {
        header("Location: home_cliente.php");
        exit();
    }

    $id_reserva = (int) $_GET['id'];
    $id_usuario = $_SESSION['id_usuario'];

    $obj = conecta_db();

    if (!$obj) {
        header("Location: database-error.php");
        exit();
    }

    // Garante que a reserva pertence ao cliente logado
    $query_check = "
        SELECT r.id_reserva
        FROM reserva r
        INNER JOIN cliente c ON r.id_cliente = c.id_cliente
        WHERE r.id_reserva = ? AND c.id_usuario = ?
    ";
    $stmt_check = $obj->prepare($query_check);
    $stmt_check->bind_param("ii", $id_reserva, $id_usuario);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows === 0) {
        $_SESSION['error_message'] = "Reserva não encontrada ou sem permissão!";
        header("Location: home_cliente.php");
        exit();
    }

    // Deleta a reserva
    $query_delete = "DELETE FROM reserva WHERE id_reserva = ?";
    $stmt_delete = $obj->prepare($query_delete);
    $stmt_delete->bind_param("i", $id_reserva);

    if ($stmt_delete->execute() && $stmt_delete->affected_rows > 0) {
        $_SESSION['success_message'] = "Reserva cancelada com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao cancelar a reserva!";
    }

    header("Location: home_cliente.php");
    exit();
?>