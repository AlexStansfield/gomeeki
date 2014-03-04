<?php

namespace AlexStansfield\Gomeeki\Models;

use AlexStansfield\Gomeeki\Models\Location;
use Doctrine\DBAL\Connection;
use Endroid\Twitter\Twitter;

class Tweets
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $db;

    /**
     * @var \Endroid\Twitter\Twitter
     */
    protected $twitter;

    /**
     * @param Connection $db
     * @param Twitter $twitter
     */
    public function __construct(Connection $db, Twitter $twitter)
    {
        $this->db = $db;
        $this->twitter = $twitter;
    }

    /**
     * Search Twitter for the given Location
     *
     * @param Location $location
     * @return mixed
     */
    public function searchTwitter(Location $location)
    {
        // Build the search params
        $params = array(
            'q' => $location->getName(),
            'geocode' => $location->getLatitude() . ',' . $location->getLongitude() . ',50km',
            'count' => 100
        );

        // Search for the tweets
        $response = $this->twitter->query('search/tweets', 'GET', 'json', $params);

        // todo check response

        // Decode the json into an associative array
        $results = json_decode($response->getContent(), true);

        // Find the tweets we want (those with location data)
        $tweets = array();
        foreach ($results['statuses'] as $tweet) {
            if (isset($tweet['coordinates']) && !is_null($tweet['coordinates'])) {
                $tweets[] = $tweet;
            }
        }

        return $tweets;
    }
}