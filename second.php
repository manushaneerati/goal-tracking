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
    $start_date = $_POST['start_date'];
    $goal_type = $_POST['goal_type'];
    $progress = $_POST['progress'];

    $stmt = $conn->prepare("INSERT INTO goals (goal_text, target_days, start_date, goal_type, progress, username) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissis", $goal_text, $target_days, $start_date, $goal_type, $progress, $current_username);
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
$stmt = $conn->prepare("SELECT id, goal_text, target_days, start_date, goal_type, progress, username FROM goals WHERE username = ?");
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();

$goals = [];
while ($row = $result->fetch_assoc()) {
    $goals[] = $row;
}
?>

<!DOCTYPE html>
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

// Add goal with start_date and goal_type
if (isset($_POST['add_goal'])) {
    $goal_text = $_POST['goal_text'];
    $target_days = $_POST['target_days'];
    $start_date = $_POST['start_date'];
    $goal_type = $_POST['goal_type'];

    $stmt = $conn->prepare("INSERT INTO goals (goal_text, target_days, start_date, goal_type, username) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $goal_text, $target_days, $start_date, $goal_type, $current_username);
    $stmt->execute();

    header("Location: index.php");
    exit();
}

// Fetch goals for user
$stmt = $conn->prepare("SELECT id, goal_text, target_days, start_date, goal_type FROM goals WHERE username = ?");
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
<meta charset="UTF-8" />
<title>Goal Tracker Dashboard</title>
<style>
  /* Your CSS here, keep it simple for clarity */
  .goal-item {
    border: 1px solid #ccc;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 6px;
  }
</style>
</head>
<body>
<h1>Goal Tracker</h1>

<!-- Form to add goal -->
<form method="POST" action="index.php">
  <input type="text" name="goal_text" placeholder="Enter goal" required />
  <input type="number" name="target_days" placeholder="Target Days" min="1" required />
  <input type="date" name="start_date" required />
  <input type="text" name="goal_type" placeholder="Goal Type" required />
  <button type="submit" name="add_goal">Add Goal</button>
</form>

<hr />

<div id="goals-container">
<?php if (count($goals) > 0): ?>
  <?php foreach ($goals as $goal): ?>
    <div class="goal-item" data-goal-id="<?= $goal['id'] ?>"
         data-target-days="<?= $goal['target_days'] ?>"
         data-start-date="<?= $goal['start_date'] ?>">
      <h3><?= htmlspecialchars($goal['goal_text']) ?></h3>
      <p>Target Days: <?= $goal['target_days'] ?></p>
      <p>Start Date: <?= $goal['start_date'] ?></p>
      <p>Goal Type: <?= htmlspecialchars($goal['goal_type']) ?></p>

      <!-- Date selector (pre-filled with start date) -->
      <label>Start Date: 
        <input type="date" class="start-date" value="<?= $goal['start_date'] ?>" />
      </label>

      <div class="checkboxes-container"></div>

      <p class="progress-text">Progress: 0%</p>

      <button class="remove-goal" data-goal-id="<?= $goal['id'] ?>">Remove Goal</button>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p>No goals yet. Add one above!</p>
<?php endif; ?>
</div>

<script>
// Utility: format date as YYYY-MM-DD
function formatDate(date) {
  return date.toISOString().split('T')[0];
}

document.querySelectorAll('.goal-item').forEach(goalItem => {
  const targetDays = parseInt(goalItem.dataset.targetDays);
  const checkboxesContainer = goalItem.querySelector('.checkboxes-container');
  const progressText = goalItem.querySelector('.progress-text');
  const startDateInput = goalItem.querySelector('.start-date');
  let checkedDates = new Set();

  // Generate checkboxes based on start date and target days
  function generateCheckboxes(startDateStr) {
    checkboxesContainer.innerHTML = '';
    checkedDates.clear();

    const startDate = new Date(startDateStr);
    if (isNaN(startDate)) return;

    for (let i = 0; i < targetDays; i++) {
      let dayDate = new Date(startDate);
      dayDate.setDate(dayDate.getDate() + i);
      let dayStr = formatDate(dayDate);

      const label = document.createElement('label');
      label.style.display = 'block';

      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.value = dayStr;

      checkbox.addEventListener('change', () => {
        const todayStr = formatDate(new Date());

        if (checkbox.checked) {
          // Enforce only one checkbox per calendar day (today)
          if ([...checkedDates].includes(todayStr)) {
            alert("You can only mark progress once per day.");
            checkbox.checked = false;
            return;
          }
          checkedDates.add(dayStr);

          // Save progress to DB via AJAX
          saveProgress(goalItem.dataset.goalId, dayStr);
        } else {
          checkedDates.delete(dayStr);

          // Remove progress from DB via AJAX
          removeProgress(goalItem.dataset.goalId, dayStr);
        }
        updateProgress();
      });

      label.appendChild(checkbox);
      label.append(` ${dayStr}`);
      checkboxesContainer.appendChild(label);
    }
    updateProgress();
  }

  function updateProgress() {
    let progress = (checkedDates.size / targetDays) * 100;
    progressText.textContent = `Progress: ${progress.toFixed(2)}%`;
  }

  // AJAX helper functions
  function saveProgress(goalId, date) {
    fetch('save_progress.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({goal_id: goalId, progress_date: date})
    }).then(res => res.text()).then(console.log).catch(console.error);
  }

  function removeProgress(goalId, date) {
    fetch('remove_progress.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({goal_id: goalId, progress_date: date})
    }).then(res => res.text()).then(console.log).catch(console.error);
  }

  // Init checkboxes on page load for the start date
  generateCheckboxes(startDateInput.value);

  // Regenerate checkboxes if start date changes
  startDateInput.addEventListener('change', () => {
    generateCheckboxes(startDateInput.value);
  });

  // Remove goal button
  const removeBtn = goalItem.querySelector('.remove-goal');
  removeBtn.addEventListener('click', () => {
    const goalId = removeBtn.dataset.goalId;
    if (confirm('Are you sure you want to remove this goal?')) {
      window.location.href = `index.php?remove_goal=${goalId}`;
    }
  });
});
</script>
</body>
</html>
