<?php
//client_max_body_size 20m;
function getshitipsv3($logData, $targetsFile) {
	//$logData = file_get_contents('/home/x_m/bin/nginx/logs/access.log');
    $lines = explode("\n", trim($logData));
    
	$result = [];
	
	foreach ($targetsFile as $targetFile => $minHits) {
		$ipStats = [];
		$targetFile .= '.php';
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
					'actions' => [],
					'target' => str_replace('.php', '', $targetFile)
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
		
		foreach ($ipStats as $ip => $data) {
			if ( $data['total'] >= $targetsFile[$data['target']] ) {
				$result[] = [
					'ip' => $ip,
					'target' => $data['target'],
					'action' => $data['actions'] ?: ['none' => $data['total']],
					'total_count' => $data['total']
				];
			}
		}
		
	}

    return $result;
}

$rData = base64_decode($_POST['data']);
$targets = json_decode(base64_decode($_POST['target']), true);

if (!empty($targets)) {
	if (!empty($_POST['data'])) {
		echo json_encode(getshitipsv3($rData, $targets));
	}
}
