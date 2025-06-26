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

$current_username = $_SESSION['username'] ?? 'Guest';

// Add goal
if (isset($_POST['add_goal'])) {
    $goal_text = $_POST['goal_text'];
    $target_days = (int)$_POST['target_days'];
    $start_date = $_POST['start_date'];
    $goal_type = $_POST['goal_type'];

    $progress = 0;

    $stmt = $conn->prepare("INSERT INTO goals (goal_text, target_days, start_date, goal_type, progress, username) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissis", $goal_text, $target_days, $start_date, $goal_type, $progress, $current_username);
    $stmt->execute();
    $goal_id = $stmt->insert_id;
    $stmt->close();

    for ($i = 1; $i <= $target_days; $i++) {
        $p = $conn->prepare("INSERT INTO goal_progress (goal_id, day_number, completed) VALUES (?, ?, 0)");
        $p->bind_param("ii", $goal_id, $i);
        $p->execute();
        $p->close();
    }

    header("Location: index.php");
    exit();
}

// Update checkbox progress (toggle completed)
if (isset($_POST['goal_id']) && isset($_POST['completed_day'])) {
    $goal_id = (int)$_POST['goal_id'];
    $completed_day = (int)$_POST['completed_day'];

    // Get current completed status for this day
    $check = $conn->prepare("SELECT completed FROM goal_progress WHERE goal_id = ? AND day_number = ?");
    $check->bind_param("ii", $goal_id, $completed_day);
    $check->execute();
    $res = $check->get_result();
    if ($row = $res->fetch_assoc()) {
        $current_completed = (int)$row['completed'];
        $new_completed = $current_completed ? 0 : 1; // toggle

        // Update completed status and date_completed if marking completed, clear if unmarking
        if ($new_completed === 1) {
            $date_completed = date('Y-m-d');
            $update = $conn->prepare("UPDATE goal_progress SET completed = 1, date_completed = ? WHERE goal_id = ? AND day_number = ?");
            $update->bind_param("sii", $date_completed, $goal_id, $completed_day);
        } else {
            $update = $conn->prepare("UPDATE goal_progress SET completed = 0, date_completed = NULL WHERE goal_id = ? AND day_number = ?");
            $update->bind_param("ii", $goal_id, $completed_day);
        }
        $update->execute();
        $update->close();

        // Recalculate progress percentage
        $count_completed = $conn->prepare("SELECT COUNT(*) AS completed_count FROM goal_progress WHERE goal_id = ? AND completed = 1");
        $count_completed->bind_param("i", $goal_id);
        $count_completed->execute();
        $res_count = $count_completed->get_result()->fetch_assoc();
        $count_completed->close();

        $completed_days = $res_count['completed_count'];

        $target_days_stmt = $conn->prepare("SELECT target_days FROM goals WHERE id = ?");
        $target_days_stmt->bind_param("i", $goal_id);
        $target_days_stmt->execute();
        $target_days_result = $target_days_stmt->get_result()->fetch_assoc();
        $target_days_stmt->close();

        $target_days = (int)$target_days_result['target_days'];
        $new_progress = ($target_days > 0) ? round(($completed_days / $target_days) * 100) : 0;

        $update_progress = $conn->prepare("UPDATE goals SET progress = ? WHERE id = ?");
        $update_progress->bind_param("ii", $new_progress, $goal_id);
        $update_progress->execute();
        $update_progress->close();
    }
    $check->close();

    header("Location: index.php");
    exit();
}

// Remove goal
if (isset($_GET['remove_goal'])) {
    $goal_id = (int)$_GET['remove_goal'];
    $conn->query("DELETE FROM goal_progress WHERE goal_id = $goal_id");
    $conn->query("DELETE FROM goals WHERE id = $goal_id");
    header("Location: index.php");
    exit();
}

// Fetch goals
$stmt = $conn->prepare("SELECT * FROM goals WHERE username = ?");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();
$goals = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all progress completed today for all goals of this user for JS to know which checkboxes completed today
$today = date('Y-m-d');
$completed_today = [];
if (!empty($goals)) {
    $goal_ids = array_column($goals, 'id');
    $goal_ids_placeholder = implode(',', array_fill(0, count($goal_ids), '?'));
    $types = str_repeat('i', count($goal_ids));
    $sql = "SELECT goal_id, day_number FROM goal_progress WHERE completed = 1 AND date_completed = ? AND goal_id IN ($goal_ids_placeholder)";
    $stmt2 = $conn->prepare($sql);

    // bind params dynamically
    $params = array_merge([$today], $goal_ids);
    $stmt2->bind_param('s' . $types, ...$params);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $completed_today[] = $row['goal_id'] . '-' . $row['day_number'];
    }
    $stmt2->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Goal Tracker Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        #sidebar {
            width: 224px;
            background-color: midnightblue; 
            color: white;
            height: 100vh; 
            padding: 20px;
            position: fixed;
            top: 0;
            left: 0;
        }
        #sidebar a { color: white; text-decoration: none; display: block; margin: 10px 0; }
        #sidebar a:hover { color: yellow; }
        .main-content {
            margin-left: 270px;
            padding: 20px;
        }
        header {
            background-color: pink;
            color: white;
            padding: 20px;
            text-align: center;
            margin-left: 270px;
        }
        .goal-list { margin-top: 20px; }
        .goal-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .remove-goal { color: red; text-decoration: none; }

        input[type="checkbox"]:checked {
            accent-color: green;
        }
        input[type="checkbox"]:checked + label {
            color: green;
            font-weight: bold;
        }

        label {
            margin-left: 5px;
        }

        .checkbox-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .checkbox-container div {
            min-width: 90px;
        }
    </style>
