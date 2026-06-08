<?php
    require_once __DIR__ . '/db.php';

    function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    function isLoggedIn() {
        startSession();
        return isset($_SESSION['user_id']);
    }

    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: index.php');
            exit;
        }
    }

    function getCurrentUser() {
        startSession();
        if (!isset($_SESSION['user_id'])) return null;
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }

    function gerarCodigo($pdo) {
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM reservas");
    $row = $stmt->fetch();
    $next = ($row['max_id'] ?? 0) + 1;
    
    return str_pad($next, 2, '0', STR_PAD_LEFT);
    }
?>
