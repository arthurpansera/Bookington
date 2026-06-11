<?php
    include('../../../conecta_db.php');

    session_start();

    if (isset($_SESSION['error_message'])) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Erro!',
                    text: '{$_SESSION['error_message']}',
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

    if (!isset($_SESSION['id_usuario'])) {
        header("Location: ../../../index.php");
        exit();
    }

    $id_reserva = (int) $_GET['id'];

    $obj = conecta_db();

    if (!$obj) {
        header("Location: database-error.php");
        exit();
    }

    // Busca tipo_perfil
    $tipo_perfil = 'cliente';
    $query_tipo = "SELECT tipo_perfil FROM usuario WHERE id_usuario = ?";
    $stmt_tipo = $obj->prepare($query_tipo);
    $stmt_tipo->bind_param("i", $_SESSION['id_usuario']);
    $stmt_tipo->execute();
    $resultado_tipo = $stmt_tipo->get_result();
    if ($resultado_tipo->num_rows > 0) {
        $usuario = $resultado_tipo->fetch_assoc();
        $tipo_perfil = $usuario['tipo_perfil'];
    }

    $label_nome = ($tipo_perfil === 'funcionario') ? 'Nome do cliente: *' : 'Nome: *';
    $placeholder_nome = ($tipo_perfil === 'funcionario') ? 'Insira o nome do cliente completo' : 'Insira seu nome completo';

    // Busca dados da reserva
    $query_reserva = "
        SELECT
            r.id_reserva,
            r.id_empresa,
            r.servico,
            r.data_reserva,
            r.hora_reserva,
            r.num_pessoas,
            r.observacao,
            r.status_reserva,
            u.nome AS nome_cliente
        FROM reserva r
        INNER JOIN cliente c ON r.id_cliente = c.id_cliente
        INNER JOIN usuario u ON c.id_usuario = u.id_usuario
        WHERE r.id_reserva = ?
    ";
    $stmt_reserva = $obj->prepare($query_reserva);
    $stmt_reserva->bind_param("i", $id_reserva);
    $stmt_reserva->execute();
    $res_reserva = $stmt_reserva->get_result();

    $reserva = $res_reserva->fetch_assoc();

    // Formata data para DD/MM/AAAA
    $data_formatada = date('d/m/Y', strtotime($reserva['data_reserva']));
    $hora_formatada = date('H:i', strtotime($reserva['hora_reserva']));

    // Busca todas as empresas para o select
    $query_empresas = "SELECT id_empresa, nome_empresa FROM empresa ORDER BY nome_empresa";
    $result_empresas = $obj->query($query_empresas);

    // Processa o formulário de edição
    if (isset($_POST['name'], $_POST['company'], $_POST['service'], $_POST['date'], $_POST['time'], $_POST['people'])) {
        $nome_reserva = trim($_POST['name']);
        $id_empresa   = (int) $_POST['company'];
        $servico      = trim($_POST['service']);
        $horario      = trim($_POST['time']);
        $observacao   = trim($_POST['observation'] ?? '');
        $num_pessoas  = (int) $_POST['people'];

        if (
            empty($_POST['name']) ||
            empty($_POST['company']) ||
            empty($_POST['service']) ||
            empty($_POST['date']) ||
            empty($_POST['time']) ||
            empty($_POST['people'])
        ) {
            $_SESSION['error_message'] = "Preencha todos os campos obrigatórios!";
            header("Location: editar_reserva_cliente.php?id=$id_reserva");
            exit();
        }

        $data = DateTime::createFromFormat('d/m/Y', $_POST['date']);
        if (!$data) {
            $_SESSION['error_message'] = "Data inválida!";
            header("Location: editar_reserva_cliente.php?id=$id_reserva");
            exit();
        }
        $data_reserva = $data->format('Y-m-d');

        if (!preg_match('/^\d{2}:\d{2}$/', $horario)) {
            $_SESSION['error_message'] = "Horário inválido!";
            header("Location: editar_reserva_cliente.php?id=$id_reserva");
            exit();
        }

        // Verifica conflito de horário (ignora a própria reserva)
        $query_check = "SELECT id_reserva FROM reserva
                        WHERE id_empresa = ? AND data_reserva = ? AND hora_reserva = ?
                        AND status_reserva != 'cancelado'
                        AND id_reserva != ?";
        $stmt_check = $obj->prepare($query_check);
        $stmt_check->bind_param("issi", $id_empresa, $data_reserva, $horario, $id_reserva);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $_SESSION['error_message'] = "Já existe uma reserva para esse local, data e horário!";
            header("Location: editar_reserva_cliente.php?id=$id_reserva");
            exit();
        }

        // Atualiza a reserva
        $query_update = "UPDATE reserva
                         SET id_empresa = ?, servico = ?, data_reserva = ?, hora_reserva = ?, num_pessoas = ?, observacao = ?
                         WHERE id_reserva = ?";
        $stmt_update = $obj->prepare($query_update);

        if (!$stmt_update) {
            die("Erro na query: " . $obj->error);
        }

        $stmt_update->bind_param(
            "isssisi",
            $id_empresa,
            $servico,
            $data_reserva,
            $horario,
            $num_pessoas,
            $observacao,
            $id_reserva
        );

        if (!$stmt_update->execute()) {
            die("Erro ao atualizar reserva: " . $stmt_update->error);
        }

        $_SESSION['success_message'] = "Reserva atualizada com sucesso!";
        header("Location: home_cliente.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookington | Editar Reserva</title>
    <link rel="stylesheet" href="../../styles/pages/cadastro/cadastro.css">
</head>
<body>
    <header>
        <div class="container">
            <nav class="nav">
                <a href="../../../index.php" class="logo-container">
                    <img src="../images/logo-bookington.png" alt="Logo Bookington" class="logo">
                </a>
                <a href="perfil.php" class="nav-profile">
                    <img src="../images/icon-profile.png" alt="Perfil" class="profile-icon">
                </a>
            </nav>
        </div>
    </header>

    <section class="main-content">
        <section class="box-container">
            <section class="btn-back">
                <div class="back-btn">
                    <a href="home_cliente.php">Voltar</a>
                </div>
            </section>

            <h1>Editar Reserva</h1>

            <section class="input-register">
                <form id="form" name="form" method="POST" action="editar_reserva_cliente.php?id=<?php echo $id_reserva; ?>">

                    <!-- Nome -->
                    <div class="full-inputBox">
                        <label for="name"><b><?php echo $label_nome; ?></b></label>
                        <input type="text" id="name" name="name" class="full-inputUser required"
                            data-type="nome" data-required="true"
                            placeholder="<?php echo $placeholder_nome; ?>"
                            value="<?php echo htmlspecialchars($reserva['nome_cliente']); ?>">
                        <span class="span-required">Nome não pode conter números e caracteres especiais.</span>
                    </div>

                    <!-- Empresa / Serviço -->
                    <div class="container-row">
                        <div class="mid-inputBox">
                            <label for="company"><b>Empresa/Organização: *</b></label>
                            <select id="company" name="company" class="mid-inputUser required">
                                <option value="">Selecione uma empresa</option>
                                <?php while ($empresa = $result_empresas->fetch_assoc()): ?>
                                    <option value="<?php echo $empresa['id_empresa']; ?>"
                                        <?php echo ($empresa['id_empresa'] == $reserva['id_empresa']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($empresa['nome_empresa']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <span class="span-required">Por favor, selecione uma empresa.</span>
                        </div>

                        <div class="mid-inputBox">
                            <label for="service"><b>Serviço: *</b></label>
                            <input type="text" id="service" name="service" class="mid-inputUser required"
                                data-type="serviço" data-required="true"
                                placeholder="Escolha o serviço desejado"
                                value="<?php echo htmlspecialchars($reserva['servico'] ?? ''); ?>">
                            <span class="span-required">Por favor, informe o serviço desejado.</span>
                        </div>
                    </div>

                    <!-- Data / Horário / Número de pessoas -->
                    <div class="container-row container-row--three">
                        <div class="small-inputBox">
                            <label for="date"><b>Data: *</b></label>
                            <input type="text" id="date" name="date" class="small-inputUser required"
                                data-type="data" data-required="true"
                                placeholder="DD/MM/AAAA" maxlength="10"
                                onkeypress="return MascaraData(this, event)"
                                value="<?php echo $data_formatada; ?>">
                            <span class="span-required">Insira uma data válida.</span>
                        </div>

                        <div class="small-inputBox">
                            <label for="time"><b>Horário: *</b></label>
                            <input type="text" id="time" name="time" class="small-inputUser required"
                                data-type="horário" data-required="true"
                                placeholder="HH:mm" maxlength="5"
                                onkeypress="return MascaraHorario(this, event)"
                                value="<?php echo $hora_formatada; ?>">
                            <span class="span-required">Insira um horário válido.</span>
                        </div>

                        <div class="mid-inputBox">
                            <label for="people"><b>Número de pessoas: *</b></label>
                            <input type="number" id="people" name="people" class="mid-inputUser required"
                                data-type="número de pessoas" data-required="true" min="1"
                                placeholder="Insira o número de pessoas da sua reserva"
                                value="<?php echo $reserva['num_pessoas'] ?? 1; ?>">
                            <span class="span-required">Informe o número de pessoas.</span>
                        </div>
                    </div>

                    <!-- Observação -->
                    <div class="full-inputBox">
                        <label for="observation"><b>Observação:</b></label>
                        <input type="text" id="observation" name="observation" class="full-inputUser"
                            placeholder="Insira alguma observação sobre a sua reserva"
                            value="<?php echo htmlspecialchars($reserva['observacao'] ?? ''); ?>">
                    </div>

                    <input type="submit" value="Salvar alterações" class="register-btn">
                </form>
            </section>
        </section>
    </section>

    <footer class="footer">
        <p>&copy;2026 - Bookington - Reservas inteligentes, resultados eficientes. Todos os direitos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../scripts/register-validation.js"></script>
</body>
</html>