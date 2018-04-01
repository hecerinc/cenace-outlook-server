<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
	// return "<h1>Hello world</h1>";
	$results = DB::select("SELECT * FROM zones");
	header('Content-Type: application/json');
	return json_encode($results);
    // return $router->app->version();
});


// DEMANDA
$router->get('demanda/{sistema}/{zdc_id}/{fecha}', function($sistema, $zdc_id, $fecha) use($router) {
	// fecha,zdc_id,hora,cdm,cim,eta

	$result = DB::select("SELECT id FROM zones WHERE sistema = ? AND zona = ?", [$sistema, urldecode($zdc_id)]);
	$results = DB::select("SELECT eta FROM demanda WHERE zdc_id = ? AND fecha = ?", [$result[0]->id, $fecha]);
	$test = array_column($results, "eta");
	array_walk($test, function(&$val, $index) {
		$val = [$index, (float)$val];
	});
	$res = [
		'zdc' => $zdc_id,
		'fecha' => $fecha,
		'data' => $test
	];
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');
	return json_encode($res);
});

$router->get('precios/{node_id}', function($node_id) use($router) {
	$results  = DB::select("SELECT energia,perdidas,congestion FROM precios WHERE node_id = ? ORDER BY hora", [$node_id]);
	$res = [
		'node' => $node_id,
		'data' => [
			'energia' => array_column($results, 'energia'),
			'perdidas' => array_column($results, 'perdidas'),
			'congestion' => array_column($results, 'congestion')
		]
	];
	
	header('Content-Type: application/json');
	return json_encode($res);

});


// Data dump
$router->get('demanda/dump/{sistema}/{zdc_id}/{start_date}/{end_date}', function($sistema, $zdc_id, $start_date, $end_date) use($router) {
	$result = DB::select("SELECT id FROM zones WHERE sistema = ? AND zona = ?", [$sistema, urldecode($zdc_id)]);
	if(empty($result)) {
		return json_encode(['error' => 'error', 'msg' => 'An error has been produced']);
	}

	$results = DB::select("SELECT fecha,zdc_id,hora,cdm,cim,eta FROM demanda WHERE zdc_id = ? AND fecha >= ? AND fecha <= ?", [$result[0]->id, $start_date, $end_date]);

	$csv_export = "fecha,hora,clave,carga.directamente.modelada,carga.indirectamente.modelada,energia.total".PHP_EOL;

	foreach ($results as $result) {
		$csv_export .= "\"$result->fecha\",$result->hora,\"$result->zdc_id\",$result->cdm,$result->cim,$result->eta".PHP_EOL;
	}
	$csv_filename = "DEMANDA_".date('Ymd_His').".csv";
    header("Content-Description: File Transfer");
    header("Content-Transfer-Encoding: binary");
    header("Content-Type: binary/octet-stream");
	// header("Content-type: text/x-csv");
	header("Content-Disposition: attachment; filename=".$csv_filename."");
	return $csv_export;

});

$router->get('precios/dump/{node_id}/{start_date}/{end_date}', function($node_id, $start_date, $end_date) use($router) {
	// TODO: Add validation here
	$results = DB::select("SELECT fecha,hora,node_id,pml,energia,perdidas,congestion FROM precios WHERE node_id = ? AND fecha >= ? AND fecha <= ?", [$node_id, $start_date, $end_date]);
	// Construct CSV

	$csv_export = "fecha,hora,clave_nodo,pml,energia,perdidas,congestion".PHP_EOL;
	foreach ($results as $result) {
		$csv_export .= "\"$result->fecha\",$result->hora,\"$result->node_id\",$result->pml,$result->energia,$result->perdidas,$result->congestion".PHP_EOL;
	}
	$csv_filename = "PRECIOS_".date('Ymd_His').".csv";
	header("Content-type: text/x-csv");
	header("Content-Disposition: attachment; filename=".$csv_filename."");
	return $csv_export;
});

