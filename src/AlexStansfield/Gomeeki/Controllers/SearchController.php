<?php

namespace AlexStansfield\Gomeeki\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use AlexStansfield\Gomeeki\Models\Location;
use AlexStansfield\Gomeeki\Models\Tweets;

class SearchController
{
    public function indexAction(Application $app)
    {
        return $app['twig']->render('search.twig');
    }

    public function searchAction(Application $app, $locationName)
    {
        // Find location by name
        if (! $location = Location::findByName($locationName, $app['db'])) {
            try {
                $geocode = $app['geocoder']->geocode($locationName);
                $location = Location::create(
                    $locationName,
                    $geocode->getLatitude(),
                    $geocode->getLongitude(),
                    $app['db']
                );
            } catch (\Exception $e) {
                return $app['twig']->render(
                    'search.twig',
                    array('error' => 'Location "' . $locationName . '" not found')
                );
            }
        }

        // Add location to user history
        $app['history']->add($app['session']->getId(), $location);

        // Fetch the Tweets
        $tweetsService = new Tweets($app['db'], $app['twitter']);
        if (! $location->getLastTwitterSearch() ||
            $location->getSecondsSinceLastTwitterSearch() >= $app['config']['app']['twitter_refresh']) {
            $rawTweets = $tweetsService->searchTwitter($location, $app['config']['app']['twitter_distance']);
            $tweets = $tweetsService->saveTweets($location, $rawTweets);
        } else {
            $tweets = $tweetsService->getTweets($location);
        }

        return $app['twig']->render('search.twig', array('location' => $location, 'tweets' => $tweets));
    }
}
