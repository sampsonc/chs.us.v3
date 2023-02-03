<?php // Note: this script requires PHP ≥ 5.4.

// Send `204 No Content` status code.
http_response_code(204);

// Get the raw POST data.
$data = file_get_contents('php://input');
// Only continue if it’s valid JSON that is not just `null`, `0`, `false` or an
// empty string, i.e. if it could be a CSP violation report.
if ($data = json_decode($data)) {
	// Prettify the JSON-formatted data.
	$data = json_encode(
		$data,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
	);
    file_put_contents("../logs/csp.log", $data."\n\n", FILE_APPEND);
}

?>
