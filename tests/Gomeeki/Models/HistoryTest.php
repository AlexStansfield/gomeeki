<?php

namespace AlexStansfield\Tests\Gomeeki\Models;

use AlexStansfield\GoMeeki\Models\History;

class HistoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $mockConnection;

    /**
     * @var History
     */
    protected $history;

    public function setUp()
    {
        $this->mockConnection = \Mockery::mock('\Doctrine\DBAL\Connection');
        $this->history = new History($this->mockConnection);
        parent::setUp();
    }

    public function testAdd()
    {
        $sessionId = 'TESTSESSIONID';
        $locationId = 999;

        $mockLocation = \Mockery::mock('AlexStansfield\GoMeeki\Models\Location');
        $mockLocation->shouldReceive('getLocationId')->once()->andReturn($locationId);

        // Setup the mock Connection
        $this->mockConnection
            ->shouldReceive('insert')
            ->once()
            ->with('history', array('sessionId' => $sessionId, 'locationId' => $locationId))
            ->andReturn(1);

        $this->assertTrue($this->history->add($sessionId, $mockLocation));
    }

    public function testFetch()
    {
        $sessionId = 'testSessionId';
        $expected = array(array('name' => 'test 1'), array('name' => 'test 2'));

        // Setup the mock sql statement
        $mockStmt = \Mockery::mock('\Doctrine\DBAL\Driver\Statement');
        $mockStmt->shouldReceive('fetchAll')->once()->with(\PDO::FETCH_ASSOC)->andReturn($expected);

        // Setup the mock Query Builder
        $mockQB = \Mockery::mock('\Doctrine\DBAL\Query\QueryBuilder');
        $mockQB->shouldReceive('select')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('from')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('join')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('where')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('setParameter')->with('sessionId', $sessionId)->once()->andReturn($mockQB);
        $mockQB->shouldReceive('groupBy')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('orderBy')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('execute')->once()->andReturn($mockStmt);

        // Setup the mock Connection
        $this->mockConnection
            ->shouldReceive('createQueryBuilder')
            ->once()
            ->andReturn($mockQB);

        $this->assertEquals($expected, $this->history->fetch($sessionId));
    }
}
