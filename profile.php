<?php
// Include the server file to use the session and database connection
include('server.php');

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

// Fetch user information from the database
$username = $_SESSION['username']; // Use session variable for username
$query = "SELECT * FROM users WHERE username='$username'"; // Assuming a 'users' table
$result = mysqli_query($db, $query);

// Check if the query returns a result
if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    $email = $user['email']; // Assuming the 'email' field exists in the users table
    $gender = $user['gender']; // Assuming the 'gender' field exists in the users table
} else {
    // Handle the case if no user is found
    echo "User not found!";
    exit();
}

// Handle gender update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_gender'])) {
    $new_gender = $_POST['gender'];

    // Update gender in the database
    $update_query = "UPDATE users SET gender='$new_gender' WHERE username='$username'";

    if (mysqli_query($db, $update_query)) {
        // Update session gender after successful update
        $_SESSION['gender'] = $new_gender;
        $gender = $new_gender; // Update the gender in the profile
    } else {
        echo "Error updating gender: " . mysqli_error($db);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Profile Page Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        header {
            background-color: DarkBlue;
            color: white;
            text-align: center;
text-decoration:underline;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            height:69vh;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2.5em;
            margin: 0;
        }

        h2 {
            font-size: 1.8em;
            color: #5d5d5d;
        }

        .profile-info {
            margin-top: 20px;
        }

        .profile-info p {
            font-size: 1.2em;
            line-height: 1.6;
            margin: 10px 0;
        }

        .profile-info strong {
            font-weight: bold;
            color: #444;
        }

        .gender-section {
            margin-top: 20px;
            font-size: 1.2em;
        }

        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .back-btn {
            background-color: #28a745;
        }

        .back-btn:hover {
            background-color: #218838;
        }

        .logout-btn {
            background-color: #dc3545;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 10px;
            background-color: DarkBlue;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <h1>User Profile</h1>
    </header>

    <div class="container">
        <h2>Welcome, <?php echo $username; ?>!</h2>

        <div class="profile-info">
            <p><strong>Username:</strong> <?php echo $username; ?></p>
            <p><strong>Email:</strong> <?php echo $email; ?></p>
        </div>

        <!-- Gender Section -->
        <div class="gender-section">
            <form method="POST" action="">
                <strong>Gender:</strong><br>
                <input type="radio" id="male" name="gender" value="Male" <?php if ($gender == 'Male') echo 'checked'; ?>>
                <label for="male">Male</label><br>
                <input type="radio" id="female" name="gender" value="Female" <?php if ($gender == 'Female') echo 'checked'; ?>>
                <label for="female">Female</label><br><br>

                <!-- Submit Button to Update Gender -->
                <button type="submit" name="update_gender" class="btn">Update Gender</button>
            </form>
        </div>

        <a href="index.php" class="btn back-btn">Back to Dashboard</a>
        <a href="login.php?logout='1'" class="btn logout-btn">Logout</a>
    </div>

    <footer>
        <p>&copy; 2025 Goal Tracker. All rights reserved.</p>
    </footer>
</body>
</html>
