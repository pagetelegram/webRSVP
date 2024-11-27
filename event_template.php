<?php
// Load event details
$event_file = __DIR__ . '/event.dat';
if (!file_exists($event_file)) {
    die('<p>Error: Event file (event.dat) is missing.</p>');
}

// Read and parse event.dat
$event_data = file_get_contents($event_file);
$records = explode('§', $event_data); // Split by record separator

// Parse the first valid record
foreach ($records as $record) {
    if (!empty($record)) {
        $fields = explode('|', $record); // Split fields
        if (count($fields) >= 7) {
            list($event_title, $event_description, $event_date, $event_time, $in_person_limit, $total_limit, $online_link) = $fields;
            break;
        }
    }
}

// Validate required fields
if (!isset($event_title, $event_description, $event_date, $event_time)) {
    die('<p>Error: Event details are invalid or incomplete.</p>');
}

// Handle RSVP submissions
$rsvp_file = __DIR__ . '/rsvp.dat';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rsvp_action = $_POST['action'] ?? '';
    $rsvp_name = htmlspecialchars(trim($_POST['name']));
    $rsvp_attendance = htmlspecialchars(trim($_POST['attendance'] ?? ''));
    $rsvp_comment = htmlspecialchars(trim($_POST['comment'] ?? ''));

    $rsvp_data = file_exists($rsvp_file) ? file_get_contents($rsvp_file) : '';
    $rsvp_records = explode('§', $rsvp_data);

    if ($rsvp_action === 'add') {
        // Add new RSVP
        if (!empty($rsvp_name) && !empty($rsvp_attendance)) {
            $new_entry = implode('|', [$rsvp_name, $rsvp_attendance, $rsvp_comment]) . '§';
            file_put_contents($rsvp_file, $new_entry, FILE_APPEND);
        }
    } elseif ($rsvp_action === 'update' && isset($_POST['index'])) {
        // Update RSVP
        $index = intval($_POST['index']);
        if (isset($rsvp_records[$index])) {
            $rsvp_records[$index] = implode('|', [$rsvp_name, $rsvp_attendance, $rsvp_comment]);
            file_put_contents($rsvp_file, implode('§', $rsvp_records));
        }
    }
}

// Read existing RSVPs
$rsvp_data = file_exists($rsvp_file) ? file_get_contents($rsvp_file) : '';
$rsvp_records = array_filter(explode('§', $rsvp_data));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <title><?php echo htmlspecialchars($event_title); ?></title>
</head>
<body>
    <h1><?php echo htmlspecialchars($event_title); ?></h1>
    <p><strong>Description:</strong> <?php echo $event_description; ?></p>
    <p><strong>Date:</strong> <?php echo $event_date; ?></p>
    <p><strong>Time:</strong> <?php echo $event_time; ?></p>
    <p><strong>Online Access:</strong> <a href="<?php echo $online_link; ?>" target="_blank"><?php echo $online_link; ?></a></p>
    <p><a href="event.ics" download>Download Event Calendar (.ics)</a></p>

    <h2>RSVP</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name" required><br><br>

        <label for="attendance">Attendance:</label><br>
        <select id="attendance" name="attendance" required>
            <option value="In Person">In Person</option>
            <option value="Online">Online</option>
            <option value="Interested but Busy">Interested but Busy</option>
            <option value="Not Interested">Not Interested</option>
        </select><br><br>

        <label for="comment">Comment:</label><br>
        <textarea id="comment" name="comment" rows="3"></textarea><br><br>

        <button type="submit">Submit RSVP</button>
    </form>

    <h2>RSVP List</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Attendance</th>
                <th>Comment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rsvp_records as $index => $rsvp_record): ?>
                <?php list($name, $attendance, $comment) = explode('|', $rsvp_record); ?>
                <tr>
                    <form method="POST">
                        <td>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" readonly>
                        </td>
                        <td>
                            <select name="attendance" required>
                                <option value="In Person" <?php echo $attendance === 'In Person' ? 'selected' : ''; ?>>In Person</option>
                                <option value="Online" <?php echo $attendance === 'Online' ? 'selected' : ''; ?>>Online</option>
                                <option value="Interested but Busy" <?php echo $attendance === 'Interested but Busy' ? 'selected' : ''; ?>>Interested but Busy</option>
                                <option value="Not Interested" <?php echo $attendance === 'Not Interested' ? 'selected' : ''; ?>>Not Interested</option>
                            </select>
                        </td>
                        <td>
                            <textarea name="comment" rows="2"><?php echo htmlspecialchars($comment); ?></textarea>
                        </td>
                        <td>
                            <button type="submit">Update</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
