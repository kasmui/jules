<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and security headers
session_start();
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://translate.google.com https://translate.googleapis.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; img-src 'self' data: https://*; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; frame-src 'self' https://translate.google.com");

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Input sanitization function
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

// User data file (JSON)
define('USER_DATA_FILE', 'users.json');

// Function to get user data
function get_users() {
    if (!file_exists(USER_DATA_FILE)) {
        file_put_contents(USER_DATA_FILE, json_encode([]));
    }
    return json_decode(file_get_contents(USER_DATA_FILE), true);
}

// Function to save user data
function save_users($users) {
    file_put_contents(USER_DATA_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

// Authentication functions
function register_user($username, $password) {
    $users = get_users();
    if (isset($users[$username])) {
        return false; // User already exists
    }
    $users[$username] = password_hash($password, PASSWORD_DEFAULT);
    save_users($users);
    return true;
}

function verify_user($username, $password) {
    $users = get_users();
    if (isset($users[$username]) && password_verify($password, $users[$username])) {
        return true;
    }
    return false;
}

function check_auth() {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('Location: dashboard.php?page=login');
        exit();
    }
}

// Handle Login/Logout/Register
if (isset($_POST['action'])) {
    // All actions require a CSRF token check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed.');
    }

    $action = sanitize_input($_POST['action']);

    if ($action === 'login') {
        $username = sanitize_input($_POST['username']);
        $password = sanitize_input($_POST['password']);
        if (verify_user($username, $password)) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $username;
            header('Location: dashboard.php?page=dashboard');
            exit();
        } else {
            $_SESSION['login_error'] = 'Invalid username or password.';
            header('Location: dashboard.php?page=login');
            exit();
        }
    } elseif ($action === 'logout') {
        unset($_SESSION['authenticated']);
        unset($_SESSION['username']);
        session_destroy();
        header('Location: dashboard.php?page=login');
        exit();
    } elseif ($action === 'register') {
        $username = sanitize_input($_POST['username']);
        $password = sanitize_input($_POST['password']);
        if (register_user($username, $password)) {
            $_SESSION['registration_success'] = 'Registration successful. Please log in.';
            header('Location: dashboard.php?page=login');
            exit();
        } else {
            $_SESSION['registration_error'] = 'Registration failed. Username might already exist.';
            header('Location: dashboard.php?page=register');
            exit();
        }
    }
}

// Determine current page
$page = isset($_GET['page']) ? sanitize_input($_GET['page']) : 'dashboard';

// If not logged in and trying to access anything other than login/register, redirect
if (!isset($_SESSION['authenticated']) && !in_array($page, ['login', 'register'])) {
    header('Location: dashboard.php?page=login');
    exit();
}

