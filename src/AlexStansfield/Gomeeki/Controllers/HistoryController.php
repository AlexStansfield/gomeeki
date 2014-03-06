<?php

namespace AlexStansfield\Gomeeki\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class HistoryController
{
    public function indexAction(Request $request, Application $app)
    {
        $history = $app['history']->fetch($app['session']->getId());

        //return $app->json($history);
        return $app['twig']->render('history.twig', array('history' => $history));
    }
}
