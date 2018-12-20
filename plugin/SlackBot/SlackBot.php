<?php

global $global, $config;
if (!isset($global['systemRootPath'])) {
    require_once '../videos/configuration.php';
}
require_once $global['systemRootPath'] . 'plugin/Plugin.abstract.php';

class SlackBot extends PluginAbstract
{

    public function getDescription()
    {
        return "Send video upload notifications to Users on Slack who have subscribed to the channel";
    }

    public function getName()
    {
        return "SlackBot";
    }

    public function getUUID()
    {
        return "cf145581-7d5e-4bb6-8c13-848a19j1564a";
    }
    public function getTags()
    {
        return array(
            'free',
            'notifications',
            'bot'
        );
    }
    public function getPluginVersion()
    {
        return "1.0";
    }
    public function getEmptyDataObject()
    {
        global $global;
        $server = parse_url($global['webSiteRootURL']);

        $obj              = new stdClass();
        $obj->bot_user_oauth_access_token = "";
        $obj->channel_id = "";
        return $obj;
    }
    public function afterNewVideo($videos_id)
    {
        global $global;
        $o                = $this->getDataObject();
        $users_id         = Video::getOwner($videos_id);
        $user             = new User($users_id);
        $username         = $user->getNameIdentification();
        $channelName      = $user->getChannelName();
        $video            = new Video("", "", $videos_id);
        $videoName        = $video->getTitle();
        $images           = Video::getImageFromFilename($video->getFilename());
        $videoThumbs      = $images->thumbsJpg;
        $videoLink        = Video::getPermaLink($videos_id);
        $videoDuration    = $video->getDuration();
        $videoDescription = $video->getDescription();
        $token            = $o->bot_user_oauth_access_token;
        $slackChannel     = $o->channel_id;
        $message          = array(
            'payload' => json_encode(array(
                'text' => $username . " just uploaded a video\nVideo Name: " . $videoName . "\nVideo Link: " . $videoLink . "\nVideo Duration: " . $videoDuration,
                'channel' => $slackChannel
            ))
        );
        $headers = array(
            'Content-type: application/json',
            'Accept-Charset: UTF-8',
            'Authorization: Bearer ' . $token,
        );
        $c                = curl_init('https://slack.com/api/chat.postMessage');
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($c, CURLOPT_POSTFIELDS, $message);
        curl_exec($c);
        curl_close($c);
    }
}