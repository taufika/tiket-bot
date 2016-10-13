<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

require_once('./LINEBotTiny.php');

$channelAccessToken = 'wXNwka0cv5nHXaxH8gdAUzE0sLfOqVSV0RaORkWUgdDdXmHn1V2ESqcMwWBH4Mdv+96AqCaewXoBfPJB/sQADtgoi959EjaSoXvFqeMGtBnMLLXJyJVEjOCpNgYQbvNQw5OENcRm6wPuPK+LJB0YdgdB04t89/1O/w1cDnyilFU=';
$channelSecret = 'ceddb49f9818734f7da2c6cebf522694';

$client = new LINEBotTiny($channelAccessToken, $channelSecret);
foreach ($client->parseEvents() as $event) {
    switch ($event['type']) {

        case 'message':
        
            $message = $event['message'];
            $source = $event['source'];

            switch ($message['type']) {

                case 'text':

                    $theMessage = $message;

                    $client->replyMessage(array(
                        'replyToken' => $event['replyToken'],
                        'messages' => array(
                            array(
                                'type' => 'text',
                                'text' => processMessage($theMessage, $source)
                            )
                        )
                    ));
                    break;

                default:
                    error_log("Unsupporeted message type: " . $message['type']);
                    break;
            }
            break;
        case 'follow':
            $client->replyMessage(array(
                'replyToken' => $event['replyToken'],
                'messages' => array(
                    array(
                        'type' => 'text',
                        'text' => 'Halo, terima kasih telah memfollow Bot tiket kereta api. Kamu dapat memanggilku dengan nama @tibot. Ketikkan @tibot untuk memulai proses pencarian tiket. Untuk memunculkan daftar perintah, dapat dengan mengirimkan "help". Kamu juga dapat menambahkan aku ke grup dengan teman-temanmu!'
                    )
                )
            ));
            break;

        case 'join':
            $client->replyMessage(array(
                'replyToken' => $event['replyToken'],
                'messages' => array(
                    array(
                        'type' => 'text',
                        'text' => 'Halo, terima kasih telah menambahkan Bot tiket kereta api ke dalam grup ini. Kamu dapat memanggilku dengan nama @tibot. Ketikkan @tibot untuk memulai proses pencarian tiket. Untuk memunculkan daftar perintah, dapat dengan mengirimkan "@tibot help".'
                    )
                )
            ));
            break;

        default:
            error_log("Unsupporeted event type: " . $event['type']);
            break;
    }
};

function processMessage($message, $source){

    // check greeting
    if( stripos($message['text'], "@tibot") !== false ){

        $sourceType = $source['type'];
        
        if($sourceType === "user"){

            $response = $client->getProfile( $source['userId'] );

            return $response;
        } else {
            return "heyho! " . $sourceType;
        }
    }

    return $message['text'];
}
