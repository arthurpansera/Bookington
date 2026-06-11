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
                    confirmButtonColor: '#77CD46',
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

    $query = "SELECT nome FROM usuario WHERE id_usuario = ?";
    $stmt = $obj->prepare($query);
    $stmt->bind_param('i', $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $primeiro_nome = $usuario ? explode(' ', $usuario['nome'])[0] : 'Usuário';

    $query_empresa = "SELECT f.id_empresa, e.nome_empresa FROM funcionario f
        INNER JOIN empresa e ON f.id_empresa = e.id_empresa WHERE f.id_usuario = ?";

    $stmt_empresa = $obj->prepare($query_empresa);
    $stmt_empresa->bind_param("i", $id_usuario);
    $stmt_empresa->execute();

    $result_empresa = $stmt_empresa->get_result();
    $empresa = $result_empresa->fetch_assoc();

    $id_empresa = $empresa['id_empresa'] ?? 0;
    $nome_empresa = $empresa['nome_empresa'] ?? '';

    $query = "SELECT r.id_reserva, e.nome_empresa, r.data_reserva, r.hora_reserva, r.status_reserva, COALESCE(u.nome, r.nome_cliente) AS nome_cliente
    FROM reserva r
    INNER JOIN empresa e ON r.id_empresa = e.id_empresa
    LEFT JOIN cliente c ON r.id_cliente = c.id_cliente
    LEFT JOIN usuario u ON c.id_usuario = u.id_usuario
    WHERE r.id_empresa = ?
    ORDER BY r.data_reserva DESC, r.hora_reserva DESC
    ";

    $stmt = $obj->prepare($query);
    $stmt->bind_param("i", $id_empresa);
    $stmt->execute();

    $reservas = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookington | Início</title>
    <link rel="stylesheet" href="../../styles/pages/home_funcionario/home_funcionario.css?">
</head>
<body>
    <header>
        <div class="container">
            <nav class="nav">

                <a href="home_funcionario.php" class="logo-container">
                    <img src="../images/logo-bookington.png"
                        alt="Logo Bookington"
                        class="logo">
                </a>

                <div class="user-info">
                    <span class="welcome-message">
                        Olá, <?php echo htmlspecialchars($primeiro_nome); ?>!
                    </span>
                    <div class="profile-dropdown">
                        <button class="navbar-avatar" id="profileBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </button>

                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="#" id="editarEmail">Alterar E-mail</a>
                            <a href="#" id="editarSenha">Alterar Senha</a>
                            <a href="#" onclick="confirmarLogout()">Sair</a>
                        </div>
                    </div>
                </div>

            </nav>
        </div>
    </header>

    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../images/logo-bookington.png" alt="Logo">
        </div>

        <nav class="sidebar-nav">
            <a href="home_funcionario.php" class="active">Página Inicial</a>
            <a href="cadastro_funcionario.php">Cadastrar Novo Funcionário</a>
            <a href="solicitacao_reserva.php">Cadastrar Nova Reserva</a>
            <a href="calendario.php">Calendário</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="page-wrapper">
            <div class="page-header">
                <h1 class="page-title">
                    Reservas - <?php echo htmlspecialchars($nome_empresa); ?>
                </h1>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
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
                                    <td class="cliente-nome"><?php echo htmlspecialchars($reserva['nome_cliente']); ?></td>
                                    <td><?php echo $data_formatada . ' - ' . $hora_formatada; ?></td>
                                    <td class="status-cell <?php echo $status_class; ?>"><?php echo $status_label; ?></td>
                                    <td>
                                        <div class="td-actions">
                                            <div class="td-actions">
                                                <?php if ($status === 'cancelado'): ?>
                                                    <a href="ver-reserva.php?id=<?php echo $reserva['id_reserva']; ?>" class="btn-view btn-sm">
                                                        Ver &#9673;
                                                    </a>
                                                <?php else: ?>
                                                    <a href="editar-reserva.php?id=<?php echo $reserva['id_reserva']; ?>" class="btn-edit btn-sm">
                                                        Editar &#9998;
                                                    </a>

                                                    <a href="cancelar-reserva.php?id=<?php echo $reserva['id_reserva']; ?>" class="btn-cancel-res btn-sm"
                                                    onclick="return confirm('Deseja realmente cancelar esta reserva?');">
                                                        Cancelar &#10005;
                                                    </a>

                                                    <a href="ver-reserva.php?id=<?php echo $reserva['id_reserva']; ?>" class="btn-view btn-sm">
                                                        Ver &#9673;
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2026 - Bookington - Reservas inteligentes, resultados eficientes. Todos os direitos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const profileBtn = document.getElementById("profileBtn");
        const dropdownMenu = document.getElementById("dropdownMenu");

        profileBtn.addEventListener("click", function(e){
            e.stopPropagation();
            dropdownMenu.classList.toggle("show");
        });

        document.addEventListener("click", function(){
            dropdownMenu.classList.remove("show");
        });
    </script>

    <script>
        document.getElementById("editarEmail").addEventListener("click", function(e){
            e.preventDefault();

            Swal.fire({
                title: 'Alterar E-mail',
                input: 'email',
                inputLabel: 'Novo e-mail',
                showCancelButton: true,
                confirmButtonText: 'Salvar',
                confirmButtonColor: '#6B1020'
            }).then((result) => {

                if(result.isConfirmed){

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'alterar_email.php';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'email';
                    input.value = result.value;

                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }

            });
        });
    </script>

    <script>
        document.getElementById("editarSenha").addEventListener("click", function(e){
            e.preventDefault();

            Swal.fire({
                title: 'Nova senha',
                input: 'password',
                inputLabel: 'Digite a nova senha',
                showCancelButton: true,
                confirmButtonText: 'Salvar',
                confirmButtonColor: '#6B1020'
            }).then((result) => {

                if(result.isConfirmed){

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'alterar_senha.php';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'senha';
                    input.value = result.value;

                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }

            });
        });
    </script>

    <script>
        function confirmarLogout() {
            Swal.fire({
                title: 'Deseja sair?',
                text: 'Sua sessão será encerrada.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, sair',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#6B1020'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        }
    </script>

</body>
</html>