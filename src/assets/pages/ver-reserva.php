<?php
    include('../../../conecta_db.php');

    session_start();

    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header("Location: login.php");
        exit();
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

    $query_tipo = "SELECT tipo_perfil FROM usuario WHERE id_usuario = ?";
    $stmt_tipo = $obj->prepare($query_tipo);
    $stmt_tipo->bind_param("i", $id_usuario);
    $stmt_tipo->execute();
    $tipo = $stmt_tipo->get_result()->fetch_assoc()['tipo_perfil'] ?? 'cliente';

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header("Location: home_cliente.php");
        exit();
    }

    $id_reserva = (int) $_GET['id'];

    if ($tipo === 'funcionario') {

        $q = "SELECT id_empresa FROM funcionario WHERE id_usuario = ?";
        $st = $obj->prepare($q);
        $st->bind_param("i", $id_usuario);
        $st->execute();
        $id_empresa = $st->get_result()->fetch_assoc()['id_empresa'] ?? 0;

        $query = "
            SELECT r.*, e.nome_empresa, r.nome_cliente
            FROM reserva r
            INNER JOIN empresa e ON r.id_empresa = e.id_empresa
            WHERE r.id_reserva = ?
            AND r.id_empresa = ?
        ";

        $stmt = $obj->prepare($query);
        $stmt->bind_param("ii", $id_reserva, $id_empresa);

    } else {
        $query = "
            SELECT 
                r.id_reserva,
                r.id_empresa,
                r.id_cliente,
                r.servico,
                r.data_reserva,
                r.hora_reserva,
                r.num_pessoas,
                r.observacao,
                r.status_reserva,
                r.criado_em,
                e.nome_empresa,
                u.nome AS nome_cliente
            FROM reserva r
            INNER JOIN empresa e ON r.id_empresa = e.id_empresa
            INNER JOIN cliente c ON r.id_cliente = c.id_cliente
            INNER JOIN usuario u ON c.id_usuario = u.id_usuario
            WHERE r.id_reserva = ?
            AND r.id_cliente = (
                SELECT id_cliente FROM cliente WHERE id_usuario = ?
            )
        ";

        $stmt = $obj->prepare($query);
        $stmt->bind_param("ii", $id_reserva, $id_usuario);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $reserva = $result->fetch_assoc();

    $data_formatada = date('d/m/Y', strtotime($reserva['data_reserva']));
    $hora_formatada = date('H\hi', strtotime($reserva['hora_reserva']));
    $criado_formatado = date('d/m/Y H\hi', strtotime($reserva['criado_em']));

    $status = $reserva['status_reserva'];
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

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookington | Detalhes da Reserva</title>
    <link rel="stylesheet" href="../../styles/pages/ver_reserva/ver_reserva.css?v=<?php echo time(); ?>">
</head>
<body>
    <header>
        <div class="container">
            <nav class="nav">

                <a href="<?php echo ($tipo === 'funcionario') ? 'home_funcionario.php' : 'home_cliente.php'; ?>" class="logo-container">
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

    <div class="page-wrapper">
        <div class="page-header">
            <h1 class="page-title">Detalhes da Reserva</h1>

            <a href="<?php echo ($tipo === 'funcionario') ? 'home_funcionario.php' : 'home_cliente.php'; ?>" class="btn-success">Voltar</a>
        </div>

        <div class="table-wrap">
            <table>
                <tbody>
                    <tr>
                        <th style="width: 220px;">Código</th>
                        <td><?php echo str_pad($reserva['id_reserva'], 2, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <th>Cliente</th>
                        <td><?php echo htmlspecialchars($reserva['nome_cliente']); ?></td>
                    </tr>
                    <tr>
                        <th>Empresa/Organização</th>
                        <td><?php echo htmlspecialchars($reserva['nome_empresa']); ?></td>
                    </tr>
                    <tr>
                        <th>Data</th>
                        <td><?php echo $data_formatada; ?></td>
                    </tr>
                    <tr>
                        <th>Hora</th>
                        <td><?php echo $hora_formatada; ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td class="status-cell <?php echo $status_class; ?>"><?php echo $status_label; ?></td>
                    </tr>
                    <tr>
                        <th>Serviço</th>
                        <td><?php echo $reserva['servico'] ? htmlspecialchars($reserva['servico']) : '-'; ?></td>
                    </tr>
                    <tr>
                        <th>Número de Pessoas</th>
                        <td><?php echo (int) $reserva['num_pessoas']; ?></td>
                    </tr>
                    <tr>
                        <th>Observação</th>
                        <td><?php echo $reserva['observacao'] ? nl2br(htmlspecialchars($reserva['observacao'])) : '-'; ?></td>
                    </tr>
                    <tr>
                        <th>Criado em</th>
                        <td><?php echo $criado_formatado; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

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