<?php
/**
 * @author Solution Fuse <hello@solutionfuse.com>
 */

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require 'vendor/autoload.php';
require_once('SentimentAnalyzer.php');
use \Firebase\JWT\JWT;

// Takes raw data from the request
$json = file_get_contents('php://input');
// Converts it into a PHP object
$data = json_decode($json);

$body = $data->raw;
$secretKey;
$userId;

if(isset($_SERVER['HTTP_BOT_TOKEN'])){
    $secretKey = $_SERVER['HTTP_BOT_TOKEN'];
}


if(isset($_SERVER['HTTP_MACHAAO_USER_ID'])){
    $userId = $_SERVER['HTTP_MACHAAO_USER_ID'];
}

if(!$body || !$userId){
    http_response_code(500);

    echo json_encode(array("message" => "Unauthorized Client Request, Bot is not available at this time, Please contact us at connect@machaao.com."));

    exit;
}

try {
    $decoded = JWT::decode($body, $secretKey, ["HS512"]);

    if(isset($decoded) &&  isset($decoded->sub) && isset($decoded->sub->messaging)){
        $_body = $decoded->sub;

        $messagingEntries = getMessagingEntries($_body);

        if(isset($messagingEntries)){
            foreach($messagingEntries->messaging as $m){
                
                $version = str_replace("v","", $m->version);

                if(isset($version) && $version < 0.585){
                    $_upgradeClient = "Sorry, you are using an older or an invalid version of the client.\nPlease update from Play Store....\nDownload -> https://play.google.com/store/apps/details?id=com.machaao.ganglia.cricket.release";

                    http_response_code(500);
                    echo json_encode(array("message" => $_upgradeClient));
                    exit;
                }

                $message = (isset($m->message_data) && $m->message_data->text) ? $m->message_data->text : "";
                
                $silent = $m->silent;
                $sender = $m->sender;

                $user = getUser($m);

//                My Code
                $sentiment = analyzeText($message);
                sendTextMessage($userId, $sentiment);

//                Existing Code
//                switch ($message) {
//                    case 'generic':
//                        sendGenericMessage($userId);
//                        break;
//                    default:
//                        sendTextMessage($userId, $message);
//                }
    
                echo ("processed message: " . $message  . " from " . $userId);

            }
        }
    }

}catch(Exception $e){
    http_response_code(500);

    echo json_encode(array("message" => "Unauthorized Client Request, Bot is not available at this time, Please contact us at connect@machaao.com."));
}

function analyzeText($sentence = "I love Coding") : string {
    $sent = new SentimentAnalyzerTest(new SentimentAnalyzer());

    $sent->trainAnalyzer('data.neg', 'negative', 5000); //training with negative data
    $sent->trainAnalyzer('data.pos', 'positive', 5000); //trainign with positive data

    $sentimentAnalysis = $sent->analyzeSentence($sentence);

    $result = $sentimentAnalysis['sentiment'];

    return $result;
}

function getMessagingEntries($body){
    if(isset($body->messaging) && is_array($body->messaging) && sizeof($body->messaging) > 0 && $body->messaging){
        return $body;
    }else{
        return null;
    }
}

function getUser($messaging) {
    $val = $messaging->user;
    if(isset($val)){
        return $val;
    }else{
        return null;
    }
}

function sendGenericMessage($userId){
    $messageData = array(
        "identifier" => "BROADCAST_FB_QUICK_REPLIES",
        "users" => [$userId],
        "source" => "firebase",
        "message" => array(
            "attachment" => array(
                "type" => "template",
                "payload" => array(
                    "template_type" => "generic",
                    "elements" => [array(
                        "title" => "rift",
                        "subtitle" => "Next-generation virtual reality",
                        "image_url" => "https://i.imgur.com/nGF1K8f.jpg",
                        "buttons" => [
                            array(
                                "type" => "web_url",
                                "url" => "https://www.oculus.com/en-us/rift/",
                                "title" => "Open Web URL"
                            ),
                            array(
                                "type" => "postback",
                                "url" => "Call Postback",
                                "title" => "Payload for first bubble"
                            )
                        ]
                    ),
                    array(
                        "title" => "touch",
                        "subtitle" => "Your Hands, Now in VR",
                        "image_url" => "https://i.imgur.com/nGF1K8f.jpg",
                        "buttons" => [array(
                            "type" => "web_url",
                            "url" => "https://www.oculus.com/en-us/touch/",
                            "title" => "Open Web URL"
                        ),
                        array(
                            "type" => "postback",
                            "url" => "Call Postback",
                            "title" => "Payload for second bubble"
                        )],
                    )],
                )
            )
        )
    );

    $messageDataJson = json_encode($messageData, true);

    callSendAPI($messageDataJson); 
}

function sendTextMessage($userId, $messageText) {
    $messageData = array(
        "identifier" => 'BROADCAST_FB_QUICK_REPLIES',
        "users" => [$userId],
        "source" => "firebase",
        "message" => array(
            "text" => $messageText
        )
    );

    $messageDataJson = json_encode($messageData, true);

    callSendAPI($messageDataJson);
}

function callSendAPI($messageData){

    $url = "https://ganglia-dev.machaao.com/v1/messages/send";

    $ch = curl_init() or die(curl_error());
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $messageData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',  
        // 'api_token: <-- Please update with your api_token -->'
        'api_token: e4d6d410-cc47-11ea-bb8b-b75d1b27b6b5'
        )                                                                       
    );

    $responseText = curl_exec ($ch);

    if(curl_errno($ch)){
        echo 'Request Error:' . curl_error($ch);
    }

    echo "Successfully sent a message - " . json_encode($responseText); 

    curl_close($ch);
}
