<?php
require_once '../conexion/session.php';
require_once '../conexion/bd.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Verificar bloqueo por intentos fallidos
    if (isset($_SESSION['login_lockout'])) {
        $tiempo_restante = $_SESSION['login_lockout'] - time();
        if ($tiempo_restante > 0) {
            $minutos = ceil($tiempo_restante / 60);
            $_SESSION['errores'] = ["Demasiados intentos fallidos. Por favor, espera $minutos minuto(s) para intentar de nuevo."];
            header("Location: ../login.php");
            exit;
        } else {
            // El bloqueo expiró, limpiar variables
            unset($_SESSION['login_attempts']);
            unset($_SESSION['login_lockout']);
        }
    }

    // Recibir datos
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $errores = [];

    // Validacion y sanitizacion
    if (empty($email)) {
        $errores[] = "El email es requerido";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no es valido";
    } elseif (strlen($email) > 100) {
        $errores[] = "El email es muy largo";
    }

    if (empty($password)) {
        $errores[] = "La contraseña es requerida";
    } elseif (strlen($password) < 5) {
        $errores[] = "La contraseña debe tener al menos 5 caracteres";
    }

    // Verificar credenciales (todo en una consulta para evitar user enumeration)
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id, nombre, rol, password, estado FROM usuarios WHERE email = :email");
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->execute();
            $usuario = $sql->fetch(PDO::FETCH_OBJ);

            if (!$usuario || $usuario->estado === 'inactivo' || !password_verify($password, $usuario->password)) {
                // Mensaje único: no revelar si el email existe, está inactivo o la clave es incorrecta
                if (!isset($_SESSION['login_attempts'])) {
                    $_SESSION['login_attempts'] = 1;
                } else {
                    $_SESSION['login_attempts']++;
                }

                if ($_SESSION['login_attempts'] >= 3) {
                    $_SESSION['login_lockout'] = time() + 180;
                    $_SESSION['errores'] = ["Has superado el límite de 3 intentos fallidos. Por favor, espera 3 minutos."];
                } else {
                    $intentos_restantes = 3 - $_SESSION['login_attempts'];
                    $_SESSION['errores'] = ["Credenciales incorrectas. Te quedan $intentos_restantes intento(s)."];
                }

                header("Location: ../login.php");
                exit;
            }

            // 1. Actualizar el Ultimo acceso
            try {
                $fechaActual = date('Y-m-d H:i:s');
                $sqlAcceso = $conexion->prepare("UPDATE usuarios SET ultimo_acceso = :fecha WHERE id = :id");
                $sqlAcceso->bindParam(':fecha', $fechaActual, PDO::PARAM_STR);
                $sqlAcceso->bindParam(':id', $usuario->id, PDO::PARAM_INT);
                $sqlAcceso->execute();
            } catch (PDOException $e) {
                error_log("Error al actualizar el ultimo acceso: " . $e->getMessage());
            }

            // 2. Establecer variables de sesión
            $_SESSION['id_usuario'] = $usuario->id;
            $_SESSION['nombre'] = $usuario->nombre;
            $_SESSION['rol'] = $usuario->rol;
            $_SESSION['logueado'] = true;

            unset($_SESSION['login_attempts']);
            unset($_SESSION['login_lockout']);

            switch ($usuario->rol) {
                case 'admin':
                    header("Location: ../admin/dash_admin.php");
                    break;
                case 'cliente':
                    header("Location: ../cliente/dash_cliente.php");
                    break;
                case 'recepcionista':
                    header("Location: ../recepcionista/dash_recepcionista.php");
                    break;
                default:
                    header("Location: ../login.php");
                    break;
            }
            exit;
        } catch (PDOException $e) {
            error_log("Error al iniciar sesión: " . $e->getMessage());
            $_SESSION['errores'] = ["Error interno al iniciar sesión. Intente nuevamente."];
            header("Location: ../login.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../login.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error al iniciar sesión"];
    header("Location: ../login.php");
    exit;
}
