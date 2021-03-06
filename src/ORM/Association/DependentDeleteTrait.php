<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\ORM\Association;

use Cake\ORM\Entity;

/**
 * Implements cascading deletes for dependent associations.
 *
 * Included by HasOne and HasMany association classes.
 */
trait DependentDeleteTrait {

/**
 * Cascade a delete to remove dependent records.
 *
 * This method does nothing if the association is not dependent.
 *
 * @param \Cake\ORM\Entity $entity The entity that started the cascaded delete.
 * @param array $options The options for the original delete.
 * @return boolean Success.
 */
	public function cascadeDelete(Entity $entity, array $options = []) {
		if (!$this->dependent()) {
			return true;
		}
		$table = $this->target();
		$foreignKey = (array)$this->foreignKey();
		$primaryKey = (array)$this->source()->primaryKey();
		$conditions = array_combine($foreignKey, $entity->extract($primaryKey));

		if ($this->_cascadeCallbacks) {
			$query = $this->find('all')->where($conditions)->bufferResults(false);
			foreach ($query as $related) {
				$table->delete($related, $options);
			}
			return true;
		}

		$conditions = array_merge($conditions, $this->conditions());
		return $table->deleteAll($conditions);
	}
}