// Regenerate CSRF token on each page load for enhanced security
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes" />
    <meta name="robots" content="noindex, nofollow" />
    <title data-i18n="app_title">Workship Framework</title>

    <link rel="shortcut icon" href="assets/img/favicon.ico" sizes="32x32" />
    <link rel="icon" href="assets/img/favicon-192x192.png" sizes="192x192" />
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Google Translate Banner */
        .goog-te-banner-frame.skiptranslate {display: none !important;}
        body {top: 0px !important;}
        .goog-logo-link { display:none !important; }
        .goog-te-gadget { font-size: 0px !important; }

        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --sidebar-width-expanded: 250px;
            --sidebar-width-collapsed: 70px;
            --gap: 20px;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            --page-width: 96%;
        }

        /* Theme Colors */
        body.theme-default { --primary-color: #2c3e50; --secondary-color: #3498db; --accent-color: #e74c3c; }
        body.theme-blue { --primary-color: #3498db; --secondary-color: #2980b9; --accent-color: #e74c3c; }
        body.theme-green { --primary-color: #27ae60; --secondary-color: #229954; --accent-color: #e74c3c; }
        body.theme-red { --primary-color: #e74c3c; --secondary-color: #c0392b; --accent-color: #3498db; }
        body.theme-purple { --primary-color: #9b59b6; --secondary-color: #8e44ad; --accent-color: #e74c3c; }
        body.theme-orange { --primary-color: #f39c12; --secondary-color: #e67e22; --accent-color: #3498db; }
        body.theme-dark { --primary-color: #34495e; --secondary-color: #2c3e50; --accent-color: #e74c3c; }
        body.theme-teal { --primary-color: #1abc9c; --secondary-color: #16a085; --accent-color: #e74c3c; }

        /* Font Families */
        body.font-poppins { --font-family: 'Poppins', sans-serif; }
        body.font-amiri { --font-family: 'Amiri', serif; }
        body.font-arial { --font-family: Arial, sans-serif; }
        body.font-verdana { --font-family: Verdana, sans-serif; }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
            transition: var(--transition);
        }

        body {
            overflow-x: hidden;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--dark-color);
        }

        .container-wrapper {
            display: flex;
            flex: 1;
            position: relative;
            overflow: hidden;
            min-height: calc(100vh - 60px);
            width: 100%;
            margin: 0 auto;
            gap: var(--gap);
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width-collapsed);
            background-color: var(--primary-color);
            color: white;
            padding: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
            overflow-y: auto;
            flex-shrink: 0;
            transition: width 0.3s ease;
        }

        .sidebar.sidebar-expanded {
            width: var(--sidebar-width-expanded);
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(to right, var(--primary-color), rgba(44, 62, 80, 0.8));
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }

        .sidebar:not(.sidebar-expanded) .sidebar-header h2 {
            display: none;
        }

        .sidebar-header h2 {
            font-size: 1.3rem;
            white-space: nowrap;
            font-weight: 600;
            transition: var(--transition);
        }

        .toggle-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px 8px;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2), inset 0 1px 2px rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .nav-links {
            flex: 1;
            overflow-y: auto;
            padding: 15px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            margin: 5px 10px;
            border-radius: var(--border-radius);
            white-space: nowrap;
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1), inset 0 -1px 2px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15), inset 0 -2px 3px rgba(0, 0, 0, 0.1);
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar:not(.sidebar-expanded) .nav-link span {
            display: none;
        }

        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            font-size: 0.8rem;
            opacity: 0.8;
            margin-top: auto;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #f5f7fa;
            border-radius: var(--border-radius);
        }

        .content-header {
            margin-bottom: 20px;
            padding: 15px 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            color: var(--primary-color);
        }

        .user-info i {
            font-size: 1.2rem;
            color: var(--secondary-color);
        }

        .social-links {
            display: flex;
            gap: 10px;
            margin-left: 20px;
        }

        .social-links a {
            color: var(--secondary-color);
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .social-links a:hover {
            color: var(--primary-color);
            transform: scale(1.1);
        }

        .content-header h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .content-section {
            display: none;
            animation: fadeIn 0.5s ease;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .content-section.active {
            display: block;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.2rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }

        .table td {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }

        .table tr:hover {
            background-color: #f9f9f9;
        }

        /* Form Styles */
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 500; color: var(--dark-color); }
        .form-control { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: var(--border-radius); font-size: 1rem; transition: var(--transition); }
        .form-control:focus { border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2); outline: none; }
        .btn { display: inline-block; padding: 10px 20px; background-color: var(--secondary-color); color: white; border: none; border-radius: var(--border-radius); cursor: pointer; font-size: 1rem; font-weight: 500; text-align: center; transition: var(--transition); box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
        .btn:hover { background-color: #2980b9; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15); }
        .btn-primary { background-color: var(--primary-color); }
        .btn-success { background-color: var(--success-color); }
        .btn-warning { background-color: var(--warning-color); }
        .btn-danger { background-color: var(--danger-color); }

        /* Alert Styles */
        .alert { padding: 15px; border-radius: var(--border-radius); margin-bottom: 20px; position: relative; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        /* Loading Indicator */
        .loading { display: flex; justify-content: center; align-items: center; height: 200px; font-size: 1.2rem; color: var(--primary-color); }
        .spinner { border: 4px solid rgba(0, 0, 0, 0.1); border-radius: 50%; border-top: 4px solid var(--primary-color); width: 30px; height: 30px; animation: spin 1s linear infinite; margin-right: 10px; }

        /* Animation */
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .container-wrapper { flex-direction: column; gap: 0; }
            .sidebar { width: 100%; height: auto; position: static; }
            .sidebar.sidebar-expanded, .sidebar:not(.sidebar-expanded) { width: 100%; }
            .sidebar .nav-links { display: flex; flex-wrap: wrap; justify-content: center; }
            .sidebar .nav-link { margin: 5px; flex: 1 1 auto; max-width: 150px; justify-content: center; }
            .sidebar .nav-link i { margin-right: 5px; }
            .sidebar-header h2, .sidebar:not(.sidebar-expanded) .nav-link span { display: block; }
            .main-content { width: 100%; }
            .content-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .social-links { margin-left: 0; margin-top: 10px; }
        }
        @media (max-width: 768px) {
            .content-header { padding: 10px 15px; }
            .nav-link { padding: 8px 12px; font-size: 0.9rem; }
            .card { padding: 15px; }
            .sidebar .nav-link i { margin-right: 0; }
        }

        /* Utility Classes */
        .text-center { text-align: center; } .text-right { text-align: right; } .text-left { text-align: left; }
        .mt-1 { margin-top: 5px; } .mt-2 { margin-top: 10px; } .mt-3 { margin-top: 15px; } .mt-4 { margin-top: 20px; } .mt-5 { margin-top: 25px; }
        .mb-1 { margin-bottom: 5px; } .mb-2 { margin-bottom: 10px; } .mb-3 { margin-bottom: 15px; } .mb-4 { margin-bottom: 20px; } .mb-5 { margin-bottom: 25px; }
        .p-1 { padding: 5px; } .p-2 { padding: 10px; } .p-3 { padding: 15px; } .p-4 { padding: 20px; } .p-5 { padding: 25px; }
        .d-flex { display: flex; } .justify-between { justify-content: space-between; } .align-center { align-items: center; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: var(--primary-color); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }

        /* Theme/Font/Width Selectors */
        .theme-color, .font-option, .width-option { width: 80px; height: 80px; border-radius: var(--border-radius); cursor: pointer; border: 3px solid #eee; transition: var(--transition); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: var(--dark-color); text-align: center; line-height: 1.2; }
        .theme-color:hover, .font-option:hover, .width-option:hover { transform: scale(1.05); }
        .selected-option { border: 3px solid var(--primary-color) !important; box-shadow: 0 0 10px rgba(0,0,0,0.2); }

        /* Login/Register Page Styling */
        .auth-container { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: var(--light-color); }
        .auth-card { background-color: white; padding: 30px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); width: 100%; max-width: 400px; text-align: center; }
        .auth-card h2 { margin-bottom: 20px; color: var(--primary-color); }
        .auth-card .form-control { margin-bottom: 15px; }
    </style>
</head>
<body oncontextmenu="return false;">
    <script>
        // Prevent right-click and keyboard shortcuts for dev tools
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if (e.keyCode === 123 || (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) || (e.ctrlKey && e.keyCode === 85) || (e.ctrlKey && e.keyCode === 83)) {
                e.preventDefault();
            }
        });

        // CSRF Protection for AJAX
        function getCSRFToken() { return '<?php echo $_SESSION['csrf_token']; ?>'; }
    </script>

    <?php if (!isset($_SESSION['authenticated'])) { ?>
        <div class="auth-container">
            <div class="auth-card">
                <?php if ($page === 'login') { ?>
                    <h2>Login</h2>
                    <?php if (isset($_SESSION['login_error'])) { echo '<div class="alert alert-danger">' . $_SESSION['login_error'] . '</div>'; unset($_SESSION['login_error']); } ?>
                    <?php if (isset($_SESSION['registration_success'])) { echo '<div class="alert alert-success">' . $_SESSION['registration_success'] . '</div>'; unset($_SESSION['registration_success']); } ?>
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group">
                            <input type="text" name="username" class="form-control" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <p class="mt-3">Don't have an account? <a href="dashboard.php?page=register">Register here</a></p>
                <?php } elseif ($page === 'register') { ?>
                    <h2>Register</h2>
                    <?php if (isset($_SESSION['registration_error'])) { echo '<div class="alert alert-danger">' . $_SESSION['registration_error'] . '</div>'; unset($_SESSION['registration_error']); } ?>
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="action" value="register">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group">
                            <input type="text" name="username" class="form-control" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" class="form-control" placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Register</button>
                    </form>
                    <p class="mt-3">Already have an account? <a href="dashboard.php?page=login">Login here</a></p>
                <?php } ?>
            </div>
        </div>
    <?php } else { // Authenticated user ?>
        <div class="container-wrapper">
            <aside class="sidebar">
                <div class="sidebar-header">
                    <h2 data-i18n="app_title">Workship</h2>
                    <button class="toggle-btn" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>

                <div class="nav-links">
                    <a href="dashboard.php?page=dashboard" class="nav-link <?php echo ($page === 'dashboard' || $page === '') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i><span data-i18n="dashboard">Dashboard</span></a>
                    <a href="dashboard.php?page=products" class="nav-link <?php echo ($page === 'products') ? 'active' : ''; ?>"><i class="fas fa-box-open"></i><span data-i18n="products">Products</span></a>
                    <a href="dashboard.php?page=orders" class="nav-link <?php echo ($page === 'orders') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i><span data-i18n="orders">Orders</span></a>
                    <a href="dashboard.php?page=customers" class="nav-link <?php echo ($page === 'customers') ? 'active' : ''; ?>"><i class="fas fa-users"></i><span data-i18n="customers">Customers</span></a>
                    <a href="dashboard.php?page=analytics" class="nav-link <?php echo ($page === 'analytics') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i><span data-i18n="analytics">Analytics</span></a>
                    <a href="dashboard.php?page=calendar" class="nav-link <?php echo ($page === 'calendar') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i><span data-i18n="calendar">Calendar</span></a>
                    <a href="dashboard.php?page=messages" class="nav-link <?php echo ($page === 'messages') ? 'active' : ''; ?>"><i class="fas fa-envelope"></i><span data-i18n="messages">Messages</span></a>
                    <a href="dashboard.php?page=settings" class="nav-link <?php echo ($page === 'settings') ? 'active' : ''; ?>"><i class="fas fa-cog"></i><span data-i18n="settings">Settings</span></a>
                    <a href="dashboard.php?page=profile" class="nav-link <?php echo ($page === 'profile') ? 'active' : ''; ?>"><i class="fas fa-user-circle"></i><span data-i18n="profile">Profile</span></a>
                    <a href="#" class="nav-link" onclick="document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt"></i><span data-i18n="logout">Logout</span></a>
                    <form id="logout-form" action="dashboard.php" method="POST" style="display: none;">
                        <input type="hidden" name="action" value="logout">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    </form>
                </div>

                <div class="sidebar-footer">
                    <div class="sidebar-logo">
                        <h4>&copy; <?php echo date('Y'); ?> Workship</h4>
                    </div>
                </div>
            </aside>

            <main class="main-content">
                <div class="content-header">
                    <h3 id="contentTitle"><?php echo ucwords(str_replace('-', ' ', $page)); ?></h3>
                    <div class="d-flex align-items-center">
                        <?php if (isset($_SESSION['username'])) { ?>
                            <div class="user-info me-3">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </div>
                        <?php } ?>
                        <span id="current-date" style="font-weight: 500; color: var(--primary-color);"></span>
                        <div class="social-links">
                            <a href="https://www.google.com/maps/place/Patemon,+Kec.+Gn.+Pati,+Kota+Semarang,+Jawa+Tengah/@-7.068214,110.3863383,15z/data=!3m1!4b1!4m6!3m5!1s0x2e708968a9b5ccc3:0x5027a76e356e080!8m2!3d-7.0704486!4d110.3993969!16s%2Fg%2F122h619c?entry=ttu&g_ep=EgoyMDI1MDYwOC4wIKXMDSoASAFQAw%3D%3D" target="_blank" title="Map"><i class="fas fa-map-marked-alt"></i></a>
                            <a href="https://www.facebook.com/anaskasmui" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
                            <a href="https://www.youtube.com/@kasmui" target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>
                            <a href="https://wa.me/62818294312" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                            <a href="https://t.me/433309162" target="_blank" title="Telegram"><i class="fab fa-telegram-plane"></i></a>
                            <a href="mailto:kasmui@mail.unnes.ac.id" title="Email"><i class="fas fa-envelope"></i></a>
                            <a href="javascript:window.print();" title="Print Page"><i class="fas fa-print"></i></a>
                            <a href="#" id="share-button" title="Share Page"><i class="fas fa-share-alt"></i></a>
                        </div>
                    </div>
                </div>

                <?php
                // Include page content based on $page variable
                $template_path = 'templates/' . $page . '.php';
                if (file_exists($template_path)) {
                    include $template_path;
                } else {
                    include 'templates/404.php'; // Not Found page
                }
                ?>
            </main>
        </div>

        <footer style="background-color: var(--primary-color); color: white; padding: 15px; text-align: center;">
            <div style="max-width: var(--page-width); margin: 0 auto;">
                <p style="margin: 0;">&copy; <?php echo date('Y'); ?> Workship Framework. All rights reserved.</p>
            </div>
        </footer>
    <?php } // End authenticated user block ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set current date
            const dateEl = document.getElementById('current-date');
            if (dateEl) {
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                dateEl.textContent = new Date().toLocaleDateString('en-US', options);
            }

            // Initialize chart (only if dashboard is active)
            if (document.getElementById('reportChart')) {
                const ctx = document.getElementById('reportChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['January', 'February', 'March', 'April', 'May', 'June'],
                        datasets: [{
                            label: 'User Registrations',
                            data: [12, 19, 3, 5, 2, 3],
                            backgroundColor: 'rgba(52, 152, 219, 0.7)',
                            borderColor: 'rgba(52, 152, 219, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Content Created',
                            data: [8, 15, 7, 12, 9, 14],
                            backgroundColor: 'rgba(46, 204, 113, 0.7)',
                            borderColor: 'rgba(46, 204, 113, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: { responsive: true, scales: { y: { beginAtZero: true } } }
                });
            }

            // Sidebar Toggle
            const toggleBtn = document.getElementById('toggleSidebar');
            const sidebar = document.querySelector('.sidebar');
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('sidebar-expanded');
                    const icon = this.querySelector('i');
                    if (sidebar.classList.contains('sidebar-expanded')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-chevron-left');
                    } else {
                        icon.classList.remove('fa-chevron-left');
                        icon.classList.add('fa-bars');
                    }
                });
            }

            // Theme changer function
            window.changeTheme = function(themeName, el) {
                document.body.className = document.body.className.replace(/theme-[\w-]+/, '');
                document.body.classList.add('theme-' + themeName);
                localStorage.setItem('appTheme', themeName);

                document.querySelectorAll('.theme-color').forEach(e => e.classList.remove('selected-option'));
                if(el) el.classList.add('selected-option');
            };

            // Font changer function
            window.changeFont = function(fontName, el) {
                document.body.className = document.body.className.replace(/font-[\w-]+/, '');
                document.body.classList.add('font-' + fontName);
                localStorage.setItem('appFont', fontName);

                document.querySelectorAll('.font-option').forEach(e => e.classList.remove('selected-option'));
                if(el) el.classList.add('selected-option');
            };

            // Page width changer function
            window.changePageWidth = function(width, el) {
                document.documentElement.style.setProperty('--page-width', width);
                localStorage.setItem('pageWidth', width);

                document.querySelectorAll('.width-option').forEach(e => e.classList.remove('selected-option'));
                if(el) el.classList.add('selected-option');
            };

            // Load saved settings
            const savedTheme = localStorage.getItem('appTheme') || 'default';
            const savedFont = localStorage.getItem('appFont') || 'poppins';
            const savedPageWidth = localStorage.getItem('pageWidth') || '96%';

            document.body.classList.add('theme-' + savedTheme, 'font-' + savedFont);
            document.documentElement.style.setProperty('--page-width', savedPageWidth);

            document.querySelector(`.theme-color[data-theme="${savedTheme}"]`)?.classList.add('selected-option');
            document.querySelector(`.font-option[data-font="${savedFont}"]`)?.classList.add('selected-option');
            document.querySelector(`.width-option[data-width="${savedPageWidth}"]`)?.classList.add('selected-option');

            // Initialize Google Translate
            window.googleTranslateElementInit = function() {
                new google.translate.TranslateElement({
                    pageLanguage: 'en',
                    includedLanguages: 'en,id,es,fr,de,it,pt,ru,zh-CN,ja,ar',
                    layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                    autoDisplay: false
                }, 'google_translate_element');
            };

            // Share button functionality
            const shareButton = document.getElementById('share-button');
            if (shareButton) {
                shareButton.addEventListener('click', async () => {
                    if (navigator.share) {
                        try {
                            await navigator.share({ title: document.title, url: window.location.href });
                        } catch (error) {
                            console.error('Error sharing page:', error);
                        }
                    } else {
                        alert('Web Share API is not supported in this browser.');
                    }
                });
            }
        });

        // Security: Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Security: Disable browser back button caching for sensitive pages
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>

    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>
