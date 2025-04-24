<?php
header('Content-Type: application/json; charset=utf-8');

// Get city from query parameter (default to 'India')
$city = isset($_GET['city']) && $_GET['city'] ? $_GET['city'] : 'India';

// GNews API key (replace with your real key!)
$api_key = 'c318fb8b45c8c2edc4de14fc4a114ebd'; // <-- PUT YOUR KEY HERE

// Prepare GNews API URL
$gnews_url = "https://gnews.io/api/v4/search?q=" . urlencode($city) . "&lang=en&country=in&max=10&token=" . $api_key;

$articles = [];
$response = @file_get_contents($gnews_url);
if ($response === false) {
    echo json_encode(['error' => 'Failed to fetch from GNews', 'url' => $gnews_url]);
    exit;
}
$data = json_decode($response, true);
if (!isset($data['articles']) || empty($data['articles'])) {
    // Try fallback to India if not already
    if (strtolower($city) !== 'india') {
        $fallback_url = "https://gnews.io/api/v4/search?q=India&lang=en&country=in&max=5&token=" . $api_key;
        $fallback_response = @file_get_contents($fallback_url);
        $fallback_data = json_decode($fallback_response, true);
        if (isset($fallback_data['articles']) && !empty($fallback_data['articles'])) {
            $articles = [];
            foreach ($fallback_data['articles'] as $item) {
                $articles[] = [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'link' => $item['url'],
                    'pubDate' => $item['publishedAt']
                ];
            }
            echo json_encode(['articles' => $articles, 'fallback' => 'India']);
            exit;
        }
    }
    echo json_encode(['error' => 'No articles found', 'gnews_response' => $data]);
    exit;
}
foreach ($data['articles'] as $item) {
    $articles[] = [
        'title' => $item['title'],
        'description' => $item['description'],
        'link' => $item['url'],
        'pubDate' => $item['publishedAt']
    ];
}
// Shuffle articles for variety on each refresh
shuffle($articles);
echo json_encode(['articles' => $articles]);
