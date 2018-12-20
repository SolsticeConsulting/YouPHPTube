<?php

global $global, $config;
if (!isset($global['systemRootPath'])) {
    require_once '../videos/configuration.php';
}
require_once $global['systemRootPath'] . 'plugin/Plugin.abstract.php';
require_once $global['systemRootPath'] . 'objects/subscribe.php';

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
        $usersSubscribed  = Subscribe::getAllSubscribes($users_id);
        $usersSubString   = json_encode($usersSubscribed);
        error_log("Users Subscribed: " . $usersSubString);
        error_log("users id " . $users_id);
        $username         = $user->getNameIdentification();
        $channelName      = $user->getChannelName();
        $video            = new Video("", "", $videos_id);
        $videoName        = $video->getTitle();
        error_log("Video Name is: " . $videoName);
        $images           = Video::getImageFromFilename($video->getFilename());
        $videoThumbs      = $images->thumbsJpg;
        $videoLink        = Video::getPermaLink($videos_id);
        $videoDuration    = $video->getDuration();
        $videoDescription = $video->getDescription();
        $token            = $o->bot_user_oauth_access_token;
        $slackChannel     = $o->channel_id;
        $slackIds         = array();
        $emails           = array();

        //For each user email, get the slack id
        foreach ($usersSubscribed as $subscribedUser) {
            error_log("subscribed user: " . $subscribedUser["email"]);
            $emails[] = $subscribedUser["email"];
            // $headers = array(
            //     'Content-type: application/json',
            //     'Accept-Charset: UTF-8',
            //     'Authorization: Bearer ' . $token,
            // );
            // $c            = curl_init('https://slack.com/api/users.lookupByEmail?email=' . $subscribedUser->$email);
            // curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
            // curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
            // curl_setopt($c, CURLOPT_POSTFIELDS, $message);
            // curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            // $result = curl_exec($c);
            // $userSlackInformation = json_decode($result);
            // $slackIds[] = $userSlackInformation->$user->$id;
            // curl_close($c);
        }
        error_log("user emails " . json_encode($emails));
        error_log("user slack ids " . json_encode($slackIds));

        $paylod->text     = $username . " just uploaded a video\nVideo Name: " . $videoName . "\nVideo Link: " . $videoLink . "\nVideo Duration: " . $videoDuration . "\nSubscribers: " . json_encode($usersSubscribed);
        $paylod->channel  = $slackChannel;
        $message          = json_encode($paylod);
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