<?php
// File: bs/generate_hash.php
// A simple utility to generate a secure password hash for a predefined password.
// This is essential for fixing login issues caused by PHP version differences.

// --- IMPORTANT: Delete this file from your server after you have used it! ---

// -----------------------------------------------------------------------------
// The password you want to create a hash for.
// You can change this to any password you like.
// -----------------------------------------------------------------------------
$plain_password = 'admin123';


// -----------------------------------------------------------------------------
// Generate the password hash using your local system's PHP configuration.
// PASSWORD_DEFAULT is the recommended modern algorithm (currently bcrypt).
// -----------------------------------------------------------------------------
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);


// -----------------------------------------------------------------------------
// Display the result in a clean, user-friendly HTML page.
// This makes it easy to see the password and copy the generated hash.
// -----------------------------------------------------------------------------
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Hash Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 800px;
        }
        h1 {
            color: #007bff;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
        }
        p {
            font-size: 1.1em;
            line-height: 1.6;
        }
        textarea {
            width: 100%;
            padding: 15px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.1em;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #ced4da;
            resize: none;
            box-sizing: border-box; /* Ensures padding is included in width */
        }
        .important-note {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Password Hash Generator</h1>
        
        <p>The plain password to be hashed is: <strong><?php echo htmlspecialchars($plain_password); ?></strong></p>
        
        <p>Copy the entire hash from the box below and paste it into the `password_hash` column of your `admins` table in phpMyAdmin.</p>

        <textarea rows="3" readonly onclick="this.select();"><?php echo htmlspecialchars($hashed_password); ?></textarea>
        
        <div class="important-note">
            <strong>Important:</strong> After copying the hash and updating your database, please delete this `generate_hash.php` file from your server for security reasons.
        </div>
    </div>
</body>
</html>