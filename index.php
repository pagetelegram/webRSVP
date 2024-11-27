<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $event_title = htmlspecialchars(trim($_POST['event_title']));
    $event_description = htmlspecialchars(trim($_POST['event_description']));
    $event_date = trim($_POST['event_date']); // Expected format: MM-DD-YYYY
    $event_time = trim($_POST['event_time']); // Expected format: HH:MM
    $in_person_limit = intval($_POST['in_person_limit']);
    $total_limit = intval($_POST['total_limit']);
    $online_link = htmlspecialchars(trim($_POST['online_link']));
    $captcha_result = intval($_POST['captcha_result']);
    $captcha_expected = intval($_POST['captcha_expected']);

    // Validate math captcha
    if ($captcha_result !== $captcha_expected) {
        die('<p>Error: Invalid captcha answer. Please go back and try again.</p>');
    }

    // Validate required fields
    if (
        empty($event_title) || empty($event_description) || empty($event_date) || empty($event_time) ||
        empty($online_link) || $in_person_limit <= 0 || $total_limit <= 0
    ) {
        die('<p>Error: All fields are required and must be valid. Please go back and try again.</p>');
    }

    // Validate date and time formats
    if (!DateTime::createFromFormat('Y-m-d', $event_date)) {
        die('<p>Error: Event date must be in MM-DD-YYYY format. Please go back and try again.</p>');
    }
    if (!DateTime::createFromFormat('H:i', $event_time)) {
        die('<p>Error: Event time must be in HH:MM format. Please go back and try again.</p>');
    }

    // Generate a random folder name for the event
    $event_folder = uniqid('event_', true);
    $event_folder_path = __DIR__ . '/' . $event_folder;

    if (!mkdir($event_folder_path, 0755)) {
        die('<p>Error: Failed to create event folder.</p>');
    }

    // Sanitize the event description
    $sanitized_description = str_replace(["\r", "\n"], '<br>', $event_description);

    // Create the event.dat file with new structure
    $event_data = implode('|', [
        $event_title,
        $sanitized_description,
        $event_date,
        $event_time,
        $in_person_limit,
        $total_limit,
        $online_link,
    ]);
    $event_dat_content = $event_data . '§'; // End the record with §
    if (!file_put_contents($event_folder_path . '/event.dat', $event_dat_content)) {
        die('<p>Error: Failed to create event file.</p>');
    }

    // Generate iCalendar file
    try {
        $ical_datetime = DateTime::createFromFormat('Y-m-d H:i', "$event_date $event_time");
        $ical_content = "BEGIN:VCALENDAR\r\n";
        $ical_content .= "VERSION:2.0\r\n";
        $ical_content .= "BEGIN:VEVENT\r\n";
        $ical_content .= "SUMMARY:" . addslashes($event_title) . "\r\n";
        $ical_content .= "DESCRIPTION:" . addslashes(strip_tags($sanitized_description)) . "\r\n";
        $ical_content .= "DTSTART:" . $ical_datetime->format('Ymd\THis') . "\r\n";
        $ical_content .= "DTEND:" . $ical_datetime->modify('+1 hour')->format('Ymd\THis') . "\r\n";
        $ical_content .= "URL:$online_link\r\n";
        $ical_content .= "END:VEVENT\r\n";
        $ical_content .= "END:VCALENDAR\r\n";

        file_put_contents($event_folder_path . "/event.ics", $ical_content);
    } catch (Exception $e) {
        die('<p>Error: Unable to generate iCalendar file.</p>');
    }

    // Copy the event template to the new folder
    if (!copy(__DIR__ . '/event_template.php', $event_folder_path . '/index.php')) {
        die('<p>Error: Failed to copy event template file.</p>');
    }

    // Provide the event link
    $event_link = basename($event_folder);
    echo <<<HTML
    <p>Event created successfully!</p>
    <button onclick="redirectAndCopy('$event_link')">Go to Event Page and Copy Link</button>
    <script>
        function redirectAndCopy(eventLink) {
            const fullLink = window.location.origin + '/' + eventLink;
            navigator.clipboard.writeText(fullLink).then(() => {
                window.location.href = '/' + eventLink;
            }).catch(err => {
                alert('Failed to copy link: ' + err);
            });
        }
    </script>
HTML;
    exit;
}

// Generate math captcha
$num1 = rand(1, 10);
$num2 = rand(1, 10);
$captcha_expected = $num1 + $num2;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <title>Create Event</title>
    <script>
        function formatDateForSubmission() {
            const dateInput = document.getElementById('event_date');
            const formattedInput = document.getElementById('formatted_date');
            const [month, day, year] = dateInput.value.split('-');
            formattedInput.value = `${year}-${month}-${day}`;
        }
    </script>
</head>
<body>
    <h1>Create an Event</h1>
    <form method="POST" onsubmit="formatDateForSubmission()">
        <label for="event_title">Event Title:</label><br>
        <input type="text" id="event_title" name="event_title" required><br><br>

        <label for="event_description">Event Description:</label><br>
        <textarea id="event_description" name="event_description" rows="5" required></textarea><br><br>

        <label for="event_date">Event Date (MM-DD-YYYY):</label><br>
        <input type="text" id="event_date" name="display_date" placeholder="MM-DD-YYYY" pattern="\d{2}-\d{2}-\d{4}" required><br><br>
        <input type="hidden" id="formatted_date" name="event_date">

        <label for="event_time">Event Time:</label><br>
        <input type="time" id="event_time" name="event_time" required><br><br>

        <label for="in_person_limit">In-Person Limit:</label><br>
        <input type="number" id="in_person_limit" name="in_person_limit" min="1" required><br><br>

        <label for="total_limit">Total Limit:</label><br>
        <input type="number" id="total_limit" name="total_limit" min="1" required><br><br>

        <label for="online_link">Online Link (https://):</label><br>
        <input type="url" id="online_link" name="online_link" required><br><br>

        <label for="captcha_result">What is <?php echo $num1; ?> + <?php echo $num2; ?>?</label><br>
        <input type="number" id="captcha_result" name="captcha_result" required><br><br>
        <input type="hidden" name="captcha_expected" value="<?php echo $captcha_expected; ?>">

        <button type="submit">Create Event</button>
    </form>
</body>
</html>
