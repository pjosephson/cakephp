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
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Type;
use Cake\Database\Type\TimeType;
use Cake\TestSuite\TestCase;

/**
 * Test for the Time type.
 */
class TimeTypeTest extends TestCase {

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->type = Type::build('time');
		$this->driver = $this->getMock('Cake\Database\Driver');
	}

/**
 * Test toPHP
 *
 * @return void
 */
	public function testToPHP() {
		$this->assertNull($this->type->toPHP(null, $this->driver));

		$result = $this->type->toPHP('16:30:15', $this->driver);
		$this->assertInstanceOf('DateTime', $result);
		$this->assertEquals('16', $result->format('H'));
		$this->assertEquals('30', $result->format('i'));
		$this->assertEquals('15', $result->format('s'));

		$result = $this->type->toPHP('2001-01-04 10:11:12', $this->driver);
		$this->assertFalse($result);
	}

/**
 * Test converting to database format
 *
 * @return void
 */
	public function testToDatabase() {
		$value = '16:30:15';
		$result = $this->type->toDatabase($value, $this->driver);
		$this->assertEquals($value, $result);

		$date = new \DateTime('16:30:15');
		$result = $this->type->toDatabase($date, $this->driver);
		$this->assertEquals('16:30:15', $result);

		$date = new \DateTime('2013-08-12 15:16:18');
		$result = $this->type->toDatabase($date, $this->driver);
		$this->assertEquals('15:16:18', $result);
	}

/**
 * Data provider for marshal()
 *
 * @return array
 */
	public function marshalProvider() {
		$date = new \DateTime('@1392387900');

		return [
			// invalid types.
			[null, null],
			[false, false],
			[true, true],
			['', ''],
			['derpy', 'derpy'],
			['16-nope!', '16-nope!'],

			// valid string types
			['1392387900', $date],
			[1392387900, $date],
			['13:10:10', new \DateTime('13:10:10')],
			['2014-02-14 13:14:15', new \DateTime('2014-02-14 13:14:15')],

			// valid array types
			[
				['year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 13, 'minute' => 14, 'second' => 15],
				new \DateTime('2014-02-14 13:14:15')
			],
			[
				[
					'year' => 2014, 'month' => 2, 'day' => 14,
					'hour' => 1, 'minute' => 14, 'second' => 15,
					'meridian' => 'am'
				],
				new \DateTime('2014-02-14 01:14:15')
			],
			[
				[
					'year' => 2014, 'month' => 2, 'day' => 14,
					'hour' => 1, 'minute' => 14, 'second' => 15,
					'meridian' => 'pm'
				],
				new \DateTime('2014-02-14 13:14:15')
			],
			[
				[
					'hour' => 1, 'minute' => 14, 'second' => 15,
				],
				new \DateTime('01:14:15')
			],

			// Invalid array types
			[
				['hour' => 'nope', 'minute' => 14, 'second' => 15],
				new \DateTime(date('Y-m-d 00:14:15'))
			],
			[
				[
					'year' => '2014', 'month' => '02', 'day' => '14',
					'hour' => 'nope', 'minute' => 'nope'
				],
				new \DateTime('2014-02-14 00:00:00')
			],
		];
	}

/**
 * test marshalling data.
 *
 * @dataProvider marshalProvider
 * @return void
 */
	public function testMarshal($value, $expected) {
		$result = $this->type->marshal($value);
		$this->assertEquals($expected, $result);
	}

}