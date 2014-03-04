<?php

use AlexStansfield\GoMeeki\Models\Location;
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

}
