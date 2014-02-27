<?php

namespace Guzzle\Tests\Service\Description;

use GuzzleHttp\Command\Guzzle\Operation;
use GuzzleHttp\Command\Guzzle\Parameter;
use GuzzleHttp\Command\Guzzle\Description;

/**
 * @covers \GuzzleHttp\Command\Guzzle\Operation
 */
class OperationTest extends \PHPUnit_Framework_TestCase
{
    public static function strtoupper($string)
    {
        return strtoupper($string);
    }

    public function testOperationIsDataObject()
    {
        $description = new Description([]);
        $c = new Operation(array(
            'name'               => 'test',
            'summary'            => 'doc',
            'notes'              => 'notes',
            'documentationUrl'   => 'http://www.example.com',
            'httpMethod'         => 'POST',
            'uri'                => '/api/v1',
            'responseModel'      => 'abc',
            'deprecated'         => true,
            'parameters'         => array(
                'key' => array(
                    'required'  => true,
                    'type'      => 'string',
                    'maxLength' => 10
                ),
                'key_2' => array(
                    'required' => true,
                    'type'     => 'integer',
                    'default'  => 10
                )
            )
        ), $description);

        $this->assertEquals('test', $c->getName());
        $this->assertEquals('doc', $c->getSummary());
        $this->assertEquals('http://www.example.com', $c->getDocumentationUrl());
        $this->assertEquals('POST', $c->getHttpMethod());
        $this->assertEquals('/api/v1', $c->getUri());
        $this->assertEquals('abc', $c->getResponseModel());
        $this->assertTrue($c->getDeprecated());

        $params = array_map(function ($c) {
            return $c->toArray();
        }, $c->getParams());

        $this->assertEquals([
            'key' => [
                'required'  => true,
                'type'      => 'string',
                'maxLength' => 10,
            ],
            'key_2' => [
                'required' => true,
                'type'     => 'integer',
                'default'  => 10,
            ]
        ], $params);

        $this->assertEquals([
            'required' => true,
            'type'     => 'integer',
            'default'  => 10,
        ], $c->getParam('key_2')->toArray());

        $this->assertNull($c->getParam('afefwef'));
        $this->assertArrayNotHasKey('parent', $c->getParam('key_2')->toArray());
    }

    public function testConvertsToArray()
    {
        $data = [
            'name'             => 'test',
            'summary'          => 'test',
            'documentationUrl' => 'http://www.example.com',
            'httpMethod'       => 'PUT',
            'uri'              => '/',
            'parameters'       => ['p' => ['name' => 'foo']]
        ];
        $c = new Operation($data, new Description([]));
        $toArray = $c->toArray();
        unset($data['name']);
        $this->assertArrayHasKey('parameters', $toArray);
        $this->assertInternalType('array', $toArray['parameters']);

        // Normalize the array
        unset($data['parameters']);
        unset($toArray['parameters']);

        $this->assertEquals($data, $toArray);
    }

    public function testDeterminesIfHasParam()
    {
        $command = $this->getTestCommand();
        $this->assertTrue($command->hasParam('data'));
        $this->assertFalse($command->hasParam('baz'));
    }

    protected function getTestCommand()
    {
        return new Operation([
            'parameters' => [
                'data' => ['type' => 'string']
            ]
        ], new Description([]));
    }

    public function testAddsNameToParametersIfNeeded()
    {
        $command = new Operation(['parameters' => ['foo' => []]], new Description([]));
        $this->assertEquals('foo', $command->getParam('foo')->getName());
    }

    public function testContainsApiErrorInformation()
    {
        $command = $this->getOperation();
        $this->assertEquals(1, count($command->getErrorResponses()));
        $arr = $command->toArray();
        $this->assertEquals(1, count($arr['errorResponses']));
    }

    public function testHasNotes()
    {
        $o = new Operation(array('notes' => 'foo'), new Description([]));
        $this->assertEquals('foo', $o->getNotes());
    }

    public function testHasData()
    {
        $o = new Operation(array('data' => array('foo' => 'baz', 'bar' => 123)), new Description([]));
        $this->assertEquals('baz', $o->getData('foo'));
        $this->assertEquals(123, $o->getData('bar'));
        $this->assertNull($o->getData('wfefwe'));
        $this->assertEquals([
            'parameters'    => [],
            'data'          => ['foo' => 'baz', 'bar' => 123]
        ], $o->toArray());
        $this->assertEquals(['foo' => 'baz', 'bar' => 123], $o->getData());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMesssage Parameters must be arrays
     */
    public function testEnsuresParametersAreArrays()
    {
        new Operation(['parameters' => ['foo' => true]], new Description([]));
    }

    public function testHasDescription()
    {
        $s = new Description([]);
        $o = new Operation(array(), $s);
        $this->assertSame($s, $o->getServiceDescription());
    }

    public function testHasAdditionalParameters()
    {
        $o = new Operation(array(
            'additionalParameters' => array(
                'type' => 'string', 'name' => 'binks'
            ),
            'parameters' => array(
                'foo' => array('type' => 'integer')
            )
        ), new Description([]));
        $this->assertEquals('string', $o->getAdditionalParameters()->getType());
        $arr = $o->toArray();
        $this->assertEquals(array(
            'type' => 'string'
        ), $arr['additionalParameters']);
    }

    /**
     * @return Operation
     */
    protected function getOperation()
    {
        return new Operation(array(
            'name'       => 'OperationTest',
            'class'      => get_class($this),
            'parameters' => array(
                'test'          => array('type' => 'object'),
                'bool_1'        => array('default' => true, 'type' => 'boolean'),
                'bool_2'        => array('default' => false),
                'float'         => array('type' => 'numeric'),
                'int'           => array('type' => 'integer'),
                'date'          => array('type' => 'string'),
                'timestamp'     => array('type' => 'string'),
                'string'        => array('type' => 'string'),
                'username'      => array('type' => 'string', 'required' => true, 'filters' => 'strtolower'),
                'test_function' => array('type' => 'string', 'filters' => __CLASS__ . '::strtoupper')
            ),
            'errorResponses' => array(
                array('code' => 503, 'reason' => 'InsufficientCapacity', 'class' => 'Guzzle\\Exception\\RuntimeException')
            )
        ), new Description([]));
    }
}
