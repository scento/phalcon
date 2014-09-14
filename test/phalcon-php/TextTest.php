<?php
/**
 * Text Testsuite
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/
class TextTest extends BaseTest
{
	public function testCamelize()
	{
		$this->assertEquals(\Phalcon\Text::camelize('coco_bongo'), 'CocoBongo');
		$this->assertEquals(\Phalcon\Text::camelize('what-ever'), 'WhatEver');
		$this->assertEquals(\Phalcon\Text::camelize('strange-sTring'), 'StrangeString');
		$this->assertEquals(\Phalcon\Text::camelize('specialString-'), 'Specialstring'.chr(0));
	}

	public function testCamelizeException()
	{
		$this->setExpectedException('PHPUnit_Framework_Error');
		\Phalcon\Text::camelize(false);
	}

	public function testUncamelize()
	{
		$this->assertEquals(\Phalcon\Text::uncamelize('CocoBongo'), 'coco_bongo');
		$this->assertEquals(\Phalcon\Text::uncamelize('unicödeTest'), 'unicöde_test');
		$this->assertEquals(\Phalcon\Text::uncamelize('another-Test'), 'another-_test');
		$this->assertEquals(\Phalcon\Text::uncamelize('withInter'.chr(0).'ruption'), 'with_inter');
	}

	public function testUncamelizeException()
	{
		$this->setExpectedException('PHPUnit_Framework_Error');
		\Phalcon\Text::uncamelize(1.2);
	}

	public function testIncrement()
	{
		$this->assertEquals(\Phalcon\Text::increment('a'), 'a_1');
		$this->assertEquals(\Phalcon\Text::increment('a_1'), 'a_2');
		$this->assertEquals(\Phalcon\Text::increment('b', '-'), 'b-1');
		$this->assertEquals(\Phalcon\Text::increment('b-1', '-'), 'b-2');
	}

	public function testIncrementExceptionA()
	{
		$this->setExpectedException('\Phalcon\Exception');
		\Phalcon\Text::increment(false, null);
	}

	public function testIncrementExceptionB()
	{
		$this->setExpectedException('\Phalcon\Exception');
		\Phalcon\Text::increment('a_c', 6);
	}

	public function testRandomAlnum()
	{
		$generated = \Phalcon\Text::random(\Phalcon\Text::RANDOM_ALNUM, 255);
		$match = preg_match('#^[A-Za-z0-9]{255}$#', $generated);

		$this->assertTrue($match === 1);
	}

	public function testRandomAlpha()
	{
		$generated = \Phalcon\Text::random(\Phalcon\Text::RANDOM_ALPHA, 255);
		$match = preg_match('#^[a-z]{255}$#', $generated);

		$this->assertTrue($match === 1);
	}

	public function testRandomHexdec()
	{
		$generated = \Phalcon\Text::random(\Phalcon\Text::RANDOM_HEXDEC, 255);
		$match = preg_match('#^[0-9a-f]{255}$#', $generated);

		$this->assertTrue($match === 1);
	}

	public function testRandomNumeric()
	{
		$generated = \Phalcon\Text::random(\Phalcon\Text::RANDOM_NUMERIC, 255);
		$match = preg_match('#^[0-9]{255}$#', $generated);

		$this->assertTrue($match === 1);
	}

	public function testRandomNozero()
	{
		$generated = \Phalcon\Text::random(\Phalcon\Text::RANDOM_NOZERO, 255);
		$match = preg_match('#^[1-9]{255}$#', $generated);

		$this->assertTrue($match === 1);
	}

	public function testRandomDefaultLength()
	{
		$generated = \Phalcon\Text::random(\Phalcon\Text::RANDOM_NOZERO);
		$this->assertEquals(strlen($generated), 8);
	}

	public function testRandomStrangeExceptionBehavior()
	{
		$this->assertTrue(is_null(\Phalcon\Text::random(-1)));
		$this->assertTrue(is_null(\Phalcon\Text::random(5)));
		$this->assertTrue(is_null(\Phalcon\Text::random(false)));
		$this->assertTrue(is_null(\Phalcon\Text::random(\Phalcon\Text::RANDOM_NOZERO, 'false')));
		$this->assertTrue(is_null(\Phalcon\Text::random(\Phalcon\Text::RANDOM_NOZERO, false)));
	}

	public function testStartsWith()
	{
		$this->assertTrue(\Phalcon\Text::startsWith('Hello', 'He'));
		$this->assertFalse(\Phalcon\Text::startsWith('Hello', 'he'));
		$this->assertTrue(\Phalcon\Text::startsWith('Hello', 'he', false));
		$this->assertFalse(\Phalcon\Text::startsWith('Hello', 'he', 3));
	}

	public function testStartsWithException()
	{
		$this->setExpectedException('\Phalcon\Exception');
		\Phalcon\Text::startsWith(false, 'he');
	}

	public function testEndsWith()
	{
		$this->assertTrue(\Phalcon\Text::endsWith('Hello', 'llo'));
		$this->assertFalse(\Phalcon\Text::endsWith('Hello', 'LLO'));
		$this->assertTrue(\Phalcon\Text::endsWith('Hello', 'LLO', false));
		$this->assertFalse(\Phalcon\Text::endsWith('Hello', 'LLO', 3));
	}

	public function testEndsWithException()
	{
		$this->setExpectedException('\Phalcon\Exception');
		\Phalcon\Text::endsWith(false, 'he');
	}

	public function testLower()
	{
		$this->assertEquals(\Phalcon\Text::lower('StrangeText'), 'strangetext');
		$this->assertEquals(\Phalcon\Text::lower('Nothing_ToLower'), 'nothing_tolower');
		$this->assertEquals(\Phalcon\Text::lower('already'), 'already');
		$this->assertEquals(\Phalcon\Text::lower('Ümlaute'), 'ümlaute');
	}

	public function testLowerException()
	{
		$this->setExpectedException('\Phalcon\Exception');
		\Phalcon\Text::lower(13.43);
	}

	public function testUpper()
	{
		$this->assertEquals(\Phalcon\Text::upper('pleaseUpperThisText'), 'PLEASEUPPERTHISTEXT');
		$this->assertEquals(\Phalcon\Text::upper('ALREADY'), 'ALREADY');
		$this->assertEquals(\Phalcon\Text::upper('SPECIAL_CHARs'), 'SPECIAL_CHARS');
		$this->assertEquals(\Phalcon\Text::upper('üMLAUTE'), 'ÜMLAUTE');
	}

	public function testUpperException()
	{
		$this->setExpectedException('\Phalcon\Exception');
		\Phalcon\Text::upper(false);
	}
}