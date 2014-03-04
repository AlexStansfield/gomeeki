<?php

use AlexStansfield\Gomeeki\Models\Tweets;

class TweetsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $mockConnection;

    /**
     * @var \Endroid\Twitter\Twitter
     */
    protected $mockTwitter;

    public function setUp()
    {
        $this->mockConnection = \Mockery::mock('\Doctrine\DBAL\Connection');
        $this->mockTwitter = \Mockery::mock('\Endroid\Twitter\Twitter');
    }

    public function testSearchTwitter()
    {
        $expects = array(
            array("text" => "test 1", "coordinates" => "coordinates 1"),
            array("text" => "test 3", "coordinates" => "coordinates 2"));

        $tweets = new Tweets($this->mockConnection, $this->mockTwitter);

        // json response from twitter
        $response = '{"statuses": [
        { "text": "test 1", "coordinates": "coordinates 1" },
        { "text": "test 2" },
        { "text": "test 3", "coordinates": "coordinates 2" },
        { "text": "test 4", "coordinates": null }
        ] }';

        // setup mock location
        $mockLocation = \Mockery::mock('AlexStansfield\GoMeeki\Models\Location');
        $mockLocation->shouldReceive('getName')->once()->andReturn('test');
        $mockLocation->shouldReceive('getLatitude')->once()->andReturn(12.3456);
        $mockLocation->shouldReceive('getLongitude')->once()->andReturn(65.4321);

        // setup mock response from twitter
        $mockResponse = \Mockery::mock('\Buzz\Message\Response');
        $mockResponse->shouldReceive('getContent')->once()->andReturn($response);

        // setup the mock twitter client
        $this->mockTwitter
            ->shouldReceive('query')
            ->once()
            ->with('search/tweets', 'GET', 'json', array('q' => 'test', 'geocode' => '12.3456,65.4321,50km', 'count' => 100))
            ->andReturn($mockResponse);

        $results = $tweets->searchTwitter($mockLocation);
        $this->assertSame($expects, $results);
    }
}