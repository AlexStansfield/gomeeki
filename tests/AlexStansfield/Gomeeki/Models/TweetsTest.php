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
        parent::setUp();
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
            ->with('search/tweets', 'GET', 'json', array(
                'q' => 'test',
                'geocode' => '12.3456,65.4321,50km',
                'count' => 100))
            ->andReturn($mockResponse);

        $results = $tweets->searchTwitter($mockLocation);
        $this->assertSame($expects, $results);
    }

    public function testSaveTweets()
    {
        $tweetsArray = array(
            array(
                "id_str" => "idstring1",
                "created_at" => "Mon Sep 24 04:46:45 +0000 2012",
                "text" => "test 1",
                "coordinates" => array('coordinates' => array(12.3456, 65.4321)),
                "user" => array(
                    "id" => 123456,
                    "screen_name" => "@tweetmaster",
                    "profile_image_url" => "http://www.example.com/image.jpg"
                )
            ),
            array(
                "id_str" => "idstring2",
                "created_at" => "Mon Sep 24 03:35:21 +0000 2012",
                "text" => "test 2",
                "coordinates" => array('coordinates' => array(65.4321, 12.3456)),
                "user" => array(
                    "id" => 654321,
                    "screen_name" => "@testtweeter",
                    "profile_image_url" => "http://www.test.com/image.jpg"
                )
            )
        );

        $expected = array(
            array(
                "locationId" => 999,
                "user" => "@tweetmaster",
                "profileImageUrl" => "http://www.example.com/image.jpg",
                "content" => "test 1",
                "latitude" => 12.3456,
                "longitude" => 65.4321,
                "posted" => "2012-09-24 04:46:45"
            ),
            array(
                "locationId" => 999,
                "user" => "@testtweeter",
                "profileImageUrl" => "http://www.test.com/image.jpg",
                "content" => "test 2",
                "latitude" => 65.4321,
                "longitude" => 12.3456,
                "posted" => "2012-09-24 03:35:21"
            )
        );

        // setup mock location
        $mockLocation = \Mockery::mock('AlexStansfield\GoMeeki\Models\Location');
        $mockLocation->shouldReceive('getLocationId')->times(3)->andReturn(999);
        $mockLocation->shouldReceive('updateTwitterSearch')->once()->andReturn(true);

        //-- Setup the mock connection
        // Delete
        $this->mockConnection
            ->shouldReceive('delete')
            ->once()
            ->with('tweet', array('locationId' => 999));
        // Inserts
        $this->mockConnection
            ->shouldReceive('insert')
            ->with('tweet', $expected[0]);
        $this->mockConnection
            ->shouldReceive('insert')
            ->with('tweet', $expected[1]);

        $tweets = new Tweets($this->mockConnection, $this->mockTwitter);
        $this->assertSame($expected, $tweets->saveTweets($mockLocation, $tweetsArray));
    }

    public function testGetTweets()
    {
        $expected = array(
            array(
                "locationId" => 999,
                "user" => "@tweetmaster",
                "profileImageUrl" => "http://www.example.com/image.jpg",
                "content" => "test 1",
                "latitude" => 12.3456,
                "longitude" => 65.4321,
                "posted" => "2012-09-24 04:46:45"
            ),
            array(
                "locationId" => 999,
                "user" => "@testtweeter",
                "profileImageUrl" => "http://www.test.com/image.jpg",
                "content" => "test 2",
                "latitude" => 65.4321,
                "longitude" => 12.3456,
                "posted" => "2012-09-24 03:35:21"
            )
        );

        // setup mock location
        $mockLocation = \Mockery::mock('AlexStansfield\GoMeeki\Models\Location');
        $mockLocation->shouldReceive('getLocationId')->once()->andReturn(999);

        // Setup the mock sql statement
        $mockStmt = \Mockery::mock('\Doctrine\DBAL\Driver\Statement');
        $mockStmt->shouldReceive('fetch')->once()->with(\PDO::FETCH_ASSOC)->andReturn($expected);

        // Setup the mock Query Builder
        $mockQB = \Mockery::mock('\Doctrine\DBAL\Query\QueryBuilder');
        $mockQB->shouldReceive('select')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('from')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('where')->once()->andReturn($mockQB);
        $mockQB->shouldReceive('setParameter')->with('locationId', 999)->once()->andReturn($mockQB);
        $mockQB->shouldReceive('execute')->once()->andReturn($mockStmt);

        // Setup the mock Connection
        $this->mockConnection
            ->shouldReceive('createQueryBuilder')
            ->once()
            ->andReturn($mockQB);

        $tweets = new Tweets($this->mockConnection, $this->mockTwitter);
        $this->assertSame($expected, $tweets->getTweets($mockLocation));
    }


}