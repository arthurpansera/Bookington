<?php
    include('../../../conecta_db.php');
    include('buscar_nome.php');

    session_start();

    if (isset($_SESSION['error_message'])) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Erro!',
                    text: '{$_SESSION['error_message']}',
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#5F0D07',
                    allowOutsideClick: true,
                    heightAuto: false
                });
            });
        </script>";
        unset($_SESSION['error_message']);
    }

    if (isset($_POST['name'], $_POST['cpf'], $_POST['birthYear'], $_POST['telephone'], $_POST['email'], $_POST['password'], $_POST['confirm-pass'], $_POST['company'])) {
        $nome = $_POST['name'];
        $cpf = $_POST['cpf'];
        $telefone = $_POST['telephone'];
        $email = $_POST['email'];
        $empresa = trim($_POST['company']);

        if (
            empty($_POST['name']) ||
            empty($_POST['cpf']) ||
            empty($_POST['birthYear']) ||
            empty($_POST['telephone']) ||
            empty($_POST['email']) ||
            empty($_POST['password']) ||
            empty($_POST['confirm-pass'])
        ) {
            $_SESSION['error_message'] = "Preencha todos os campos obrigatórios.";
            header("Location: cadastro_cliente.php");
            exit();
        }

        $data = DateTime::createFromFormat('d/m/Y', $_POST['birthYear']);

        if (!$data || $data->format('d/m/Y') !== $_POST['birthYear']) {
            $_SESSION['error_message'] = "Data de nascimento inválida!";
            header("Location: cadastro_cliente.php");
            exit();
        }

        $data_nascimento = $data->format('Y-m-d');

        if ($_POST['password'] !== $_POST['confirm-pass']) {
            $_SESSION['error_message'] = "As senhas não coincidem!";
            header("Location: cadastro_funcionario.php");
            exit();
        }

        $senha = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $obj = conecta_db();

        if (!$obj) {
            die("Erro na conexão.");
        }

        $query_check_email = "SELECT id_usuario FROM usuario WHERE email = ?";
        $stmt_check_email = $obj->prepare($query_check_email);
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        $query_check_cpf = "SELECT id_usuario FROM usuario WHERE cpf = ?";
        $stmt_check_cpf = $obj->prepare($query_check_cpf);
        $stmt_check_cpf->bind_param("s", $cpf);
        $stmt_check_cpf->execute();
        $stmt_check_cpf->store_result();

        if ($stmt_check_email->num_rows > 0 || $stmt_check_cpf->num_rows > 0) {
            $_SESSION['error_message'] = "Usuário já cadastrado!";
            header("Location: cadastro_cliente.php");
            exit();
        }

        $query = "INSERT INTO usuario(nome, data_nasc, cpf, telefone, email, senha, tipo_perfil) VALUES (?, ?, ?, ?, ?, ?, 'funcionario')";

        $stmt = $obj->prepare($query);

        if (!$stmt) {
            die("<span class='alert alert-danger'><h5>Erro na preparação da query de usuário: " . $obj->error . "</h5></span>");
        }

        $stmt->bind_param("ssssss", $nome, $data_nascimento, $cpf, $telefone, $email, $senha);

        if (!$stmt->execute()) {
            die("<span class='alert alert-danger'><h5>Erro ao cadastrar o usuário: " . $stmt->error . "</h5></span>");
        }

        $id_usuario = $obj->insert_id;

        $query = "SELECT id_empresa FROM empresa WHERE nome_empresa = ?";
        $stmt = $obj->prepare($query);
        $stmt->bind_param("s", $empresa);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $dados_empresa = $resultado->fetch_assoc();
            $id_empresa = $dados_empresa['id_empresa'];

        } else {
            $query = "INSERT INTO empresa (nome_empresa)VALUES (?)";

            $stmt = $obj->prepare($query);
            $stmt->bind_param("s", $empresa);

            if (!$stmt->execute()) {
                die("Erro ao cadastrar empresa: " . $stmt->error);
            }

            $id_empresa = $obj->insert_id;
        }

        $query = "INSERT INTO funcionario(id_usuario, id_empresa) VALUES (?, ?)";

        $stmt = $obj->prepare($query);
        $stmt->bind_param("ii", $id_usuario, $id_empresa);

        if (!$stmt->execute()) {
            die("Erro ao cadastrar funcionário: " . $stmt->error);
        }

        $_SESSION['user_logged_in'] = true;
        $_SESSION['id_usuario'] = $id_usuario;

        header("Location: home_funcionario.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookington | Cadastrar Novo Funcionário</title>
    <link rel="stylesheet" href="../../styles/pages/cadastro/cadastro.css">
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

    <section class="main-content">
        <section class="box-container">
            <section class="btn-back">
                <div class="back-btn">
                    <a href="home_funcionario.php">Voltar</a>
                </div>
            </section>
            
            <h1>Cadastrar Novo Funcionário</h1>

            <section class="input-register">
                <form id="form" name="form" method="POST" action="cadastro_funcionario.php">
                    <div class="full-inputBox">
                        <label for="name"><b>Nome: *</b></label>
                        <input type="text" id="name" name="name" class="full-inputUser required" data-type="nome" data-required="true" placeholder="Insira seu nome completo">
                        <span class="span-required">Nome não pode conter números e caracteres especiais.</span>
                    </div>

                    <div class="container-row">
                        <div class="mid-inputBox">
                            <label for="cpf"><b>CPF: *</b></label>
                            <input type="text" name="cpf" id="cpf" class="mid-inputUser required" data-type="CPF" data-required="true"
                                placeholder="XXX.XXX.XXX-XX" maxlength="14" onkeypress="return MascaraCPF(this, event)">
                            <span class="span-required">Por favor, insira um CPF válido.</span>
                        </div>

                        <div class="mid-inputBox">
                            <label for="birthYear"><b>Data de Nascimento: *</b></label>
                            <input type="text" name="birthYear" id="birthYear" class="mid-inputUser required" data-type="data de nascimento" data-required="true"
                                placeholder="DD/MM/AAAA" maxlength="10" onkeypress="return MascaraData(this, event)">
                            <span class="span-required">Insira uma data de nascimento válida.</span>
                        </div>
                    </div>

                    <div class="container-row">
                         <div class="mid-inputBox">
                            <label for="telephone"><b>Telefone: *</b></label>
                            <input type="text" name="telephone" id="telephone" class="mid-inputUser required" data-type="telefone" data-required="true" placeholder="(XX) XXXXX-XXXX" maxlength="15" onkeypress="return MascaraTelefone(this, event)">
                            <span class="span-required">Por favor, insira um telefone válido.</span>
                        </div>

                        <div class="mid-inputBox">
                            <label for="email"><b>E-mail: *</b></label>
                            <input type="text" id="email" name="email"class="full-inputUser required" data-type="e-mail" data-required="true" placeholder="exemplo@gmail.com">
                            <span class="span-required">Insira um e-mail válido!</span>
                        </div>
                    </div>
            
                    <div class="full-inputBox">
                        <label for="company"><b>Empresa/Organização: *</b></label>
                        <input type="text" id="company" name="company" class="full-inputUser required" data-type="empresa" data-required="true" placeholder="Insira o nome da empresa/organização da qual você faz parte">
                    </div>

                    <div class="full-inputBox">
                        <label for="password"><b>Senha: *</b></label>
                        <input type="password" name="password" id="password" class="full-inputUser required" data-type="senha" data-required="true" placeholder="Crie uma senha">
                        <span class="span-required">Sua senha deve conter no mínimo 8 caracteres, combinando letras maiúsculas, minúsculas, números e símbolos especiais.</span>
                    </div>

                    <div class="full-inputBox">
                        <label for="confirm-pass"><b>Confirme sua senha: *</b></label>
                        <input type="password" name="confirm-pass" id="confirm-pass" class="full-inputUser required" data-type="confirmar senha" data-required="true" placeholder="Repita a senha">
                        <span class="span-required">As senhas não coincidem.</span>
                    </div>

                    <input type="submit" value="Cadastrar-se" class="register-btn" onclick="btnRegisterOnClick(event, this.form)">
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