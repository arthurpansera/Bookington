<?php
    $pageTitle = 'Bookington – Login';
    require_once 'bookington/includes/auth.php';
    startSession();

    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit;
    }

    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        if ($email && $senha) {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['login_success'] = true;
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'E-mail ou senha inválidos.';
            }
        } else {
            $error = 'Preencha todos os campos.';
        }
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?></title>
        <link rel="stylesheet" href="bookington/css/style.css">
    </head>
    <body>
        <nav class="navbar">
            <a href="index.php" class="navbar-brand">
                <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="5" y="8" width="30" height="28" rx="4" stroke="white" stroke-width="2"/>
                    <rect x="12" y="4" width="3" height="8" rx="1.5" fill="white"/>
                    <rect x="25" y="4" width="3" height="8" rx="1.5" fill="white"/>
                    <line x1="5" y1="17" x2="35" y2="17" stroke="white" stroke-width="2"/>
                    <rect x="10" y="22" width="5" height="4" rx="1" fill="white"/>
                    <rect x="18" y="22" width="5" height="4" rx="1" fill="white"/>
                    <rect x="26" y="22" width="5" height="4" rx="1" fill="white"/>
                </svg>
                Bookington
            </a>
        </nav>

        <div class="page-wrapper">
            <div class="login-card">
                <div class="login-left">
                    <h2>Bem-vindo ao Bookington!</h2>
                    <p>Ainda não possui uma conta?</p>
                    <a href="cadastro_cliente.php" class="btn btn-outline-white">Cadastre-se</a>
                </div>
                <div class="login-right">
                    <h3>Login</h3>
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="form-group" style="margin-bottom:1rem">
                            <div class="input-wrap">
                                <input type="email" name="email" placeholder="exemplo@gmail.com" required autocomplete="email">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:1.5rem">
                            <div class="input-wrap">
                                <input type="password" id="login-senha" name="senha" placeholder="Insira sua senha" required autocomplete="current-password">
                                <button type="button" id="toggle-login-pass" class="toggle-password">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%">Login</button>
                    </form>
                </div>
            </div>
        </div>

        <footer class="footer">
            &copy; 2026 – Bookington – Reservas inteligentes, resultados eficientes. Todos os direitos reservados.
        </footer>
        <script src="js/app.js"></script>
    </body>
</html>