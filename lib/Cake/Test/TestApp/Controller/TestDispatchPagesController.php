<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace TestApp\Controller;

use Cake\Controller\Controller;

/**
 * TestDispatchPagesController class
 *
 * @package       Cake.Test.Case.Routing
 */
class TestDispatchPagesController extends Controller {

/**
 * name property
 *
 * @var string 'TestDispatchPages'
 */
	public $name = 'TestDispatchPages';

/**
 * uses property
 *
 * @var array
 */
	public $uses = array();

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index() {
		return true;
	}

/**
 * camelCased method
 *
 * @return void
 */
	public function camelCased() {
		return true;
	}

}