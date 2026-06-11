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

    $obj = conecta_db();

    if (!$obj) {
        header("Location: database-error.php");
        exit();
    }

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

    $query_empresas = " SELECT id_empresa, nome_empresa FROM empresa ORDER BY nome_empresa";

    $result_empresas = $obj->query($query_empresas);

    if (isset($_POST['name'], $_POST['company'], $_POST['service'], $_POST['date'], $_POST['time'], $_POST['people'])) {
        $nome_reserva = trim($_POST['name']);
        $id_empresa   = (int) $_POST['company'];
        $servico      = trim($_POST['service']);
        $horario      = trim($_POST['time']);
        $observacao   = trim($_POST['observation'] ?? '');
        $num_pessoas  = (int) $_POST['people'];

        $data = DateTime::createFromFormat('d/m/Y', $_POST['date']);

        if (!$data) {
            $_SESSION['error_message'] = "Data inválida!";
            header("Location: solicitacao_reserva.php");
            exit();
        }

        $data_reserva = $data->format('Y-m-d');

        if (!preg_match('/^\d{2}:\d{2}$/', $horario)) {
            $_SESSION['error_message'] = "Horário inválido!";
            header("Location: solicitacao_reserva.php");
            exit();
        }

        if ($tipo_perfil === 'funcionario') {

            $query_cliente = "
                SELECT c.id_cliente
                FROM cliente c
                INNER JOIN usuario u
                    ON c.id_usuario = u.id_usuario
                WHERE u.nome = ?
            ";

            $stmt_cliente = $obj->prepare($query_cliente);
            $stmt_cliente->bind_param("s", $nome_reserva);
            $stmt_cliente->execute();

            $res_cliente = $stmt_cliente->get_result();

            if ($res_cliente->num_rows === 0) {
                $_SESSION['error_message'] = "Cliente não encontrado no sistema!";
                header("Location: solicitacao_reserva.php");
                exit();
            }

            $row_cliente = $res_cliente->fetch_assoc();
            $id_cliente = $row_cliente['id_cliente'];

        } else {
            $query_cliente = "SELECT id_cliente FROM cliente WHERE id_usuario = ?";

            $stmt_cliente = $obj->prepare($query_cliente);
            $stmt_cliente->bind_param("i", $_SESSION['id_usuario']);
            $stmt_cliente->execute();

            $res_cliente = $stmt_cliente->get_result();

            if ($res_cliente->num_rows === 0) {
                $_SESSION['error_message'] = "Perfil de cliente não encontrado!";
                header("Location: solicitacao_reserva.php");
                exit();
            }

            $row_cliente = $res_cliente->fetch_assoc();
            $id_cliente = $row_cliente['id_cliente'];
        }

        $query_check = "SELECT id_reserva FROM reserva WHERE id_empresa = ? AND data_reserva = ? AND hora_reserva = ? AND status_reserva != 'cancelado'";

        $stmt_check = $obj->prepare($query_check);
        $stmt_check->bind_param("iss", $id_empresa, $data_reserva, $horario);

        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $_SESSION['error_message'] =
                "Já existe uma reserva para esse local, data e horário!";

            header("Location: solicitacao_reserva.php");
            exit();
        }

        $query_insert = "INSERT INTO reserva (id_cliente, id_empresa, servico, data_reserva, hora_reserva, num_pessoas, observacao, status_reserva) VALUES (?, ?, ?, ?, ?, ?, ?, 'aberto')";

        $stmt_insert = $obj->prepare($query_insert);

        if (!$stmt_insert) {
            die("Erro na query: " . $obj->error);
        }

        $stmt_insert->bind_param("iisssis", $id_cliente, $id_empresa, $servico, $data_reserva, $horario, $num_pessoas, $observacao);

        if (!$stmt_insert->execute()) {
            die("Erro ao cadastrar reserva: " . $stmt_insert->error);
        }

        $_SESSION['success_message'] = "Reserva cadastrada com sucesso!";
        header("Location: home_cliente.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookington | Dados Reserva</title>
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

            <h1>Dados Reserva</h1>

            <section class="input-register">
                <form id="form" name="form" method="POST" action="solicitacao_reserva.php">

                    <div class="full-inputBox">
                        <label for="name"><b><?php echo $label_nome; ?></b></label>
                        <input type="text" id="name" name="name" class="full-inputUser required"
                            data-type="nome" data-required="true"
                            placeholder="<?php echo $placeholder_nome; ?>">
                        <span class="span-required">Nome não pode conter números e caracteres especiais.</span>
                    </div>

                    <div class="container-row">
                        <div class="mid-inputBox">
                            <label for="company">
                                <b>Empresa/Organização: *</b>
                            </label>

                            <select
                                id="company"
                                name="company"
                                class="mid-inputUser required">

                                <option value="">
                                    Selecione uma empresa
                                </option>

                                <?php while($empresa = $result_empresas->fetch_assoc()): ?>
                                    <option value="<?php echo $empresa['id_empresa']; ?>">
                                        <?php echo htmlspecialchars($empresa['nome_empresa']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>

                            <span class="span-required">
                                Por favor, selecione uma empresa.
                            </span>
                        </div>

                        <div class="mid-inputBox">
                            <label for="service"><b>Serviço: *</b></label>
                            <input type="text" id="service" name="service" class="mid-inputUser required"
                                data-type="serviço" data-required="true"
                                placeholder="Escolha o serviço desejado">
                            <span class="span-required">Por favor, informe o serviço desejado.</span>
                        </div>
                    </div>

                    <div class="container-row container-row--three">
                        <div class="small-inputBox">
                            <label for="date"><b>Data: *</b></label>
                            <input type="text" id="date" name="date" class="small-inputUser required"
                                data-type="data" data-required="true"
                                placeholder="DD/MM/AAAA" maxlength="10"
                                onkeypress="return MascaraData(this, event)">
                            <span class="span-required">Insira uma data válida.</span>
                        </div>

                        <div class="small-inputBox">
                            <label for="time"><b>Horário: *</b></label>
                            <input type="text" id="time" name="time" class="small-inputUser required"
                                data-type="horário" data-required="true"
                                placeholder="HH:mm" maxlength="5"
                                onkeypress="return MascaraHorario(this, event)">
                            <span class="span-required">Insira um horário válido.</span>
                        </div>

                        <div class="mid-inputBox">
                            <label for="people"><b>Número de pessoas: *</b></label>
                            <input type="number" id="people" name="people" class="mid-inputUser required"
                                data-type="número de pessoas" data-required="true" min="1"
                                placeholder="Insira o número de pessoas da sua reserva">
                            <span class="span-required">Informe o número de pessoas.</span>
                        </div>
                    </div>

                    <div class="full-inputBox">
                        <label for="observation"><b>Observação:</b></label>
                        <input type="text" id="observation" name="observation" class="full-inputUser"
                            placeholder="Insira alguma observação sobre a sua reserva">
                    </div>

                    <input type="submit" value="Cadastrar-se" class="register-btn"
                        onclick="btnRegisterOnClick(event, this.form)">
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