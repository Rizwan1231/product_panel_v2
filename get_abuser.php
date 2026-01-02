<?php
function getshitipsv3($logData, $targetFile, $minHits) {
	//$logData = file_get_contents('/home/x_m/bin/nginx/logs/access.log');
    $lines = explode("\n", trim($logData));
    $ipStats = [];

    foreach ($lines as $line) {
        // Extract IP address
        $ip = strtok($line, ' ');
        
        // Split request components
        $requestParts = explode('"', $line);
        if (count($requestParts) < 3) continue;
        
        // Parse URL and validate target file
        $urlParts = explode(' ', $requestParts[1]);
        if (count($urlParts) < 2) continue;
        
        $path = parse_url($urlParts[1], PHP_URL_PATH);
        if (basename($path) !== $targetFile) continue;

        // Extract action parameter
        $query = parse_url($urlParts[1], PHP_URL_QUERY);
        parse_str($query, $params);
        $action = $params['action'] ?? 'scan';

        // Initialize IP record if not exists
        if (!isset($ipStats[$ip])) {
            $ipStats[$ip] = [
                'total' => 0,
                'actions' => []
            ];
        }

        // Update counts
        $ipStats[$ip]['total']++;
        if (!isset($ipStats[$ip]['actions'][$action])) {
            $ipStats[$ip]['actions'][$action] = 0;
        }
        $ipStats[$ip]['actions'][$action]++;
    }

    // Format results with simplified structure
    $result = [];
    foreach ($ipStats as $ip => $data) {
		if ( $data['total'] >= $minHits ) {
			$result[] = [
				'ip' => $ip,
				'action' => $data['actions'] ?: ['none' => $data['total']],
				'total_count' => $data['total']
			];
		}
    }

    return $result;
}

$rData = base64_decode($_POST['data']);
$rHits = intval($_POST['hits']);
$target = $_POST['target'];
if (!empty($rHits)) {
	if (!empty($_POST['data'])) {
		echo json_encode(getshitipsv3($rData, $target, $rHits));
	}
}
