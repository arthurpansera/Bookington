<?php
    include('conecta_db.php');

    session_start();

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

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nome           = trim($_POST['nome']);
        $cpf            = trim($_POST['cpf']);
        $nascimento     = trim($_POST['nascimento']);
        $telefone       = trim($_POST['telefone']);
        $email          = trim($_POST['email']);
        $senha          = $_POST['senha'];
        $confirma_senha = $_POST['confirma_senha'];

        if ($senha !== $confirma_senha) {
            $_SESSION['error_message'] = "As senhas não coincidem.";
            header("Location: cadastro.php");
            exit();
        }

        if (strlen($senha) < 6) {
            $_SESSION['error_message'] = "A senha deve ter no mínimo 6 caracteres.";
            header("Location: cadastro.php");
            exit();
        }

        $obj = conecta_db();

        if (!$obj) {
            header("Location: database-error.php");
            exit;
        }

        // Verifica se o e-mail ou CPF já estão cadastrados
        $query = "SELECT c.email FROM contato c WHERE c.email = ?
                  UNION
                  SELECT u.cpf FROM usuario u WHERE u.cpf = ?";
        $stmt = $obj->prepare($query);
        $stmt->bind_param('ss', $email, $cpf);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "E-mail e/ou CPF já cadastrado(s).";
            header("Location: cadastro.php");
            exit();
        }

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $obj->begin_transaction();

        try {
            $query = "INSERT INTO usuario (nome, cpf, data_nascimento, senha) VALUES (?, ?, ?, ?)";
            $stmt = $obj->prepare($query);
            $stmt->bind_param('ssss', $nome, $cpf, $nascimento, $senha_hash);
            $stmt->execute();

            $id_usuario = $obj->insert_id;

            $query = "INSERT INTO contato (id_usuario, email, telefone) VALUES (?, ?, ?)";
            $stmt = $obj->prepare($query);
            $stmt->bind_param('iss', $id_usuario, $email, $telefone);
            $stmt->execute();

            $query = "INSERT INTO perfil (id_usuario, tipo_perfil, status_perfil) VALUES (?, 'cliente', 'ativo')";
            $stmt = $obj->prepare($query);
            $stmt->bind_param('i', $id_usuario);
            $stmt->execute();

            $obj->commit();

            $_SESSION['success_message'] = "Cadastro realizado com sucesso!";
            header("Location: login.php");
            exit();

        } catch (Exception $e) {
            $obj->rollback();
            $_SESSION['error_message'] = "Não foi possível concluir o cadastro. Tente novamente.";
            header("Location: cadastro.php");
            exit();
        }
    }
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookington | Cadastro</title>
    <link rel="stylesheet" href="src/styles/pages/index/cadastro.css?v=<?php echo time(); ?>">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="navbar-brand">
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
        <div class="navbar-actions">
            <span class="navbar-text">Já possui uma conta?</span>
            <a href="login.php" class="btn-nav-login">Login</a>
        </div>
    </nav>

    <div class="form-page">
        <a href="javascript:history.back()" class="btn-back">&larr; Voltar</a>

        <div class="form-card">
            <h2 class="page-title">Dados Cadastrais - Cliente</h2>

            <form id="form" name="form" method="POST" action="cadastro.php" autocomplete="off">

                <div class="form-group">
                    <label for="nome">Nome: *</label>
                    <div class="input-wrap">
                        <input type="text" id="nome" name="nome" placeholder="Insira seu nome completo" required>
                    </div>
                </div>

                <br>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cpf">CPF: *</label>
                        <div class="input-wrap">
                            <input type="text" id="cpf" name="cpf" placeholder="XX.XXX.XXX/XXXX-XX" maxlength="14" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nascimento">Data de Nascimento: *</label>
                        <div class="input-wrap">
                            <input type="date" id="nascimento" name="nascimento" required>
                        </div>
                    </div>
                </div>

                <br>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefone">Telefone: *</label>
                        <div class="input-wrap">
                            <input type="text" id="telefone" name="telefone" placeholder="(XX) XXXXX-XXXX" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email: *</label>
                        <div class="input-wrap">
                            <input type="email" id="email" name="email" placeholder="exemplo@gmail.com" required>
                        </div>
                    </div>
                </div>

                <br>

                <div class="form-group">
                    <label for="senha">Senha: *</label>
                    <div class="input-wrap">
                        <input type="password" id="senha" name="senha" placeholder="Crie uma senha" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('senha')">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <br>

                <div class="form-group">
                    <label for="confirma_senha">Confirme sua senha: *</label>
                    <div class="input-wrap">
                        <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Repita a senha" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirma_senha')">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-submit-wrap">
                    <button type="submit" class="btn btn-primary btn-cadastrar">Cadastrar-se</button>
                </div>

            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2026 - Bookington - Reservas inteligentes, resultados eficientes. Todos os direitos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }
    </script>

</body>
</html>