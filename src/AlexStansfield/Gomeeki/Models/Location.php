<?php

namespace AlexStansfield\Gomeeki\Models;

use Doctrine\DBAL\Connection;

/**
 * Class Location
 * @package AlexStansfield\Gomeeki\Models
 */
class Location
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var integer
     */
    protected $locationId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var float
     */
    protected $latitude;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * @var string
     */
    protected $lastTwitterSearch;

    /**
     * @var array
     */
    protected $fields = array('locationId', 'name', 'longitude', 'latitude', 'lastTwitterSearch');

    /**
     * @param Connection $db
     * @param array $data
     */
    public function __construct(Connection $db, array $data)
    {
        $this->db = $db;

        // Take the data and put it into the properties
        foreach ($this->fields as $field) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }
    }

    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * Get's the name of the location (i.e what the user searched for)
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the latitude of the location
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Get the longitude of the location
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Get the datetime of the last search for tweets at the location
     *
     * @return string
     */
    public function getLastTwitterSearch()
    {
        return $this->lastTwitterSearch;
    }

    /**
     * Calculate the number of seconds since the last twitter search
     *
     * @return int
     */
    public function getSecondsSinceLastTwitterSearch()
    {
        $lastUpdate = strtotime($this->lastTwitterSearch);

        return time() - $lastUpdate;
    }

    /**
     * Updates the lastTwitterSearch datetime
     *
     * @return Location
     * @throws \Exception
     */
    public function updateTwitterSearch()
    {
        // Get the current time stamp in sql datetime format
        $datetime = date('Y-m-d H:i:s');

        // Update the date time of last twitter search
        try {
            $result = $this->db->update(
                'location',
                array('lastTwitterSearch' => $datetime),
                array('locationId' => $this->locationId)
            );
        } catch (\Doctrine\DBAL\DBALException $e) {
            $result = false;
        }

        if (! $result) {
            throw new \Exception('Failed to update location "' . $this->name . '"');
        }

        $this->lastTwitterSearch = $datetime;

        return $this;
    }

    /**
     * Static method for finding a Location by the name
     *
     * @param string $name
     * @param Connection $db
     * @return Location
     * @throws \Exception
     */
    public static function findByName($name, Connection $db)
    {
        // To make sure we don't get duplicate locations when people use or don't use capitals
        $name = strtolower($name);

        // Create query to find the location
        $query = $db->createQueryBuilder();
        $query->select('l.*')
            ->from('location', 'l')
            ->where('l.name = :name')
            ->setParameter('name', $name);

        // Execute the query
        $stmt = $query->execute();

        // Fetch the result as an associative array
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return null if not found
        if (! $data) {
            return;
        }

        // Return the location
        return new self($db, $data);
    }

    /**
     * Create a Location from name, latitude and longitude
     * Will insert it into the DB and then return the instantiated object
     *
     * @param string $name
     * @param string $latitude
     * @param string $longitude
     * @param Connection $db
     * @return Location
     * @throws \Exception
     */
    public static function create($name, $latitude, $longitude, Connection $db)
    {
        // Setup the data to insert, change name to lower case to help avoid dupes
        $data = array('name' => strtolower($name), 'latitude' => $latitude, 'longitude' => $longitude);

        // Insert the location
        try {
            $result = $db->insert('location', $data);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $result = false;
        }

        // If we don't have result of 1 row then something went wrong
        if ($result != 1) {
            throw new \Exception('Failed to create location "' . $name . '"');
        }

        // Get the Primary Key
        $data['locationId'] = $db->lastInsertId();

        // Return the location object
        return new self($db, $data);
    }
}
