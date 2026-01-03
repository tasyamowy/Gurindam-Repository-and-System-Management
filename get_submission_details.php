<?php
require_once 'session_check.php';
require_once 'connection.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid submission ID']);
    exit();
}

$submission_id = intval($_GET['id']);

// Query to get submission details
$sql = "SELECT 
            g.gurindamID,
            g.title,
            g.content,
            g.submissionDate,
            g.status,
            t.themeName,
            u.name as authorName,
            u.email as authorEmail,
            c.contributorSince
        FROM gurindams g
        JOIN themes t ON g.themeID = t.themeID
        JOIN contributors c ON g.authorID = c.contributorID
        JOIN users u ON c.contributorID = u.userID
        WHERE g.gurindamID = $submission_id";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

if (mysqli_num_rows($result) == 0) {
    echo json_encode(['error' => 'Submission not found']);
    exit();
}

$row = mysqli_fetch_assoc($result);

// Format date nicely
$submissionDate = date("F j, Y", strtotime($row['submissionDate']));

echo json_encode([
    'title' => $row['title'],
    'content' => $row['content'],
    'submissionDate' => $submissionDate,
    'status' => $row['status'],
    'themeName' => $row['themeName'],
    'authorName' => $row['authorName'],
    'authorEmail' => $row['authorEmail'],
    'contributorSince' => $row['contributorSince']
]);

mysqli_close($conn);
?>