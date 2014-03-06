<?php

namespace AlexStansfield\Gomeeki\Models;

use Doctrine\DBAL\Connection;
use Endroid\Twitter\Twitter;

/**
 * Class Tweets
 * @package AlexStansfield\Gomeeki\Models
 */
class Tweets
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var Twitter
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
     * @param string $distance
     * @param int $count
     * @return array
     * @throws \Exception
     */
    public function searchTwitter(Location $location, $distance = '50km', $count = 100)
    {
        // Build the search params
        $params = array(
            'q' => '"' . $location->getName() . '" OR #' . str_replace(' ', '', $location->getName()),
            'geocode' => $location->getLatitude() . ',' . $location->getLongitude() . ',' . $distance,
            'count' => $count
        );

        // Search for the tweets
        $response = $this->twitter->query('search/tweets', 'GET', 'json', $params);

        // Check Response is valid
        if (! ($response && $response->isSuccessful())) {
            throw new \Exception('Error getting the search results from Twitter');
        }

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

    /**
     * Get the Tweets from the database
     *
     * @param Location $location
     * @return array
     */
    public function getTweets(Location $location)
    {
        // Create query to find the location
        $query = $this->db->createQueryBuilder();
        $query->select('t.tweetId, t.locationId, t.user, t.profileImageUrl, t.content, t.latitude, t.longitude, t.posted')
            ->from('tweet', 't')
            ->where('t.locationId = :locationId')
            ->setParameter('locationId', $location->getLocationId());

        // Execute the query
        $stmt = $query->execute();

        // Fetch the result as an associative array
        $tweets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $tweets;
    }

    /**
     * Save the tweets to the database
     *
     * @param Location $location
     * @param array $tweets
     * @return bool
     */
    public function saveTweets(Location $location, array $tweets)
    {
        // Parse the raw tweet data to get insertable data
        $data = $this->parseTweets($location, $tweets);

        // Delete existing tweets for this location
        $this->db->delete('tweet', array('locationId' => $location->getLocationId()));

        // Insert Tweets - Doctrine DBAL doesn't support multiple row insert :(
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
     * @param Location $location
     * @param array $tweets
     * @return array
     */
    protected function parseTweets(Location $location, array $tweets)
    {
        $data = array();
        foreach ($tweets as $tweet) {
            $tmpTweet = array();
            $tmpTweet['locationId'] = $location->getLocationId();
            $tmpTweet['user'] = $tweet['user']['screen_name'];
            $tmpTweet['profileImageUrl'] = $tweet['user']['profile_image_url'];
            $tmpTweet['content'] = $tweet['text'];
            $tmpTweet['latitude'] = $tweet['coordinates']['coordinates'][1];
            $tmpTweet['longitude'] = $tweet['coordinates']['coordinates'][0];
            $tmpTweet['posted'] = date('Y-m-d H:i:s', strtotime($tweet['created_at']));
            $data[] = $tmpTweet;
        }

        return $data;
    }
}
