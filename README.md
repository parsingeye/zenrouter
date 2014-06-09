zenrouter
=========

super simple php router

	// Init router
	$router = new Gil\ZenRouter\Router();

	$router->get('/incident', 'Incident@getIncident');
	$router->post('/status', 'Status@postStatus');
	
	$router->route();