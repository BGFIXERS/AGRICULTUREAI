<?php
session_start();

// --- Mock Database ---
if (!isset($_SESSION['users'])) $_SESSION['users'] = [];
if (!isset($_SESSION['loans'])) $_SESSION['loans'] = [];

// --- Utility Functions ---
function render_header($title) {
    echo "<!DOCTYPE html><html><head><title>$title</title><style>
    body {
        font-family: Arial; margin: 0; padding: 0;
        background: url('https://img.freepik.com/premium-photo/spring-grain-concept-agriculture-healthy-eating-organic-food-generative-ai_58409-32489.jpg') no-repeat center center fixed;
        background-size: cover;
    }
    .container {
        max-width: 700px; margin: 40px auto; background: rgba(255,255,255,0.95); padding: 20px;
        border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
    h1, h2 { text-align: center; color: #2f7d32; }
    input, button, select {
        width: 100%; padding: 10px; margin: 8px 0;
        border-radius: 5px; border: 1px solid #ccc;
    }
    button { background: #2f7d32; color: white; border: none; }
    button:hover { background: #256628; }
    .message { padding: 10px; background: #e7fbe7; border: 1px solid #a2d5a2; border-radius: 5px; }
    .error { background: #ffd2d2; border: 1px solid #e60000; padding: 10px; border-radius: 5px; }
    .linkbar { text-align: center; margin-bottom: 15px; }
    .linkbar a { margin: 0 10px; color: #2f7d32; text-decoration: none; font-weight: bold; }
    footer { text-align: center; color: #555; margin-top: 20px; }
    </style></head><body><div class='container'>";
    echo "<h1>AgriCred: AI-powered Microfinance</h1><hr>";
}

function render_footer() {
    echo "<hr><footer>&copy; 2025 AgriCred</footer></div></body></html>";
}

function mock_crop_analysis($img) {
    $filename = strtolower($img);
    if (str_contains($filename, 'wheat')) $type = 'Wheat';
    elseif (str_contains($filename, 'rice')) $type = 'Rice';
    elseif (str_contains($filename, 'maize') || str_contains($filename, 'corn')) $type = 'Maize';
    else $type = ['Wheat', 'Rice', 'Maize'][array_rand(['Wheat', 'Rice', 'Maize'])];

    $health = ['Good', 'Moderate', 'Poor'][array_rand(['Good', 'Moderate', 'Poor'])];
    return [
        'type' => $type,
        'health' => $health
    ];
}

function credit_score($health) {
    return match($health) {
        'Good' => rand(750, 850),
        'Moderate' => rand(600, 749),
        'Poor' => rand(400, 599),
        default => 0
    };
}

function mock_soil_analysis($crop) {
    $ph = rand(50, 89) / 10;
    $type = ['Loamy', 'Sandy', 'Clay'][array_rand(['Loamy', 'Sandy', 'Clay'])];
    return [
        'type' => $type,
        'ph' => $ph,
        'recommendation' => "Soil type is $type with pH $ph. Suitable for $crop."
    ];
}

function mock_weather_forecast($crop) {
    $conditions = ['Sunny', 'Rainy', 'Cloudy', 'Stormy'];
    $temp = rand(20, 38);
    $humidity = rand(40, 90);
    return [
        'condition' => $conditions[array_rand($conditions)],
        'temperature' => $temp,
        'humidity' => $humidity,
        'recommendation' => "Expected weather is $temp and $humidity% humidity. Monitor crop accordingly."
    ];
}

function analyze_soil_and_weather($crop) {
    $soil = mock_soil_analysis($crop);
    $weather = mock_weather_forecast($crop);
    return [
        'soil' => $soil,
        'weather' => $weather,
        'summary' => "
            <b>Soil Type:</b> {$soil['type']}<br>
            <b>pH Level:</b> {$soil['ph']}<br>
            <b>Soil Advice:</b> {$soil['recommendation']}<br><hr>
            <b>Weather:</b> {$weather['condition']}<br>
            <b>Temperature:</b> {$weather['temperature']}°C<br>
            <b>Humidity:</b> {$weather['humidity']}%<br>
            <b>Weather Advice:</b> {$weather['recommendation']}<br>"
    ];
}

// --- Router ---
$action = $_GET['action'] ?? 'home';

if ($action === 'register') {
    render_header("Register");
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = [
            'id' => uniqid(), 'name' => $_POST['name'],
            'email' => $_POST['email'], 'pass' => $_POST['pass']
        ];
        $_SESSION['users'][] = $user;
        echo "<div class='message'>Registered successfully. <a href='?action=login'>Login now</a></div>";
    } else {
        echo "<form method='post'>
            <input name='name' placeholder='Full Name' required>
            <input name='email' type='email' placeholder='Email' required>
            <input name='pass' type='password' placeholder='Password' required>
            <button>Register</button>
        </form>";
    }
    render_footer();

} elseif ($action === 'login') {
    render_header("Login");
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($_SESSION['users'] as $u) {
            if ($u['email'] === $_POST['email'] && $u['pass'] === $_POST['pass']) {
                $_SESSION['user'] = $u;
                header("Location: ?action=dashboard");
                exit;
            }
        }
        echo "<div class='error'>Invalid email or password.</div>";
    }
    echo "<form method='post'>
        <input name='email' type='email' placeholder='Email' required>
        <input name='pass' type='password' placeholder='Password' required>
        <button>Login</button>
    </form>";
    render_footer();

} elseif ($action === 'dashboard' && isset($_SESSION['user'])) {
    render_header("Dashboard");
    $user = $_SESSION['user'];
    echo "<div class='linkbar'>
        Welcome, <b>{$user['name']}</b> |
        <a href='?action=upload'>Upload Crop</a> |
        <a href='?action=loans'>Loan Status</a> |
        <a href='?action=logout'>Logout</a>
    </div>";
    render_footer();

} elseif ($action === 'upload' && isset($_SESSION['user'])) {
    render_header("Upload Crop Image");
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
        $dir = 'uploads/';
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $path = $dir . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $path)) {
            $ai = mock_crop_analysis($path);
            $score = credit_score($ai['health']);
            $analysis = analyze_soil_and_weather($ai['type']);

            $loan = [
                'user_id' => $_SESSION['user']['id'],
                'image' => $path,
                'crop' => $ai['type'],
                'health' => $ai['health'],
                'score' => $score,
                'soil' => $analysis['soil'],
                'weather' => $analysis['weather'],
                'status' => $score > 700 ? 'Approved' : 'Pending',
                'amount' => $score > 700 ? 50000 : 20000
            ];
            $_SESSION['loans'][] = $loan;

            echo "<div class='message'>
                <b>Crop:</b> {$ai['type']}<br>
                <b>Health:</b> {$ai['health']}<br>
                <b>AI Credit Score:</b> {$score}<br><hr>
                {$analysis['summary']}
                <b>Loan Status:</b> {$loan['status']}<br>
                <b>Loan Amount:</b> ₹{$loan['amount']}<br>
                <a href='?action=dashboard'>Back to Dashboard</a>
            </div>";
        } else {
            echo "<div class='error'>Upload failed. Try again.</div>";
        }
    } else {
        echo "<form method='post' enctype='multipart/form-data'>
            <input type='file' name='image' required>
            <button>Analyze Crop</button>
        </form>";
    }
    render_footer();

} elseif ($action === 'loans' && isset($_SESSION['user'])) {
    render_header("Loan Status");
    foreach ($_SESSION['loans'] as $loan) {
        if ($loan['user_id'] === $_SESSION['user']['id']) {
            echo "<div class='message'>
                <b>Crop:</b> {$loan['crop']} |
                <b>Health:</b> {$loan['health']} |
                <b>Score:</b> {$loan['score']} |
                <b>Loan:</b> ₹{$loan['amount']} ({$loan['status']})<br>
                <b>Soil:</b> {$loan['soil']['type']} (pH: {$loan['soil']['ph']})<br>
                <b>Weather:</b> {$loan['weather']['condition']} ({$loan['weather']['temperature']}°C, {$loan['weather']['humidity']}% humidity)
            </div>";
        }
    }
    echo "<div class='linkbar'><a href='?action=dashboard'>Back to Dashboard</a></div>";
    render_footer();

} elseif ($action === 'logout') {
    session_destroy();
    header("Location: ?action=login");
    exit;

} else {
    render_header("Home");
    echo "<div class='linkbar'>
        <a href='?action=register'>Register</a> |
        <a href='?action=login'>Login</a>
    </div>";
    render_footer();
}
?>