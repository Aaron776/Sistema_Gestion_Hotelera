<?php
require_once '../conexion/session.php';
require_once '../conexion/bd.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['telefono']) && isset($_POST['confirmar_password']) && isset($_POST['direccion']) && isset($_POST['cedula'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $cedula = trim($_POST['cedula']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];
    $errores = [];

    // Validacion y sanitizacion
    if (empty($nombre)) {
        $errores[] = "El nombre es requerido";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $nombre)) {
        $errores[] = "El nombre solo puede contener letras y espacios";
    } elseif (strlen($nombre) > 100) {
        $errores[] = "El nombre es muy largo";
    }

    if (empty($cedula)) {
        $errores[] = "La cedula es requerida";
    } elseif (!preg_match("/^[0-9]+$/", $cedula)) {
        $errores[] = "La cedula solo puede contener numeros";
    } elseif (strlen($cedula) > 20) {
        $errores[] = "La cedula es muy larga";
    }

    if (!empty($direccion)) {
        if (strlen($direccion) > 255) {
            $errores[] = "La direccion es muy larga";
        }
    }

    if (!empty($telefono)) {
        if (!preg_match("/^[0-9]+$/", $telefono)) {
            $errores[] = "El telefono solo puede contener numeros";
        } elseif (strlen($telefono) > 20) {
            $errores[] = "El telefono es muy largo";
        }
    }

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

    if (empty($confirmar_password)) {
        $errores[] = "La confirmacion de la contraseña es requerida";
    } elseif ($password !== $confirmar_password) {
        $errores[] = "Las contraseñas no coinciden";
    }

    // Verificar si existe otro usuario con el mimso email o telefono o cedula
    if (empty($errores)) {
        try {
            // Evitamos un falso positivo si $telefono está vacío
            $query = "SELECT email,telefono,cedula FROM usuarios WHERE email=:email OR cedula=:cedula";
            if (!empty($telefono)) {
                $query .= " OR telefono=:telefono";
            }

            $sql = $conexion->prepare($query);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            if (!empty($telefono)) {
                $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            }

            $sql->execute();
            $usuario_existe = $sql->fetch(PDO::FETCH_OBJ);
            if ($usuario_existe) {
                if ($usuario_existe->email === $email) {
                    $errores[] = "Ya existe un usuario con este email";
                }
                if ($usuario_existe->cedula === $cedula) {
                    $errores[] = "Ya existe un usuario con esta cedula";
                }
                if (!empty($telefono) && $usuario_existe->telefono === $telefono) {
                    $errores[] = "Ya existe un usuario con este telefono";
                }
            }
        } catch (PDOException $e) {
            error_log("Error al verificar usuario existente: " . $e->getMessage());
            $errores[] = "Error al verificar usuario existente";
        }
    }

    // Si no hay errores, registrar usaurio cliente
    if (empty($errores)) {
        try {
            $password_hasheada = password_hash($password, PASSWORD_DEFAULT);
            $sql = $conexion->prepare("INSERT INTO usuarios (nombre,cedula,email,password,telefono,direccion,rol) VALUES (:nombre,:cedula,:email,:password_hasheada,:telefono,:direccion,'cliente')");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":password_hasheada", $password_hasheada, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->bindParam(":direccion", $direccion, PDO::PARAM_STR);
            $sql->execute();

            $_SESSION['exito'] = "Usuario registrado correctamente";
            header("Location: ../registro.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error al registrar usuario: " . $e->getMessage());
            $_SESSION['errores'] = ["Error al registrar el usuario. Intente nuevamente."];
            header("Location: ../registro.php");
            exit;
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../registro.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error al registrar usuario"];
    header("Location: ../registro.php");
    exit;
}
