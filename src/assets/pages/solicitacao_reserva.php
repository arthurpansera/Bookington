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

    $tipo_perfil = 'cliente';

    if (isset($_SESSION['id_usuario'])) {
        $query_tipo = "SELECT tipo_perfil FROM usuario WHERE id_usuario = ?";
        $stmt_tipo = $obj->prepare($query_tipo);
        $stmt_tipo->bind_param("i", $_SESSION['id_usuario']);
        $stmt_tipo->execute();

        $resultado_tipo = $stmt_tipo->get_result();

        if ($resultado_tipo->num_rows > 0) {
            $usuario = $resultado_tipo->fetch_assoc();
            $tipo_perfil = $usuario['tipo_perfil'];
        }
    }

    $label_nome = ($tipo_perfil === 'funcionario')
        ? 'Nome do cliente: *'
        : 'Nome: *';

    $placeholder_nome = ($tipo_perfil === 'funcionario')
        ? 'Insira o nome do cliente completo'
        : 'Insira seu nome completo';

    if (isset($_POST['name'], $_POST['company'], $_POST['service'], $_POST['date'], $_POST['time'], $_POST['people'])) {
        $nome        = $_POST['name'];
        $empresa     = $_POST['company'];
        $servico     = $_POST['service'];
        $observacao  = $_POST['observation'] ?? '';

        $data = DateTime::createFromFormat('d/m/Y', $_POST['date']);

        if (!$data) {
            $_SESSION['error_message'] = "Data inválida!";
            header("Location: cadastro_reserva.php");
            exit();
        }

        $data_reserva = $data->format('Y-m-d');
        $horario      = $_POST['time'];
        $num_pessoas  = (int) $_POST['people'];

        $obj = conecta_db();

        if (!$obj) {
            header("Location: database-error.php");
            exit;
        }

        // Insira aqui sua lógica de INSERT na tabela de reservas
        // Exemplo:
        // $query = "INSERT INTO reserva (nome, empresa, servico, data, horario, num_pessoas, observacao) VALUES (?, ?, ?, ?, ?, ?, ?)";
        // $stmt = $obj->prepare($query);
        // $stmt->bind_param("sssssiss", $nome, $empresa, $servico, $data_reserva, $horario, $num_pessoas, $observacao);
        // $stmt->execute();

        header("Location: home.php");
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
                    <a href="home.php">Voltar</a>
                </div>
            </section>

            <h1>Dados Reserva</h1>

            <section class="input-register">
                <form id="form" name="form" method="POST" action="cadastro_reserva.php">

                    <div class="full-inputBox">
                        <label for="name"><b><?php echo $label_nome; ?></b></label>
                        <input type="text" id="name" name="name" class="full-inputUser required" data-type="nome" data-required="true" placeholder="<?php echo $placeholder_nome; ?>">
                        <span class="span-required">Nome não pode conter números e caracteres especiais.</span>
                    </div>

                    <div class="container-row">
                        <div class="mid-inputBox">
                            <label for="company"><b>Empresa/Organização: *</b></label>
                            <input type="text" id="company" name="company" class="mid-inputUser required"
                                data-type="empresa" data-required="true"
                                placeholder="Escolha o local da reserva">
                            <span class="span-required">Por favor, informe a empresa ou organização.</span>
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
                                placeholder="DD/MM/AAAA" maxlength="10"
                                onkeypress="return MascaraData(this, event)">
                            <span class="span-required">Insira uma data válida.</span>
                        </div>

                        <div class="small-inputBox">
                            <label for="time"><b>Horário: *</b></label>
                            <input type="text" id="time" name="time" class="small-inputUser required"
                                placeholder="HH:mm" maxlength="5"
                                onkeypress="return MascaraHorario(this, event)">
                            <span class="span-required">Insira um horário válido.</span>
                        </div>

                        <div class="mid-inputBox">
                            <label for="people"><b>Número de pessoas: *</b></label>
                            <input type="number" id="people" name="people" class="mid-inputUser required"
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