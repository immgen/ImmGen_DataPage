<?php

session_start();

	// Reject the request with a JSON error body and stop.
	function reject_request($status, $message) {
		http_response_code($status);
		header('Content-Type: application/json');
		echo json_encode(array('error' => $message));
		exit;
	}

	// Database credentials live outside the web root. Environment variables (e.g. Apache SetEnv) win;
	// otherwise the PHP file named by DP_DB_CONFIG, or ../config/db_config.php next to public/, is loaded.
	// That file must return array('host' => ..., 'user' => ..., 'pass' => ..., 'db' => ...).
	function env_value($name) {
		$value = getenv($name);
		if ($value === false && isset($_SERVER[$name])) { $value = $_SERVER[$name]; }
		return $value;
	}

	function load_db_config() {
		if (env_value('DP_DB_USER') !== false) {
			return array('host' => env_value('DP_DB_HOST'), 'user' => env_value('DP_DB_USER'), 'pass' => env_value('DP_DB_PASS'), 'db' => env_value('DP_DB_NAME'));
		}
		$config_file = env_value('DP_DB_CONFIG');
		if ($config_file === false || $config_file === '') { $config_file = dirname(__DIR__) . '/config/db_config.php'; }
		if (!is_readable($config_file)) { return null; }
		$config = include $config_file;
		return is_array($config) ? $config : null;
	}

	$db_config = load_db_config();
	if ($db_config === null) {
		error_log('dataPage_get_data.php: no database configuration (set DP_DB_* env vars or create config/db_config.php outside the web root)');
		reject_request(500, 'Can not connect.');
	}

	// Errors go to the PHP error log, never to the client.
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	try {
		$con = mysqli_connect($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['db']);
	} catch (mysqli_sql_exception $e) {
		error_log('dataPage_get_data.php: ' . $e->getMessage());
		reject_request(500, 'Can not connect.');
	}

	// Fixed query, no user input.
	$query = "SELECT data.* FROM `ImmGen_datasets` AS data WHERE 1 LIMIT 0, 30 ";

	try {
		$result = mysqli_query($con, $query);
	} catch (mysqli_sql_exception $e) {
		// As before, a failed query returns an empty table.
		error_log('dataPage_get_data.php: ' . $e->getMessage());
		$result = false;
	}

  $json = array();
  $data = array();

  $id = 0;
	if ($result !== false) {
		while ($r = mysqli_fetch_row($result)) {
		$id++;
		array_push($data, array('Name'=>$r[0], 'GeoAccession'=>$r[1],  'Description'=>$r[2], 'CellTypes'=>$r[3], 'DataTypes' => $r[4], 'PubMed' => $r[5], 'PubMed2'=> $r[6], 'PubMed3'=> $r[7], 'PubMed4' => $r[8], 'PubMed5' => $r[9],
		'PubMed6' => $r[10], 'PubMed7' => $r[11], 'PubMed8' => $r[12],'PubMed9' => $r[13],'PubMed10' => $r[14],'NormalizedData' => $r[15], 'NormalizedName' => $r[16], "RawGeneCount" => $r[17], "RawName" => $r[18]));
		}
	}
	$json = array('data' => $data);
	echo json_encode($json);


 ?>
