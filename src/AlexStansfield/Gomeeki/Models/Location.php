<?php

namespace AlexStansfield\Gomeeki\Models;

use Doctrine\DBAL\Connection;

class Location
{
    /**
     * @var Connection
     */
    protected $db;

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

    public function __construct(Connection $db, array $data = null)
    {
        $this->db = $db;
        $fields = array('name', 'longitude', 'latitude', 'lastTwitterSearch');

        if (! is_null($data)) {
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $this->$field = $data[$field];
                }
            }
        }
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
     * Updates the lastTwitterSearch datetime
     *
     * @return Location
     * @throws \Exception
     */
    public function updateTwitterSearch()
    {
        date_default_timezone_set ('UTC');
        $datetime = date('Y-m-d H:i:s');

        // Update the date time of last twitter search
        try {
            $result = $this->db->update('location',
                array('lastTwitterSearch' => $datetime),
                array('name' => $this->db->quote($this->name))
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
    static public function findByName($name, Connection $db)
    {
        // Create query to find the location
        $query = $db->createQueryBuilder();
        $query->select()
            ->from('location', 'l')
            ->where('l.name = :name')
            ->setParameter('name', $name);

        // Execute the query
        $stmt = $query->execute();

        // Fetch the result as an associative array
        $data = $stmt->fetchAssoc();

        // Throw an exception if not found
        if (! $data) {
            throw new \Exception('Location "' . $name . '" not found');
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
    static public function create($name, $latitude, $longitude, Connection $db)
    {
        $data = array('name' => $name, 'latitude' => $latitude, 'longitude' => $longitude);

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

        // Return the location object
        return new self($db, $data);
    }
}