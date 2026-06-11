<?php
    include('../../../conecta_db.php');

    session_start();

    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }

    if (isset($_SESSION['error_message'])) {
        $mensagem = addslashes($_SESSION['error_message']);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Erro!',
                    text: '{$mensagem}',
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#6B1020',
                    allowOutsideClick: true,
                    heightAuto: false
                });
            });
        </script>";
        unset($_SESSION['error_message']);
    }

    if (isset($_SESSION['success_message'])) {
        $mensagem = addslashes($_SESSION['success_message']);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Sucesso!',
                    text: '{$mensagem}',
                    icon: 'success',
                    confirmButtonText: 'Ok',
                    confirmButtonColor: '#6B1020',
                    allowOutsideClick: true,
                    heightAuto: false
                });
            });
        </script>";
        unset($_SESSION['success_message']);
    }

    $id_usuario = $_SESSION['id_usuario'];

    $obj = conecta_db();

    if (!$obj) {
        header("Location: database-error.php");
        exit;
    }

    // Nome do usuário para a saudação
    $query = "SELECT nome FROM usuario WHERE id_usuario = ?";
    $stmt = $obj->prepare($query);
    $stmt->bind_param('i', $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $primeiro_nome = $usuario ? explode(' ', $usuario['nome'])[0] : 'Usuário';

    // Busca o id_cliente vinculado ao usuário
    $query_cliente = "SELECT id_cliente FROM cliente WHERE id_usuario = ?";
    $stmt_cliente = $obj->prepare($query_cliente);
    $stmt_cliente->bind_param("i", $id_usuario);
    $stmt_cliente->execute();

    $result_cliente = $stmt_cliente->get_result();
    $cliente = $result_cliente->fetch_assoc();

    $id_cliente = $cliente['id_cliente'] ?? 0;

    // Reservas do cliente
    $query = "SELECT
                r.id_reserva,
                e.nome_empresa,
                r.data_reserva,
                r.hora_reserva,
                r.status_reserva
            FROM reserva r
            INNER JOIN empresa e
                    ON r.id_empresa = e.id_empresa
            WHERE r.id_cliente = ?
            ORDER BY r.data_reserva DESC, r.hora_reserva DESC";

    $stmt = $obj->prepare($query);
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();

    $reservas = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookington | Início</title>
    <link rel="stylesheet" href="../../styles/pages/home_cliente/home_cliente.css?v=<?php echo time(); ?>">
</head>
<body>

    <nav class="navbar">
        <a href="home.php" class="navbar-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
                <circle cx="8" cy="14" r="1"></circle>
                <circle cx="12" cy="14" r="1"></circle>
                <circle cx="16" cy="14" r="1"></circle>
                <circle cx="8" cy="18" r="1"></circle>
                <circle cx="12" cy="18" r="1"></circle>
            </svg>
            Bookington
        </a>

        <form class="navbar-search" action="pesquisa.php" method="GET">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="q" placeholder="Pesquisar">
        </form>

        <div class="navbar-right">
            <span class="navbar-welcome">Bem-vindo, <?php echo htmlspecialchars($primeiro_nome); ?>!</span>
            <a href="perfil.php" class="navbar-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </div>
    </nav>

    <div class="page-wrapper">
        <h1 class="page-title">Minhas Reservas</h1>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Empresa/Organização</th>
                        <th>Data / Hora</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reservas->num_rows > 0): ?>
                        <?php while ($reserva = $reservas->fetch_assoc()): ?>
                            <?php
                                $data_formatada = date('d/m/Y', strtotime($reserva['data_reserva']));
                                $hora_formatada = date('H\hi', strtotime($reserva['hora_reserva']));
                                $status = $reserva['status_reserva'];

                                $status_label = '';
                                $status_class = '';
                                switch ($status) {
                                    case 'aberto':
                                        $status_label = 'Em aberto';
                                        $status_class = 'status-aberto';
                                        break;
                                    case 'reservado':
                                        $status_label = 'Reservado';
                                        $status_class = 'status-reservado';
                                        break;
                                    case 'cancelado':
                                        $status_label = 'Cancelado';
                                        $status_class = 'status-cancelado';
                                        break;
                                    default:
                                        $status_label = ucfirst($status);
                                        $status_class = '';
                                }
                            ?>
                            <tr>
                                <td><?php echo str_pad($reserva['id_reserva'], 2, '0', STR_PAD_LEFT); ?></td>
                                <td class="empresa-nome"><?php echo htmlspecialchars($reserva['nome_empresa']); ?></td>
                                <td><?php echo $data_formatada . ' - ' . $hora_formatada; ?></td>
                                <td class="status-cell <?php echo $status_class; ?>"><?php echo $status_label; ?></td>
                                <td>
                                    <div class="td-actions">
                                        <?php if ($status !== 'cancelado'): ?>
                                            <a href="editar-reserva.php?id=<?php echo $reserva['id_reserva']; ?>" class="btn btn-edit btn-sm">Editar &#9998;</a>
                                            <a href="cancelar-reserva.php?id=<?php echo $reserva['id_reserva']; ?>" class="btn btn-cancel-res btn-sm" onclick="return confirm('Deseja realmente cancelar esta reserva?');">Cancelar &#10005;</a>
                                        <?php endif; ?>
                                        <a href="ver-reserva.php?id=<?php echo $reserva['id_reserva']; ?>" class="btn btn-view btn-sm">Ver &#9673;</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 24px; color: var(--gray-400);">
                                Você ainda não possui reservas.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="td-more">...</div>
        </div>
    </div>

    <a href="solicitar-reserva.php" class="btn-success-float">+ Solicitar reserva</a>

    <footer class="footer">
        <p>&copy; 2026 - Bookington - Reservas inteligentes, resultados eficientes. Todos os direitos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>