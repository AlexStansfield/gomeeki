<?php

namespace AlexStansfield\Gomeeki\Models;

use \Doctrine\DBAL\Connection;
use \Endroid\Twitter\Twitter;

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
     * @param \Doctrine\DBAL\Connection $db
     * @param \Endroid\Twitter\Twitter $twitter
     */
    public function __construct(Connection $db, Twitter $twitter)
    {
        $this->db = $db;
        $this->twitter = $twitter;
    }

    /**
     * Search Twitter for the given Location
     *
     * @param \AlexStansfield\Gomeeki\Models\Location $location
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

    public function getTweets(Location $location)
    {
        // Create query to find the location
        $query = $this->db->createQueryBuilder();
        $query->select('t.*')
            ->from('tweet', 't')
            ->where('t.locationId = :locationId')
            ->setParameter('locationId', $location->getLocationId());

        // Execute the query
        $stmt = $query->execute();

        // Fetch the result as an associative array
        $tweets = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $tweets;
    }

    /**
     * Save the tweets to the database
     *
     * @param \AlexStansfield\Gomeeki\Models\Location $location
     * @param array $tweets
     * @return bool
     */
    public function saveTweets(Location $location, array $tweets)
    {
        // Parse the raw tweet data to get insertable data
        $data = $this->parseTweets($location, $tweets);

        // Delete existing tweets for this location
        $this->db->delete('tweet', array('locationId' => $location->getLocationId()));

        // Insert Tweets
        foreach ($data as $row) {
            $this->db->insert('tweet', $row);
        }

        // Update location last search
        $location->updateTwitterSearch();

        return $data;
    }

    /**
     * Parse the raw tweets and return the data ready for the database
     *
     * @param \AlexStansfield\Gomeeki\Models\Location $location
     * @param array $tweets
     * @return array
     */
    protected function parseTweets(Location $location, array $tweets)
    {
        $data = array();
        foreach ($tweets as $tweet){
            $tmpTweet = array();
            $tmpTweet['locationId'] = $location->getLocationId();
            $tmpTweet['user'] = $tweet['user']['screen_name'];
            $tmpTweet['profileImageUrl'] = $tweet['user']['profile_image_url'];
            $tmpTweet['content'] = $tweet['text'];
            $tmpTweet['latitude'] = $tweet['coordinates']['coordinates'][0];
            $tmpTweet['longitude'] = $tweet['coordinates']['coordinates'][1];
            $tmpTweet['posted'] = date('Y-m-d H:i:s', strtotime($tweet['created_at']));
            $data[] = $tmpTweet;
        }

        return $data;
    }
}