</head>
<body>

<header>
    <h1>Goal Tracker</h1>
</header>

<div id="sidebar">
    <img src="images/logo1.jpg" alt="Logo" width="100" height="60" style="border-radius: 12px;" />
    <h2>Goal <span class="pink">Tracker</span></h2>
    <a href="profile.php"><i class="material-icons">person</i> Profile</a>
    <a href="login.php?logout='1'"><i class="material-icons">exit_to_app</i> Logout</a>
</div>

<div class="main-content">
    <h2>Add New Goal</h2>
    <form action="index.php" method="POST">
        <input type="text" name="goal_text" placeholder="Enter a new goal" required /><br /><br />
        <input type="number" name="target_days" placeholder="Target Days" required min="1" /><br /><br />
        <input type="date" name="start_date" required /><br /><br />
        <input type="text" name="goal_type" placeholder="Goal Type (e.g. fitness)" required /><br /><br />
        <button type="submit" name="add_goal">Add Goal</button>
    </form>

    <h2>Your Goals</h2>
    <div class="goal-list">
        <?php foreach ($goals as $goal): ?>
            <div class="goal-item">
                <p><strong><?php echo htmlspecialchars($goal['goal_text']); ?></strong></p>
                <p>Target Days: <?php echo $goal['target_days']; ?></p>
                <p>Start Date: <?php echo $goal['start_date']; ?></p>
                <p>Goal Type: <?php echo $goal['goal_type']; ?></p>
                <p>Progress: <?php echo $goal['progress']; ?>%</p>

                <form method="POST" action="index.php" class="goal-form">
                    <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>" />
                    <div class="checkbox-container">
                    <?php
                    $pg = $conn->prepare("SELECT day_number, completed, date_completed FROM goal_progress WHERE goal_id = ? ORDER BY day_number");
                    $pg->bind_param("i", $goal['id']);
                    $pg->execute();
                    $pg_result = $pg->get_result();
                    while ($row = $pg_result->fetch_assoc()):
                        $checkbox_id = "goal_{$goal['id']}_day_{$row['day_number']}";
                        ?>
                        <div>
                            <input type="checkbox"
                                   id="<?php echo $checkbox_id; ?>"
                                   name="completed_day"
                                   value="<?php echo $row['day_number']; ?>"
                                   <?php echo $row['completed'] ? 'checked' : ''; ?>
                                   data-goal-id="<?php echo $goal['id']; ?>"
                                   data-date-completed="<?php echo htmlspecialchars($row['date_completed']); ?>"
                                   onchange="this.form.submit()" />
                            <label for="<?php echo $checkbox_id; ?>">Day <?php echo $row['day_number']; ?></label>
                        </div>
                    <?php endwhile; $pg->close(); ?>
                    </div>
                </form>

                <a class="remove-goal" href="index.php?remove_goal=<?php echo $goal['id']; ?>" onclick="return confirm('Remove this goal?');">Remove Goal</a>
            </div>
        <?php endforeach; ?>
        <?php if (count($goals) === 0): ?>
            <p>No goals yet. Add one above!</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('input[type=checkbox][name=completed_day]');

    // PHP array of checkboxes completed today (goal_id-day_number strings)
    const completedToday = new Set(<?php echo json_encode($completed_today); ?>);

    checkboxes.forEach(chk => {
        const goalId = chk.dataset.goalId;
        const dayNum = chk.value;

        if (completedToday.has(goalId + '-' + dayNum)) {
            chk.dataset.checkedToday = 'true';
        } else {
            chk.dataset.checkedToday = '';
        }
    });

    function updateCheckboxStates() {
        // Get all unique goal IDs
        const goals = [...new Set([...checkboxes].map(chk => chk.dataset.goalId))];

        goals.forEach(goalId => {
            // Get checkboxes for this goal
            const goalCheckboxes = [...checkboxes].filter(chk => chk.dataset.goalId === goalId);

            // Check if any checkbox for this goal is checked and completed today
            const checkedTodayBoxes = goalCheckboxes.filter(chk => chk.checked && chk.dataset.checkedToday === 'true');

            if (checkedTodayBoxes.length > 0) {
                // Disable unchecked checkboxes for this goal
                goalCheckboxes.forEach(chk => {
                    if (!chk.checked) {
                        chk.disabled = true;
                    } else {
                        chk.disabled = false;
                    }
                });
            } else {
                // Enable all checkboxes for this goal if none checked today
                goalCheckboxes.forEach(chk => chk.disabled = false);
            }
        });
    }

    checkboxes.forEach(chk => {
        chk.addEventListener('change', () => {
            const goalId = chk.dataset.goalId;
            const goalCheckboxes = [...checkboxes].filter(c => c.dataset.goalId === goalId && c !== chk);

            if (chk.checked) {
                // Check if any other checkbox for the same goal is checked today
                const otherChecked = goalCheckboxes.filter(c => c.checked && c.dataset.checkedToday === 'true');

                if (otherChecked.length > 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Oops...',
                        text: 'You can only check one progress box per day for each goal. Come back tomorrow!'
                    });
                    chk.checked = false;
                    return;
                } else {
                    chk.dataset.checkedToday = 'true';
                }
            } else {
                chk.dataset.checkedToday = '';
            }
            updateCheckboxStates();
        });
    });

    updateCheckboxStates();
});
</script>

</body>
</html>
