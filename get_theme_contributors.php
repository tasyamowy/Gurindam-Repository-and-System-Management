<?php
require_once 'connection.php';

// Get theme ID from query parameter
$theme_id = isset($_GET['theme_id']) ? intval($_GET['theme_id']) : 0;

if ($theme_id <= 0) {
    echo json_encode(['error' => 'Invalid theme ID']);
    exit;
}

// Query to get top 5 contributors for the selected theme
$query = "SELECT 
    u.userID,
    u.name,
    c.contributorSince,
    COUNT(g.gurindamID) as theme_works
FROM users u
JOIN contributors c ON u.userID = c.contributorID
JOIN gurindams g ON u.userID = g.authorID AND g.status = 'Approved'
WHERE g.themeID = ?
GROUP BY u.userID, u.name, c.contributorSince
ORDER BY theme_works DESC
LIMIT 5";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $theme_id);
$stmt->execute();
$result = $stmt->get_result();

$contributors = [];
while($row = $result->fetch_assoc()) {
    $contributors[] = $row;
}

// Get total works for this theme
$totalQuery = "SELECT COUNT(*) as total FROM gurindams WHERE themeID = ? AND status = 'Approved'";
$stmt2 = $conn->prepare($totalQuery);
if (!$stmt2) {
    echo json_encode(['error' => 'Total query preparation failed: ' . $conn->error]);
    exit;
}

$stmt2->bind_param("i", $theme_id);
$stmt2->execute();
$totalResult = $stmt2->get_result();
$totalRow = $totalResult->fetch_assoc();
$total_works = $totalRow['total'] ?? 0;

// Calculate stats
$contributor_count = count($contributors);
$avg_works = $contributor_count > 0 ? round($total_works / $contributor_count, 1) : 0;
$top_contributor = $contributor_count > 0 ? $contributors[0]['theme_works'] : 0;

$response = [
    'contributors' => $contributors,
    'stats' => [
        'total_works' => $total_works,
        'avg_works' => $avg_works,
        'top_contributor' => $top_contributor,
        'contributor_count' => $contributor_count
    ]
];

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>