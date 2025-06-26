<?php
session_start();

$host = 'localhost';
$dbname = 'project';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$current_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';

// Add goal
if (isset($_POST['add_goal'])) {
    $goal_text = $_POST['goal_text'];
    $target_days = $_POST['target_days'];

    $stmt = $conn->prepare("INSERT INTO goals (goal_text, target_days, username) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $goal_text, $target_days, $current_username);
    $stmt->execute();

    header("Location: index.php");
    exit();
}

// Remove goal
if (isset($_GET['remove_goal'])) {
    $goal_id = $_GET['remove_goal'];

    $stmt = $conn->prepare("DELETE FROM goals WHERE id = ?");
    $stmt->bind_param("i", $goal_id);
    $stmt->execute();

    header("Location: index.php");
    exit();
}

// Fetch goals
$stmt = $conn->prepare("SELECT id, goal_text, target_days, username FROM goals WHERE username = ?");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();

$goals = [];
while ($row = $result->fetch_assoc()) {
    $goals[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Goal Tracker Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />
    <style>
        /* General Styles for the Sidebar */
        #sidebar {
            width: 250px;
            background-color: midnightblue; 
            color: white;
            height: 100vh; 
            padding: 20px;
            font-family: 'Arial', sans-serif; 
            box-shadow: 2px 0px 10px rgba(0, 0, 0, 0.1); 
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;

        }
#sidebar a {
    color: white;
    text-decoration: none;
    display: block;
    margin: 10px 0;
}

#sidebar a:hover {
    color: yellow; /* Optional: lighter color on hover */
}


        /* Main Content Section (beside sidebar) */
        .main-content {
            margin-left: 270px; /* Add space to the left for the sidebar */
            padding: 20px;
            text-align: center;
            font-size: 20px;
        }

        /* Calendar Section */
        #calendar-section {
            background-color: MistyRose;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            display: none;
        }

        #calendar-header {
            text-align: center;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .goal-list {
            margin-top: 20px;
            text-align: left;
        }

        .goal-item {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            background-color: #f8f8f8;
            border-radius: 5px;
        }

        footer {
            background-color: pink;
            height: 8vh;
        }

        header {
            background-color: pink;
            color: white;
            padding: 20px;
            text-align: center;
            margin-left: 290px;
        }

        /* Remove underline from the 'Remove Goal' link */
        .remove-goal {
            text-decoration: none;
            color: red;
        }
/* Progress Bar Styles */
.progress-container {
    width: 100%;
    background-color: #ddd;
    border-radius: 5px;
    margin-top: 10px;
    height: 20px; /* Height of the progress bar */
}

.progress-bar {
    height: 100%;
    background-color: #4caf50; /* Green color */
    border-radius: 5px;
    text-align: center;
    line-height: 20px; /* Centers text vertically */
    color: white;
    font-weight: bold;
}
    </style>
</head>
<body>
<header>
    <h1>Goal Tracker</h1>
</header>
<div id="sidebar">
    <img src="images/logo1.jpg" alt="Logo" width="100" height="60">
    <h2>Goal <span class="pink">Tracker</span></h2>
    <a href="profile.php"><i class="material-icons">person</i> Profile</a>
<!-- Goal Type Dropdown with Material Icon -->
        <label for="goal-type"><i class="material-icons">star</i>Goal Type:</label>
        <select id="goal-type" name="goal-type">
            <option value="short-term">Short-Term</option>
            <option value="long-term">Long-Term</option>
        </select><br>

    <a href="login.php?logout='1'"><i class="material-icons">exit_to_app</i> Logout</a>
</div>

<div class="main-content">
    <h2>Your Goals</h2>
    <form action="index.php" method="POST">
        <input type="text" name="goal_text" placeholder="Enter a new goal" required>
        <input type="number" name="target_days" placeholder="Target Days" required>
        <button type="submit" name="add_goal">Add Goal</button>
    </form>

    <div class="goal-list">
        <?php if (count($goals) > 0): ?>
            <?php foreach ($goals as $goal): ?>
                <div class="goal-item" data-goal-id="<?php echo $goal['id']; ?>">
                    <p><strong><?php echo htmlspecialchars($goal['goal_text']); ?></strong></p>
                    <p>Target Days: <?php echo $goal['target_days']; ?> days</p>
                    <p>Added by: <?php echo htmlspecialchars($goal['username']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No goals yet. Add one above!</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const goals = document.querySelectorAll('.goal-item');

    goals.forEach((goalItem) => {
        const goalId = goalItem.dataset.goalId || '';
        const targetDays = parseInt(goalItem.querySelector('p:nth-child(2)').textContent.match(/\d+/)[0]);

        const dateInput = document.createElement('input');
        dateInput.type = 'date';
        goalItem.appendChild(dateInput);

        const daysContainer = document.createElement('div');
        goalItem.appendChild(daysContainer);

        const progressBar = document.createElement('progress');
        progressBar.value = 0;
        progressBar.max = 100;
        goalItem.appendChild(progressBar);

        const progressText = document.createElement('p');
        progressText.textContent = 'Progress: 0%';
        goalItem.appendChild(progressText);

        const removeLink = document.createElement('a');
        removeLink.href = `index.php?remove_goal=${goalId}`;
        removeLink.className = 'remove-goal';
        removeLink.textContent = 'Remove Goal';
        removeLink.style.display = 'block';
        removeLink.style.marginTop = '10px';
        goalItem.appendChild(removeLink);

        let checkedDates = [];
        let checkLog = {};

        dateInput.addEventListener('change', () => {
            daysContainer.innerHTML = '';
            checkedDates = [];
            checkLog = {};

            const startDate = new Date(dateInput.value);
            if (isNaN(startDate)) return;

            for (let i = 0; i < targetDays; i++) {
                const date = new Date(startDate);
                date.setDate(startDate.getDate() + i);
                const goalDay = date.toISOString().split('T')[0];

                const label = document.createElement('label');
                label.style.display = 'block';
                label.style.margin = '5px 0';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.dataset.goalDay = goalDay;

                checkbox.addEventListener('change', () => {
                    const todayStr = new Date().toISOString().split('T')[0];

                    if (checkbox.checked) {
                        if (Object.values(checkLog).includes(todayStr)) {
                            alert("You’ve already marked progress for today. Please come back tomorrow.");
                            checkbox.checked = false;
                            return;
                        }

                        checkedDates.push(goalDay);
                        checkLog[goalDay] = todayStr;
                    } else {
                        checkedDates = checkedDates.filter(d => d !== goalDay);
                        delete checkLog[goalDay];
                    }

                    updateProgress();
                });

                label.appendChild(checkbox);
                label.append(` ${goalDay}`);
                daysContainer.appendChild(label);
            }

            updateProgress();
        });

        function updateProgress() {
            const progress = (checkedDates.length / targetDays) * 100;
            progressBar.value = progress;
            progressText.textContent = `Progress: ${progress.toFixed(2)}%`;
        }
    });
});
</script>
</body>
</html>
