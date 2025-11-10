?php
namespace SIM\SIGNAL;
use SIM;

add_filter('sim-signal-daemon-response', __NAMESPACE__.'\addResponse', 10, 6);
function addResponse($response, $message, $source, $users, $name, $signal){
    if($response['message'] != 'I have no clue, do you know?'){
        return $response;
    }

    $lowerMessage = strtolower($message);
    
    if(str_starts_with($lowerMessage, 'update profile picture')){
        $response['message']    = checkPrayerRequestToUpdate($message, $users, $signal);
    }

    return $response;
}
