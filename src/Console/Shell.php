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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console;

use Cake\Console\ConsoleIo;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Object;
use Cake\Core\Plugin;
use Cake\Error;
use Cake\Utility\ConventionsTrait;
use Cake\Utility\File;
use Cake\Utility\Inflector;
use Cake\Utility\MergeVariablesTrait;
use Cake\Utility\ModelAwareTrait;
use Cake\Utility\String;

/**
 * Base class for command-line utilities for automating programmer chores.
 *
 * Is the equivalent of Cake\Controller\Controller on the command line.
 */
class Shell extends Object {

	use MergeVariablesTrait;
	use ModelAwareTrait;

/**
 * Output constant making verbose shells.
 *
 * @var integer
 */
	const VERBOSE = ConsoleIo::VERBOSE;

/**
 * Output constant for making normal shells.
 *
 * @var integer
 */
	const NORMAL = ConsoleIo::NORMAL;

/**
 * Output constants for making quiet shells.
 *
 * @var integer
 */
	const QUIET = ConsoleIo::QUIET;

/**
 * An instance of ConsoleOptionParser that has been configured for this class.
 *
 * @var \Cake\Console\ConsoleOptionParser
 */
	public $OptionParser;

/**
 * If true, the script will ask for permission to perform actions.
 *
 * @var boolean
 */
	public $interactive = true;

/**
 * Contains command switches parsed from the command line.
 *
 * @var array
 */
	public $params = [];

/**
 * The command (method/task) that is being run.
 *
 * @var string
 */
	public $command;

/**
 * Contains arguments parsed from the command line.
 *
 * @var array
 */
	public $args = [];

/**
 * The name of the shell in camelized.
 *
 * @var string
 */
	public $name = null;

/**
 * The name of the plugin the shell belongs to.
 * Is automatically set by ShellDispatcher when a shell is constructed.
 *
 * @var string
 */
	public $plugin = null;

/**
 * Contains tasks to load and instantiate
 *
 * @var array
 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#Shell::$tasks
 */
	public $tasks = [];

/**
 * Contains the loaded tasks
 *
 * @var array
 */
	public $taskNames = [];

/**
 * Task Collection for the command, used to create Tasks.
 *
 * @var TaskRegistry
 */
	public $Tasks;

/**
 * Normalized map of tasks.
 *
 * @var string
 */
	protected $_taskMap = [];

/**
 * ConsoleIo instance.
 *
 * @var \Cake\Console\ConsoleIo
 */
	protected $_io;

/**
 *  Constructs this Shell instance.
 *
 * @param \Cake\Console\ConsoleIo $io An io instance.
 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#Shell
 */
	public function __construct(ConsoleIo $io = null) {
		if (!$this->name) {
			list(, $class) = namespaceSplit(get_class($this));
			$this->name = str_replace(['Shell', 'Task'], '', $class);
		}
		$this->_io = $io ?: new ConsoleIo();

		$this->_setModelClass($this->name);
		$this->modelFactory('Table', ['Cake\ORM\TableRegistry', 'get']);
		$this->Tasks = new TaskRegistry($this);

		$this->_io->setLoggers(true);
		$this->_mergeVars(
			['tasks'],
			['associative' => ['tasks']]
		);
	}

/**
 * Get/Set the io object for this shell.
 *
 * @param \Cake\Console\ConsoleIo $io
 * @return \Cake\Console\ConsoleIo
 */
	public function io(ConsoleIo $io = null) {
		if ($io !== null) {
			$this->_io = $io;
		}
		return $this->_io;
	}

/**
 * Initializes the Shell
 * acts as constructor for subclasses
 * allows configuration of tasks prior to shell execution
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::initialize
 */
	public function initialize() {
		$this->loadTasks();
	}

/**
 * Starts up the Shell and displays the welcome message.
 * Allows for checking and configuring prior to command or main execution
 *
 * Override this method if you want to remove the welcome information,
 * or otherwise modify the pre-command flow.
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::startup
 */
	public function startup() {
		$this->_welcome();
	}

/**
 * Displays a header for the shell
 *
 * @return void
 */
	protected function _welcome() {
		$this->out();
		$this->out(__d('cake_console', '<info>Welcome to CakePHP %s Console</info>', 'v' . Configure::version()));
		$this->hr();
		$this->out(__d('cake_console', 'App : %s', APP_DIR));
		$this->out(__d('cake_console', 'Path: %s', APP));
		$this->hr();
	}

/**
 * Lazy loads models using the loadModel() method if it matches modelClass
 *
 * @param string $name
 * @return void
 */
	public function __isset($name) {
		if ($name === $this->modelClass) {
			list($plugin, $class) = pluginSplit($name, true);
			if (!$plugin) {
				$plugin = $this->plugin ? $this->plugin . '.' : null;
			}
			return $this->loadModel($plugin . $this->modelClass);
		}
	}

/**
 * Loads tasks defined in public $tasks
 *
 * @return boolean
 */
	public function loadTasks() {
		if ($this->tasks === true || empty($this->tasks) || empty($this->Tasks)) {
			return true;
		}
		$this->_taskMap = $this->Tasks->normalizeArray((array)$this->tasks);
		$this->taskNames = array_merge($this->taskNames, array_keys($this->_taskMap));
		return true;
	}

/**
 * Check to see if this shell has a task with the provided name.
 *
 * @param string $task The task name to check.
 * @return boolean Success
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::hasTask
 */
	public function hasTask($task) {
		return isset($this->_taskMap[Inflector::camelize($task)]);
	}

/**
 * Check to see if this shell has a callable method by the given name.
 *
 * @param string $name The method name to check.
 * @return boolean
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::hasMethod
 */
	public function hasMethod($name) {
		try {
			$method = new \ReflectionMethod($this, $name);
			if (!$method->isPublic() || substr($name, 0, 1) === '_') {
				return false;
			}
			if ($method->getDeclaringClass()->name === 'Cake\Console\Shell') {
				return false;
			}
			return true;
		} catch (\ReflectionException $e) {
			return false;
		}
	}

/**
 * Dispatch a command to another Shell. Similar to Object::requestAction()
 * but intended for running shells from other shells.
 *
 * ### Usage:
 *
 * With a string command:
 *
 *	`return $this->dispatchShell('schema create DbAcl');`
 *
 * Avoid using this form if you have string arguments, with spaces in them.
 * The dispatched will be invoked incorrectly. Only use this form for simple
 * command dispatching.
 *
 * With an array command:
 *
 * `return $this->dispatchShell('schema', 'create', 'i18n', '--dry');`
 *
 * @return mixed The return of the other shell.
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::dispatchShell
 */
	public function dispatchShell() {
		$args = func_get_args();
		if (is_string($args[0]) && count($args) === 1) {
			$args = explode(' ', $args[0]);
		}

		$dispatcher = new ShellDispatcher($args, false);
		return $dispatcher->dispatch();
	}

/**
 * Runs the Shell with the provided argv.
 *
 * Delegates calls to Tasks and resolves methods inside the class. Commands are looked
 * up with the following order:
 *
 * - Method on the shell.
 * - Matching task name.
 * - `main()` method.
 *
 * If a shell implements a `main()` method, all missing method calls will be sent to
 * `main()` with the original method name in the argv.
 *
 * @param string $command The command name to run on this shell. If this argument is empty,
 *   and the shell has a `main()` method, that will be called instead.
 * @param array $argv Array of arguments to run the shell with. This array should be missing the shell name.
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::runCommand
 */
	public function runCommand($command, $argv) {
		$isTask = $this->hasTask($command);
		$isMethod = $this->hasMethod($command);
		$isMain = $this->hasMethod('main');

		if ($isTask || $isMethod && $command !== 'execute') {
			array_shift($argv);
		}

		$this->OptionParser = $this->getOptionParser();
		try {
			list($this->params, $this->args) = $this->OptionParser->parse($argv, $command);
		} catch (Error\ConsoleException $e) {
			$this->out($this->OptionParser->help($command));
			return false;
		}

		if (!empty($this->params['quiet'])) {
			$this->_io->level(ConsoleIo::QUIET);
			$this->_io->setLoggers(false);
		}
		if (!empty($this->params['verbose'])) {
			$this->_io->level(ConsoleIo::VERBOSE);
		}
		if (!empty($this->params['plugin'])) {
			Plugin::load($this->params['plugin']);
		}
		$this->command = $command;
		if (!empty($this->params['help'])) {
			return $this->_displayHelp($command);
		}

		if (($isTask || $isMethod || $isMain) && $command !== 'execute') {
			$this->startup();
		}

		if ($isTask) {
			$command = Inflector::camelize($command);
			return $this->{$command}->runCommand('execute', $argv);
		}
		if ($isMethod) {
			return $this->{$command}();
		}
		if ($isMain) {
			return $this->main();
		}
		$this->out($this->OptionParser->help($command));
		return false;
	}

/**
 * Display the help in the correct format
 *
 * @param string $command
 * @return void
 */
	protected function _displayHelp($command) {
		$format = 'text';
		if (!empty($this->args[0]) && $this->args[0] === 'xml') {
			$format = 'xml';
			$this->_io->outputAs(ConsoleOutput::RAW);
		} else {
			$this->_welcome();
		}
		return $this->out($this->OptionParser->help($command, $format));
	}

/**
 * Gets the option parser instance and configures it.
 *
 * By overriding this method you can configure the ConsoleOptionParser before returning it.
 *
 * @return ConsoleOptionParser
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::getOptionParser
 */
	public function getOptionParser() {
		$name = ($this->plugin ? $this->plugin . '.' : '') . $this->name;
		$parser = new ConsoleOptionParser($name);
		return $parser;
	}

/**
 * Overload get for lazy building of tasks
 *
 * @param string $name
 * @return Shell Object of Task
 */
	public function __get($name) {
		if (empty($this->{$name}) && in_array($name, $this->taskNames)) {
			$properties = $this->_taskMap[$name];
			$this->{$name} = $this->Tasks->load($properties['class'], $properties['config']);
			$this->{$name}->args =& $this->args;
			$this->{$name}->params =& $this->params;
			$this->{$name}->initialize();
			$this->{$name}->loadTasks();
		}
		return $this->{$name};
	}

/**
 * Prompts the user for input, and returns it.
 *
 * @param string $prompt Prompt text.
 * @param string|array $options Array or string of options.
 * @param string $default Default input value.
 * @return mixed Either the default value, or the user-provided input.
 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#Shell::in
 */
	public function in($prompt, $options = null, $default = null) {
		if (!$this->interactive) {
			return $default;
		}
		if ($options) {
			return $this->_io->askChoice($prompt, $options, $default);
		}
		return $this->_io->ask($prompt, $default);
	}

/**
 * Wrap a block of text.
 * Allows you to set the width, and indenting on a block of text.
 *
 * ### Options
 *
 * - `width` The width to wrap to. Defaults to 72
 * - `wordWrap` Only wrap on words breaks (spaces) Defaults to true.
 * - `indent` Indent the text with the string provided. Defaults to null.
 *
 * @param string $text Text the text to format.
 * @param integer|array $options Array of options to use, or an integer to wrap the text to.
 * @return string Wrapped / indented text
 * @see String::wrap()
 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#Shell::wrapText
 */
	public function wrapText($text, $options = []) {
		return String::wrap($text, $options);
	}

/**
 * Outputs a single or multiple messages to stdout. If no parameters
 * are passed outputs just a newline.
 *
 * ### Output levels
 *
 * There are 3 built-in output level. Shell::QUIET, Shell::NORMAL, Shell::VERBOSE.
 * The verbose and quiet output levels, map to the `verbose` and `quiet` output switches
 * present in most shells. Using Shell::QUIET for a message means it will always display.
 * While using Shell::VERBOSE means it will only display when verbose output is toggled.
 *
 * @param string|array $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @param integer $level The message's output level, see above.
 * @return integer|boolean Returns the number of bytes returned from writing to stdout.
 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#Shell::out
 */
	public function out($message = null, $newlines = 1, $level = Shell::NORMAL) {
		return $this->_io->out($message, $newlines, $level);
	}

/**
 * Outputs a single or multiple error messages to stderr. If no parameters
 * are passed outputs just a newline.
 *
 * @param string|array $message A string or a an array of strings to output
 * @param integer $newlines Number of newlines to append
 * @return void
 */
	public function err($message = null, $newlines = 1) {
		return $this->_io->err($message, $newlines);
	}

/**
 * Returns a single or multiple linefeeds sequences.
 *
 * @param integer $multiplier Number of times the linefeed sequence should be repeated
 * @return string
 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#Shell::nl
 */
	public function nl($multiplier = 1) {
		return $this->_io->nl($multiplier);
	}

/**
 * Outputs a series of minus characters to the standard output, acts as a visual separator.
 *
 * @param integer $newlines Number of newlines to pre- and append
 * @param integer $width Width of the line, defaults to 63
 * @return void
 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#Shell::hr
 */
	public function hr($newlines = 0, $width = 63) {
		return $this->_io->hr($newlines, $width);
	}

/**
 * Displays a formatted error message
 * and exits the application with status code 1
 *
 * @param string $title Title of the error
 * @param string $message An optional error message
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::error
 */
	public function error($title, $message = null) {
		$this->_io->err(__d('cake_console', '<error>Error:</error> %s', $title));

		if (!empty($message)) {
			$this->_io->err($message);
		}
		return $this->_stop(1);
	}

/**
 * Clear the console
 *
 * @return void
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::clear
 */
	public function clear() {
		if (empty($this->params['noclear'])) {
			if (DS === '/') {
				passthru('clear');
			} else {
				passthru('cls');
			}
		}
	}

/**
 * Creates a file at given path
 *
 * @param string $path Where to put the file.
 * @param string $contents Content to put in the file.
 * @return boolean Success
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::createFile
 */
	public function createFile($path, $contents) {
		$path = str_replace(DS . DS, DS, $path);

		$this->_io->out();

		if (is_file($path) && empty($this->params['force'])) {
			$this->_io->out(__d('cake_console', '<warning>File `%s` exists</warning>', $path));
			$key = $this->_io->askChoice(__d('cake_console', 'Do you want to overwrite?'), ['y', 'n', 'q'], 'n');

			if (strtolower($key) === 'q') {
				$this->_io->out(__d('cake_console', '<error>Quitting</error>.'), 2);
				return $this->_stop();
			} elseif (strtolower($key) !== 'y') {
				$this->_io->out(__d('cake_console', 'Skip `%s`', $path), 2);
				return false;
			}
		} else {
			$this->out(__d('cake_console', 'Creating file %s', $path));
		}

		$File = new File($path, true);
		if ($File->exists() && $File->writable()) {
			$data = $File->prepare($contents);
			$File->write($data);
			$this->_io->out(__d('cake_console', '<success>Wrote</success> `%s`', $path));
			return true;
		}

		$this->_io->err(__d('cake_console', '<error>Could not write to `%s`</error>.', $path), 2);
		return false;
	}

/**
 * Makes absolute file path easier to read
 *
 * @param string $file Absolute file path
 * @return string short path
 * @link http://book.cakephp.org/3.0/en/console-and-shells.html#Shell::shortPath
 */
	public function shortPath($file) {
		$shortPath = str_replace(ROOT, null, $file);
		$shortPath = str_replace('..' . DS, '', $shortPath);
		$shortPath = str_replace(DS, '/', $shortPath);
		return str_replace('//', DS, $shortPath);
	}

}
