<?php

namespace AlexStansfield\Gomeeki\Models;

use Doctrine\DBAL\Connection;

/**
 * Class History
 * @package AlexStansfield\Gomeeki\Models
 */
class History
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Add a history record for the user and location
     *
     * @param string $sessionId
     * @param Location $location
     * @return bool
     * @throws \Exception
     */
    public function add($sessionId, Location $location)
    {
        // Setup the data to be inserted
        $data = array('sessionId' => $sessionId, 'locationId' => $location->getLocationId());

        // Insert the history
        try {
            $result = $this->db->insert('history', $data);
        } catch (\Doctrine\DBAL\DBALException $e) {
            // In a larger project we'd have ways to handle these errors properly
            // This try catch is just to show I understand exception handling
            // I will just rethrow the original exception now
            throw $e;
        }

        // If we don't have result of 1 row then something went wrong that didn't throw an exception
        if ($result != 1) {
            throw new \Exception('Failed to create history for session ID "' . $sessionId . '"');
        }

        return true;
    }

    /**
     * Fetch the distinct history in most recent search order
     *
     * @param $sessionId
     * @return array
     */
    public function fetch($sessionId)
    {
        // Create query to find the distinct history
        $query = $this->db->createQueryBuilder();
        $query->select('l.name')
            ->from('location', 'l')
            ->join('l', 'history', 'h', 'l.locationId = h.locationId')
            ->where('h.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->groupBy('l.name')
            ->orderBy('MAX(h.timestamp)', 'DESC');

        // Execute the query
        $stmt = $query->execute();

        // Fetch the rows
        $history = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $history;
    }
}
