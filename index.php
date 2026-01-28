<?php
require_once 'includes/auth.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (login($username, $password)) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid username or password";
    }
}

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Emergency Maintenance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        a{
            text-decoration: none;
        }

        .login-container {
            max-width: 420px;
            background: white;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            border: 1px solid #e0e0e0;
        }

        .system-title {
            color: #1a1a1a;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 28px;
            letter-spacing: -0.5px;
        }

        .system-subtitle {
            color: #888;
            margin-bottom: 30px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9f9f9;
            color: #333;
        }

        .form-control:focus {
            border-color: #1a1a1a;
            background: white;
            box-shadow: 0 0 0 4px rgba(26, 26, 26, 0.1);
            color: #333;
        }

        .form-control::placeholder {
            color: #999;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 1.4rem;
            transition: color 0.3s ease;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: #1a1a1a;
        }

        .btn-login {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 12px 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 14px;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 26, 26, 0.2);
            color: white;
        }

        .btn-login:active {
            background: #1a1a1a;
            transform: translateY(0);
        }

        .alert-danger {
            background: #ffebee;
            border: 2px solid #d32f2f;
            color: #c62828;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .login-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
        }

        .login-footer small {
            color: #888;
            font-size: 12px;
            line-height: 1.6;
        }

        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }

        .required::after {
            content: ' *';
            color: #d32f2f;
            font-weight: 700;
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                border-radius: 8px;
            }

            .system-title {
                font-size: 24px;
            }

            .form-control {
                padding: 10px 12px;
                font-size: 13px;
            }

            .btn-login {
                padding: 10px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="system-title text-center">Emergency Maintenance System</h1>
        <p class="system-subtitle text-center">Kazan Neft</p>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username" class="form-label required">Username</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    placeholder="Enter your username"
                    required
                    autofocus
                    autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password" class="form-label required">Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password">
                    <i class="fas fa-eye password-toggle" id="passwordToggle" 
                       title="Toggle password visibility"></i>
                </div>
            </div>

            <button type="submit" name="login" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>

        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('passwordToggle');

        toggleIcon.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        document.getElementById('username').addEventListener('focus', function() {
            this.style.borderColor = '#1a1a1a';
        });

        document.getElementById('password').addEventListener('focus', function() {
            this.style.borderColor = '#1a1a1a';
        });
    </script>
</body>
</html>