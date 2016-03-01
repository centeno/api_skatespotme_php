<?php
require 'Slim/Slim.php';
require 'Json.php';

$app = new Slim();
$app->response()->header('Content-Type', 'application/json');
$app->response()->header('Access-Control-Allow-Origin', '*');

$GLOBALS['db'] = new PDO("mysql:host=localhost;dbname=diego_skatespotme", "diego_skatespot", "skatespotme");
$GLOBALS['json'] = new Json();

$app->get('/obstacles/', 'getObstacles');
$app->get('/types/', 'getTypes');
$app->post('/spots/', 'postSpots');
$app->post('/spot/', 'postSpot');


function postSpots () {
	$body = $GLOBALS['json']->decode(Slim::getInstance()->request()->getBody());
	
	$spots = $GLOBALS['db']->query('SELECT spot.*, type.name type_name FROM spot, type WHERE type_id = type.id');
	$spots->setFetchMode(PDO::FETCH_OBJ);
	 
	$return = array();
	
	while($obj = $spots->fetch()) {
		$obstacles = $GLOBALS['db']->query('SELECT id, name FROM spotobstacle, obstacle WHERE obstacle_id = id AND spot_id = '. $obj->id .' ORDER BY name');
		$obstacles->setFetchMode(PDO::FETCH_ASSOC);
		
		$return[] = array(
						"id" => $obj->id,
						"name" => $obj->name,
						"latitude" => $obj->latitude,
						"longitude" => $obj->longitude,
						"type" => array("id" => $obj->type_id, "name" => $obj->type_name),
						"obstacles" => $obstacles->fetchAll(),
					);
	}
	echo $GLOBALS['json']->encode($return);
}

function getObstacles(){
	$obstacles = $GLOBALS['db']->query('SELECT id, name FROM obstacle ORDER BY name');
	$obstacles->setFetchMode(PDO::FETCH_ASSOC);
	echo $GLOBALS['json']->encode($obstacles->fetchAll());
}


function getTypes(){
	$types = $GLOBALS['db']->query('SELECT id, name FROM type ORDER BY name');
	$types->setFetchMode(PDO::FETCH_ASSOC);
	echo $GLOBALS['json']->encode($types->fetchAll());
}

function postSpot(){
	$return = array();
	$app = Slim::getInstance();
	$body = $GLOBALS['json']->decode($app->request()->getBody());
	
	try{
		$type_id = $body->type->id;
		$name = $body->name;
		$latitude = $body->latitude;
		$longitude = $body->longitude;
		
		$GLOBALS['db']->query("INSERT INTO spot (type_id, name, latitude, longitude) VALUES (".$body->type->id.", '".$body->name."', '".$body->latitude."', '".$body->longitude."')");
		$id = $GLOBALS['db']->lastInsertId();
		
		foreach ($body->obstacles as $obstacle) {
    		$GLOBALS['db']->query("INSERT INTO spotobstacle VALUES (". $id .", ". $obstacle->id .")");		
		}
		
		$return = array(
        	'sucess' => true,
        	'message' => $id
    	);
	} catch (Exception $ex) {
		$return = array(
        	'sucess' => false,
        	'message' => $ex->getMessage() ." - linha ". $ex->getLine()
    	);
	}
	echo $GLOBALS['json']->encode($return);
}

$app->run();
?>