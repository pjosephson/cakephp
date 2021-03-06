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
use Cake\Utility\Inflector;

echo "<?php\n";
?>
namespace <?= $namespace ?>\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * <?= $name ?> Model
 */
class <?= $name ?>Table extends Table {

/**
 * Initialize method
 *
 * @param array $config The configuration for the Table.
 * @return void
 */
	public function initialize(array $config) {
<?php if (!empty($table)): ?>
		$this->table('<?= $table ?>');
<?php endif ?>
<?php if (!empty($displayField)): ?>
		$this->displayField('<?= $displayField ?>');
<?php endif ?>
<?php if (!empty($primaryKey)): ?>
<?php
$key = array_map(function($el) { return "'$el'"; }, (array)$primaryKey);
?>
		$this->primaryKey([<?= implode(', ', $key) ?>]);
<?php endif ?>
<?php foreach ($behaviors as $behavior => $behaviorData): ?>
		$this->addBehavior('<?= $behavior ?>'<?= $behaviorData ? ", [" . implode(', ', $behaviorData) . ']' : '' ?>);
<?php endforeach ?>

<?php foreach ($associations as $type => $assocs): ?>
<?php foreach ($assocs as $assoc): ?>
		$this-><?= $type ?>('<?= $assoc['alias'] ?>', [
<?php foreach ($assoc as $key => $val): ?>
<?php if ($key !== 'alias'): ?>
			<?= "'$key' => '$val',\n" ?>
<?php endif ?>
<?php endforeach ?>
		]);
<?php endforeach ?>
<?php endforeach ?>
	}

<?php if (!empty($validation)): ?>
/**
 * Default validation rules.
 *
 * @param \Cake\Validation\Validator $validator
 * @return \Cake\Validation\Validator
 */
	public function validationDefault(Validator $validator) {
		$validator
<?php foreach ($validation as $field => $rule): ?>
<?php if ($rule['rule']): ?>
			->add('<?= $field ?>', 'valid', ['rule' => '<?= $rule['rule'] ?>'])
<?php endif; ?>
<?php if (is_string($rule['allowEmpty'])): ?>
			->allowEmpty('<?= $field ?>', '<?= $rule['allowEmpty'] ?>')
<?php elseif ($rule['allowEmpty']): ?>
			->allowEmpty('<?= $field ?>')
<?php else: ?>
			->validatePresence('<?= $field ?>', 'create')
			->allowEmpty('<?= $field ?>', false)
<?php endif ?>
<?php endforeach ?>;
		return $validator;
	}
<?php endif ?>

}
