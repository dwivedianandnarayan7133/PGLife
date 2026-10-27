<?php

function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function require_role($roles)
{
    require_login();
    if (!is_array($roles)) {
        $roles = array($roles);
    }
    $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
    if (!in_array($user_role, $roles, true)) {
        header('Location: ../index.php');
        exit;
    }
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function current_user_role()
{
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function load_user_session($conn, $user_id)
{
    $stmt = mysqli_prepare($conn, 'SELECT id, full_name, email, role FROM users WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
    }

    return $user;
}

function json_response($data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
