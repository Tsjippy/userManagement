<?php
namespace TSJIPPY\SIGNAL;
use TSJIPPY;

add_filter('tsjippy-signal-daemon-response', __NAMESPACE__.'\addResponse', 10, 6);
function addResponse($response, $message, $source, $users, $name, $signal){
    if($response['message'] != 'I have no clue, do you know?'){
        return $response;
    }

    $lowerMessage = strtolower($message);
    
    if(str_starts_with($lowerMessage, 'update profile picture')){
        $response['message']    = 12;//checkPrayerRequestToUpdate($message, $users, $signal);
    }

    return $response;
}
