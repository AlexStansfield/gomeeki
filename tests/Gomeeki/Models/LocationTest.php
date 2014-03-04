<?php

namespace AlexStansfield\Tests\Gomeeki\Models;

use AlexStansfield\GoMeeki\Models\Location;

class LocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $mockConnection;

    public function setUp()
    {
        $this->mockConnection = \Mockery::mock('\Doctrine\DBAL\Connection');
        parent::setUp();
    }

    protected function getTestData()
    {
        $locationData = array(
            'locationId' => 1,
            'name' => 'Test Name',
            'longitude' => 50.1234,
            'latitude' => -50.1234,
            'lastTwitterSearch' => '2014-03-01 12:34:56',
        );

        return $locationData;
    }

    public function testGetLocationId()
    {
        $location = new Location($this->mockConnection, $this->getTestData());

        $this->assertSame(1, $location->getLocationId());
    }

    public function testGetName()
    {
        $location = new Location($this->mockConnection, $this->getTestData());

        $this->assertSame('Test Name', $location->getName());
    }

    public function testGetLatitude()
    {
        $location = new Location($this->mockConnection, $this->getTestData());

        $this->assertSame(-50.1234, $location->getLatitude());
    }

    public function testGetLongitude()
    {
        $location = new Location($this->mockConnection, $this->getTestData());

        $this->assertSame(50.1234, $location->getLongitude());
    }

    public function testLastTwitterSearch()
    {
        $location = new Location($this->mockConnection, $this->getTestData());

        $this->assertSame('2014-03-01 12:34:56', $location->getLastTwitterSearch());
    }

    public function testGetSecondsSinceLastTwitterSearch()
    {
        $difference = 200;

        $data = $this->getTestData();
        $data['lastTwitterSearch'] = date('Y-m-d H:i:s', time()-$difference);
        $location = new Location($this->mockConnection, $data);

        $this->assertSame($difference, $location->getSecondsSinceLastTwitterSearch());
    }

    public function testFindByName()
    {
        $name = 'test';
        $testData = $this->getTestData();
        $expected = new Location($this->mockConnection, $testData);

        // Setup the mock sql statement
        $mockStmt = \Mockery::mock('\Doctrine\DBAL\Driver\Statement');
        $mockStmt->shouldReceive('fetch')->once()->with(\PDO::FETCH_ASSOC)->andReturn($testData);

        // Setup the mock Query Builder
        $mockQB = \Mockery::mock('\Doctrine\DBAL\Query\QueryBuilder');
        $mockQB->shouldReceive('select')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('from')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('where')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('setParameter')->with('name', $name)->once()->andReturn($mockQB);
        $mockQB->shouldReceive('execute')->once()->andReturn($mockStmt);

        // Setup the mock Connection
        $this->mockConnection
            ->shouldReceive('createQueryBuilder')
            ->once()
            ->andReturn($mockQB);

        $location = Location::findByName($name, $this->mockConnection);
        $this->assertEquals($expected, $location);
    }

    public function testCreate()
    {
        $name = 'test create';
        $latitude = 12.3456;
        $longitude = 65.4321;

        // Setup expected Result
        $expected_data = array(
            'locationId' => 999,
            'name' => $name,
            'latitude' => $latitude,
            'longitude' => $longitude
        );
        $expected = new Location($this->mockConnection, $expected_data);

        // Setup the mock Connection
        $this->mockConnection
            ->shouldReceive('insert')
            ->once()
            ->with('location', array('name' => $name, 'latitude' => $latitude, 'longitude' => $longitude))
            ->andReturn(1);

        $this->mockConnection
            ->shouldReceive('lastInsertId')
            ->once()
            ->andReturn(999);

        $location = Location::create($name, $latitude, $longitude, $this->mockConnection);

        $this->assertEquals($expected, $location);
    }

    public function testCreateThrowsExceptionIfLocationInsertFails()
    {
        $name = 'test create';
        $latitude = 12.3456;
        $longitude = 65.4321;

        $data = array('name' => $name, 'latitude' => $latitude, 'longitude' => $longitude);

        // Setup the mock Connection
        $this->mockConnection
            ->shouldReceive('insert')
            ->once()
            ->with('location', $data)
            ->andReturn(false);

        $this->setExpectedException('Exception');
        Location::create($name, $latitude, $longitude, $this->mockConnection);
    }

    public function testUpdateTwitterSearch()
    {
        $location = new Location($this->mockConnection, $this->getTestData());

        // Setup the mock Connection
        $this->mockConnection
            ->shouldReceive('update')
            ->once()
            ->andReturn(1);

        $this->assertSame('2014-03-01 12:34:56', $location->getLastTwitterSearch());
        $this->assertInstanceOf('\AlexStansfield\Gomeeki\Models\Location', $location->updateTwitterSearch());
        $this->assertSame(date('Y-m-d H:i:s'), $location->getLastTwitterSearch());
    }

    public function testUpdateTwitterSearchThrowsExceptionIfUpdateFails()
    {
        $location = new Location($this->mockConnection, $this->getTestData());

        $this->mockConnection
            ->shouldReceive('update')
            ->once()
            ->andReturn(0);

        $this->setExpectedException('Exception');
        $location->updateTwitterSearch();
    }
}
