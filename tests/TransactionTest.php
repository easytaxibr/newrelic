<?php

namespace EasyTaxi\NewRelic;

use EasyTaxi\NewRelic\Config\TransactionConfig;
use EasyTaxi\NewRelic\Test\Stub\Foo;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
    private $transaction;
    public static $transactionName;
    public static $applicationNameStarted;
    public static $customParameters;
    public static $endTransaction;
    public static $exceptionMessage;
    public static $exception;

    public function setUp()
    {
        $config = new TransactionConfig();
        $config->applicationName = 'Panthro';
        $config->transactionName = 'Jaga';
        $this->transaction = new Transaction(new Foo(), $config);
        $this->transaction->extensionAvailable = true;
        self::$endTransaction = false;
    }

    public function testHasAbilityToSetApplicationName()
    {
        $this->transaction->bar();

        $this->assertEquals('Panthro', self::$applicationNameStarted);
    }

    public function testCanSetTransactionName()
    {
        $this->transaction->bar();

        $this->assertEquals('Jaga', self::$transactionName);
    }

    public function testCanAddComplexArgumentsToNewRelic()
    {
        $complexArgument = [
            0 => 'simple',
            1 => ['array' => 'simple'],
            2 => ['string' => 'simple', 'named array' => ['json'], 'object' => new \stdClass()]
        ];

        $this->transaction->bar($complexArgument[0], $complexArgument[1], $complexArgument[2]);

        $this->assertEquals([
            '0' => 'simple',
            'array' => 'simple',
            'string' => 'simple',
            'named array' => '["json"]',
            'object' => '{}'
        ], self::$customParameters);
    }

    public function testArgumentAreCorrectedPassedToObject()
    {
        $argument = 'Cheetara';

        $returnedArgument = $this->transaction->bar($argument);

        $this->assertEquals($argument, $returnedArgument);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cheetara
     */
    public function testExceptionStillTheSame()
    {
        $expectedException = new \InvalidArgumentException('Cheetara');

        $this->transaction->fooThrowThisException($expectedException);
    }

    public function testExceptionIsRecordedOnNewRelic()
    {
        $expectedException = new \InvalidArgumentException('Cheetara');

        try {
            $this->transaction->fooThrowThisException($expectedException);
        } catch (\Exception $exception) {
            //Ignoring exceptions
        }

        $this->assertNotEmpty(self::$exceptionMessage);
        $this->assertEquals($expectedException, self::$exception);
    }

    public function testEndTransactionAndSendToNewRelicWhenAnExceptionHappen()
    {
        $expectedException = new \InvalidArgumentException('Cheetara');

        try {
            $this->transaction->fooThrowThisException($expectedException);
        } catch (\Exception $exception) {
            //Ignoring exceptions
        }

        $this->assertTrue(self::$endTransaction);
    }

    public function testEndTransactionAndSendToNewRelic()
    {
        $this->transaction->bar();

        $this->assertTrue(self::$endTransaction);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNonObject()
    {
        new Transaction('Cheetara', new TransactionConfig());
    }
}

function newrelic_start_transaction($appName)
{
    TransactionTest::$applicationNameStarted = $appName;
}

function newrelic_name_transaction($transactionName)
{
    TransactionTest::$transactionName = $transactionName;
}

function newrelic_add_custom_parameter($key, $value)
{
    TransactionTest::$customParameters[$key] = $value;
    return true;
}

function newrelic_end_transaction()
{
    TransactionTest::$endTransaction = true;
}

function newrelic_notice_error($exceptionMessage, \Exception $exception = null)
{
    TransactionTest::$exceptionMessage = $exceptionMessage;
    TransactionTest::$exception = $exception;
}

function extension_loaded($extension)
{
    return true;
}
