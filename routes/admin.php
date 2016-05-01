<?php

// Admin pages
// -----------
// The admin routes

function setupBeanController($beantype) {
	// Return controller
	$controller_name = 'Lagan'.ucfirst($beantype);
	return new $controller_name();
}

// DRY
function redirectAfterSave($container, $bean, $data, $response, $args) {
	if ( $data['submit'] == 'saveandclose' ) {
		return $response->withStatus(302)->withHeader(
			'Location',
			$container->get('router')->pathFor( 'listbeans', [ 'beantype' => $args['beantype'] ] )
		);
	} else {
		return $response->withStatus(302)->withHeader(
			'Location',
			$container->get('router')->pathFor( 'getbean', [ 'beantype' => $args['beantype'], 'id' => $bean->id ] )
		);
	}
}

// Users need to authenticate with HTTP Basic Authentication middleware
$app->group('/admin', function () {

	$this->get('[/]', function ($request, $response, $args) {
		// Get all Lagan beantypes
		$beantypes = glob(ROOT_PATH. '/model/Lagan?*.php');
		foreach ($beantypes as $key => $value) {
			$beantypes[$key] = strtolower( substr(
				$value,
				strlen(ROOT_PATH. '/model/Lagan'),
				strlen($value) - strlen(ROOT_PATH. '/model/Lagan') - 4
			) );
		}

		return $this->view->render($response, 'admin/index.html', ['beantypes' => $beantypes]);
	})->setName('admin');


	// Route of a certian type of bean
	$this->group('/{beantype}', function () use ($app) {
	
		// List
		$this->get('[/]', function ($request, $response, $args) {
			$c = setupBeanController( $args['beantype'] );

			// Oder by position if exits
			$add_to_query = '';
			foreach($c->properties as $property) {
				if ( $property['name'] === 'position' ) {
					$add_to_query = 'position, ';
					break;
				}
			}
			$beans = R::findAll( strtolower( $args['beantype'] ), ' ORDER BY '.$add_to_query.'title ASC ');

			// Show list of items
			return $this->view->render($response, 'admin/beans.html', [
				'beantype' => $args['beantype'],
				'description' => $c->description,
				'beans' => $beans,
				'flash' => $this->flash->getMessages()
			]);
		})->setName('listbeans');

		// Form to add new bean
		$this->get('/add', function ($request, $response, $args) {
			$c = setupBeanController( $args['beantype'] );
			$c->populateProperties();

			// Show populated form
			return $this->view->render($response, 'admin/bean.html', [
				'method' => 'post',
				'beantype' => $args['beantype'],
				'beanproperties' => $c->properties
			]);
		})->setName('addbean');

		// View existing bean
		$this->get('/{id}', function ($request, $response, $args) {
			$c = setupBeanController( $args['beantype'] );
			$c->populateProperties();

			// Show populated form
			return $this->view->render($response, 'admin/bean.html', [
				'method' => 'put',
				'beantype' => $args['beantype'],
				'beanproperties' => $c->properties,
				'bean' => $c->read( $args['id'] ),
				'flash' => $this->flash->getMessages()
			]);
		})->setName('getbean');

		// Add
		$this->post('[/]', function ($request, $response, $args) {
			$c = setupBeanController( $args['beantype'] );
			$data = $request->getParsedBody();

			try {
				$bean = $c->create( $data );
				
				// Redirect to overview or populated form
				$this->flash->addMessage( 'success', $bean->title.' is added.' );
				return redirectAfterSave($this, $bean, $data, $response, $args);
			} catch (Exception $e) {
				$this->flash->addMessage( 'error', $e->getMessage() );
				return $response->withStatus(302)->withHeader(
					'Location',
					$this->get('router')->pathFor( 'addbean', [ 'beantype' => $args['beantype'] ])
				);
			}
		})->setName('postbean');

		// Update
		$this->put('/{id}', function ($request, $response, $args) {
			$c = setupBeanController( $args['beantype'] );
			$data = $request->getParsedBody();

			try {
				$bean = $c->update( $data , $args['id'] );

				// Redirect to overview or populated form
				$this->flash->addMessage( 'success', $bean->title.' is updated.' );
				return redirectAfterSave($this, $bean, $data, $response, $args);
			} catch (Exception $e) {
				$this->flash->addMessage( 'error', $e->getMessage() );
				return $response->withStatus(302)->withHeader(
					'Location',
					$this->get('router')->pathFor( 'getbean', [ 'beantype' => $args['beantype'], 'id' => $args['id'] ] )
				);
			}
		})->setName('putbean');

		// Delete
		$this->delete('/{id}', function ($request, $response, $args) {
			$c = setupBeanController( $args['beantype'] );
			
			try {
				$c->delete( $args['id'] );
				$this->flash->addMessage( 'success', 'The '.$args['beantype'].' is deleted.' );
			} catch (Exception $e) {
				$this->flash->addMessage( 'error', $e->getMessage() );
			}
			return $response->withStatus(302)->withHeader(
				'Location',
				$this->get('router')->pathFor( 'listbeans', [ 'beantype' => $args['beantype'] ])
			);
		})->setName('deletebean');
		
	});

});

?>