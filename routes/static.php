<?php

// Static pages
// ------------
// Finally, we check if there is a static page available for the route.

$app->get('/{slug}', function ($request, $response, $args) {

	$slug = str_replace(array('../','./'), '', $args['slug']); // remove parent path components if request is trying to be sneaky
	
	if (file_exists(ROOT_PATH.'/templates/static/'.$slug.'.html')) {
		return $this->view->render($response, 'static/'.$slug.'.html');
	} else {
		return $this->view->render($response, 'static/404.html')->withStatus(404);
	}
});

?>