<?php

include_once('subsonic.php');
include_once('config.php');

$sub = new Subsonic;

$sub->setHost($conf['subSonicUrl']);
$sub->setUser($conf['subSonicUsername']);
$sub->setPass($conf['subSonicPassword']);

function streamSong($session, $song)
{
    sendMessage([
        'source' => 'subsonic',
        'fulfillmentText' => 'Playing ' . $song['title'],
        'payload' => [
            'google' => [
                'expectUserResponse' => true,
                'richResponse' => [
                    'items' => [
                        [
                            'simpleResponse' => [
                                'textToSpeech' => ' ',
                                ],
                        ],
                        [
                            'mediaResponse' => [
                                'mediaType' => 'AUDIO',
                                'mediaObjects' => [
                                    [
                                        'name' => $song['title'],
                                        'description' => $song['title'] . ' by ' . $song['artist'],
                                        'largeImage' => [
                                            'url' => $song['coverUrl'],
                                            'accessibilityText' => 'Album cover of ' . $song['album'] . ' by ' . $song['artist'],
                                        ],
                                        'contentUrl' => $song['url'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'suggestions' => [
                        [
                            'title' => $song['album'] . ' di ' . $song['artist'],
                        ],
                    ],
                ],
            ],
        ],
        'outputContexts' => [
            [
                'name' => $session . '/contexts/playing',
                'lifespanCount' => 5,
                'parameters' => [
                    "id" => $song['id'],
                ],
            ],
        ],
    ]);
}

function searchBySongArtist($session, $params)
{
    global $sub;
    if(!isset($params['song']) || !isset($params['artist']))
    {
        sendMessage([
            'source' => 'subsonic',
            'fulfillmentText' => 'Request problem: missing mandatory parameter.',
        ]);
        return;
    }

    $ret = $sub->apiSearch($params['artist'], $params['song']);
    switch(count($ret))
    {
        case 0:
            sendMessage([
                'source' => 'subsonic',
                'fulfillmentText' => 'Sorry, but I found no song.',
            ]);
            break;
        default:
            streamSong($session, $ret[0]);
            break;
    }
}

function nextSong($session, $params)
{
    global $sub;
    if(!isset($params['id']))
    {
        sendMessage([
            'source' => 'subsonic',
            'fulfillmentText' => 'Request problem: missing mandatory parameter.',
        ]);
        return;
    }

    $nextSong = $sub->findNextSong($params['id']);
    if($nextSong === null)
    {
        sendMessage([
            'source' => 'subsonic',
            'fulfillmentText' => 'I\'m sorry, i ran out of songs.',
        ]);
    }

    streamSong($session, $nextSong);
}

function processMessage($request)
{
    if (!isset($request['queryResult']['action']))
    {
        sendMessage([
            'source' => 'subsonic',
            'fulfillmentText' => 'Request problem: missing action.',
        ]);
        return;
    }

    if(!isset($request['session']))
    {
        sendMessage([
            'source' => 'subsonic',
            'fulfillmentText' => 'Request problem: missing session.',
        ]);
        return;
    }

    switch($request['queryResult']['action'])
    {
        case 'search':
            searchBySongArtist($request['session'], $request['queryResult']['parameters']);
            return;
        case 'next':
            nextSong($request['session'], $request['queryResult']['parameters']);
            return;
        default:
            sendMessage([
                'source' => 'subsonic',
                'fulfillmentText' => 'I\'m sorry, that command is not implemented yet.',
            ]);
            return;
    }
}

function sendMessage($parameters)
{
    header('Content-Type: application/json');
    $txt = json_encode($parameters);
    echo $txt;
}


$input = file_get_contents('php://input');
$request = json_decode($input, true);

processMessage($request);
