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

    $query_empresa = "SELECT f.id_empresa, e.nome_empresa FROM funcionario f
        INNER JOIN empresa e ON f.id_empresa = e.id_empresa WHERE f.id_usuario = ?";

    $stmt_empresa = $obj->prepare($query_empresa);
    $stmt_empresa->bind_param("i", $id_usuario);
    $stmt_empresa->execute();

    $result_empresa = $stmt_empresa->get_result();
    $empresa = $result_empresa->fetch_assoc();

    $id_empresa = $empresa['id_empresa'] ?? 0;
    $nome_empresa = $empresa['nome_empresa'] ?? '';

    // ---------- Mês/ano selecionado ----------
    $mes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) date('n');
    $ano = isset($_GET['ano']) ? (int) $_GET['ano'] : (int) date('Y');

    if ($mes < 1) {
        $mes = 12;
        $ano--;
    } elseif ($mes > 12) {
        $mes = 1;
        $ano++;
    }

    $primeiro_dia_mes = mktime(0, 0, 0, $mes, 1, $ano);
    $dias_no_mes = (int) date('t', $primeiro_dia_mes);
    $dia_semana_inicio = (int) date('w', $primeiro_dia_mes); // 0 = domingo

    $meses_pt = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];

    $mes_anterior = $mes - 1;
    $ano_anterior = $ano;
    if ($mes_anterior < 1) {
        $mes_anterior = 12;
        $ano_anterior--;
    }

    $mes_seguinte = $mes + 1;
    $ano_seguinte = $ano;
    if ($mes_seguinte > 12) {
        $mes_seguinte = 1;
        $ano_seguinte++;
    }

    // ---------- Busca as reservas do mês ----------
    $primeiro_dia_str = sprintf('%04d-%02d-01', $ano, $mes);
    $ultimo_dia_str = sprintf('%04d-%02d-%02d', $ano, $mes, $dias_no_mes);

    $query_reservas = "SELECT
            r.id_reserva,
            r.data_reserva,
            r.hora_reserva,
            r.status_reserva,
            u.nome AS nome_cliente
        FROM reserva r
        INNER JOIN cliente c ON r.id_cliente = c.id_cliente
        INNER JOIN usuario u ON c.id_usuario = u.id_usuario
        WHERE r.id_empresa = ?
          AND r.data_reserva BETWEEN ? AND ?
        ORDER BY r.hora_reserva ASC";

    $stmt_reservas = $obj->prepare($query_reservas);
    $stmt_reservas->bind_param("iss", $id_empresa, $primeiro_dia_str, $ultimo_dia_str);
    $stmt_reservas->execute();
    $result_reservas = $stmt_reservas->get_result();

    $reservas_por_dia = [];
    while ($row = $result_reservas->fetch_assoc()) {
        $dia = (int) date('j', strtotime($row['data_reserva']));
        $reservas_por_dia[$dia][] = $row;
    }

    $hoje = date('Y-m-d');
    $dias_semana_pt = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookington | Calendário</title>
    <link rel="stylesheet" href="../../styles/pages/home_funcionario/home_funcionario.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../styles/pages/home_funcionario/calendario.css?v=<?php echo time(); ?>">
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
                        Bem-vindo, <?php echo htmlspecialchars($primeiro_nome); ?>!
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
            <a href="home_funcionario.php">Página Inicial</a>
            <a href="cadastro_funcionario.php">Cadastrar Novo Funcionário</a>
            <a href="solicitacao_reserva.php">Cadastrar Nova Reserva</a>
            <a href="calendario.php" class="active">Calendário</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="page-wrapper calendario-wrapper">
            <div class="page-header">
                <h1 class="page-title">
                    Calendário - <?php echo htmlspecialchars($nome_empresa); ?>
                </h1>
            </div>

            <div class="calendario-box">

                <div class="calendar-header">
                    <h2><?php echo $meses_pt[$mes] . ' de ' . $ano; ?></h2>

                    <div class="calendar-nav">
                        <a href="?mes=<?php echo $mes_anterior; ?>&ano=<?php echo $ano_anterior; ?>" title="Mês anterior">&#8249;</a>
                        <a href="?mes=<?php echo (int) date('n'); ?>&ano=<?php echo (int) date('Y'); ?>" class="btn-hoje">Hoje</a>
                        <a href="?mes=<?php echo $mes_seguinte; ?>&ano=<?php echo $ano_seguinte; ?>" title="Próximo mês">&#8250;</a>
                    </div>
                </div>

                <div class="calendar-grid">
                    <?php foreach ($dias_semana_pt as $dia_semana): ?>
                        <div class="calendar-weekday"><?php echo $dia_semana; ?></div>
                    <?php endforeach; ?>

                    <?php for ($i = 0; $i < $dia_semana_inicio; $i++): ?>
                        <div class="calendar-day empty"></div>
                    <?php endfor; ?>

                    <?php for ($dia = 1; $dia <= $dias_no_mes; $dia++): ?>
                        <?php
                            $data_atual_str = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                            $is_hoje = ($data_atual_str === $hoje);
                        ?>
                        <div class="calendar-day <?php echo $is_hoje ? 'today' : ''; ?>">
                            <div class="calendar-day-number"><?php echo $dia; ?></div>

                            <?php if (isset($reservas_por_dia[$dia])): ?>
                                <?php foreach ($reservas_por_dia[$dia] as $reserva): ?>
                                    <?php
                                        $hora_formatada = date('H:i', strtotime($reserva['hora_reserva']));
                                        $status_class = 'status-' . $reserva['status_reserva'];
                                        $primeiro_nome_cliente = explode(' ', $reserva['nome_cliente'])[0];
                                    ?>
                                    <a href="ver-reserva.php?id=<?php echo $reserva['id_reserva']; ?>"
                                       class="calendar-event <?php echo $status_class; ?>"
                                       title="<?php echo htmlspecialchars($hora_formatada . ' - ' . $reserva['nome_cliente']); ?>">
                                        <?php echo $hora_formatada . ' ' . htmlspecialchars($primeiro_nome_cliente); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>

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