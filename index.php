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
require_once('./firebaseLib.php');

// firebase!!!!!!!!!!!!!!!!!!!!
// firebase token etc
$url = "https://cari-tiket-kereta.firebaseio.com/";
$token = "AIzaSyDzwbCWu4-wxcVRezuFB8omsjsP1UQUAHA";

// init firebase
$firebase = new \Firebase\FirebaseLib($url,$token);

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

                    $theMessage = processMessage($message, $source);
                    if( is_string($theMessage) && $theMessage !== ""){

                        $client->replyMessage(array(
                            'replyToken' => $event['replyToken'],
                            'messages' => array(
                                array(
                                    'type' => 'text',
                                    'text' => $theMessage,
                                )
                            )
                        ));
                    } else if ( !is_string($theMessage) ){

                        // if return is object
                        $client->replyMessage(array(
                            'replyToken' => $event['replyToken'],
                            'messages' => array(
                                array(
                                    'type' => 'text',
                                    'text' => $theMessage['greeting'],
                                ),

                                array(
                                    'type' => 'template',
                                    'altText' => 'List tiket',
                                    'template' => array(
                                        
                                        'type' => 'carousel',
                                        'columns' => $theMessage['list']
                                    )
                                )
                            )
                        ));
                    }
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
                        'text' => 'Halo, terima kasih telah memfollow Bot tiket kereta api. Kamu dapat memanggilku dengan nama @tibot. Ketikkan @tibot untuk memulai proses pencarian tiket. Untuk memunculkan daftar perintah, dapat dengan mengirimkan "@tibot help". Kamu juga dapat menambahkan aku ke grup dengan teman-temanmu!'
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

    // detect chat type
    $sourceType = $source['type'];

    if($sourceType === "user"){
        $fileName = $source['userId'];
    } else if ($sourceType === "room"){
        $fileName = $source['roomId'];
    } else if ($sourceType === "group"){
        $fileName = $source['groupId'];
    }

    // check greeting
    if( stripos($message['text'], "@tibot") !== false ){

        // if asking for help
        if( stripos($message['text'], "help") !== false ){

            return "Untuk memesan tiket, mention @tibot dalam pesanmu setelah itu @tibot akan membalas. Balas @tibot dengan pesan \r\n\r\n " .
                    "Pesan tiket dari <nama_kota> ke <nama_kota> pada tanggal <dd/mm/yy> untuk <n> orang dengan kelas <eksekutif/bisnis/ekonomi> \r\n" .
                    "\r\n Opsi <nama_kota> dan tanggal <dd/mm/yy> adalah wajib. Jika jumlah orang tidak diisi, diasumsikan satu orang. Jika, kelas tidak diisi, akan ditampilkan seluruh kelas \r\n" .
                    "\r\nContoh: Pesan tiket dari bandung ke surabaya pada tanggal 20/12/2016 untuk 2 orang dengan kelas bisnis\r\n\r\n" .
                    "Untuk melihat daftar kota yang dapat dilakukan pencarian, kirimkan '@tibot list'";

        } else if( stripos($message['text'], "list") !== false ){

            $text = "Berikut daftar kota yang dapat dilakukan pencarian: ";

            // get kota
            $kota = $firebase->get("/stasiun");

            $i = 1;
            foreach($kota as $key => $val){
                $text .= "\r\n ".$i." ".$key;
                $i++;
            }

            return $text;
        }{

            file_put_contents($fileName, "1");
            return "Halo, mau mencari tiket? Jika iya silahkan masukkan sintaks";
        }

    } else if(file_exists($fileName) == 1  ){

        // detect session
        $respArr = explode(" ", strtolower($message['text']));

        // check if real order message
        if( $respArr[0] == "pesan" && $respArr[1] == "tiket"){

            $messageLower = strtolower($message['text']);

            $kotaAsal = explode(" ", explode("dari ", $messageLower)[1])[0];
            $kotaTujuan = explode(" ", explode(" ke ", $messageLower)[1])[0];
            $tanggal = explode(" ", explode("pada tanggal ", $messageLower)[1])[0];

            $jumlah = 1;
            if( stripos($messageLower, " untuk ") !== false){
                $jumlah = explode(" ", explode(" untuk ", $messageLower)[1])[0];
            }

            $kelas = "apapun";
            if( stripos($messageLower, " dengan kelas ") !== false){
                $kelas = explode(" dengan kelas ", $messageLower)[1];
            }
        
            unlink($fileName);

            

            $ret = array(
                'greeting' => "Menampilkan hasil pencarian tiket dari " . $kotaAsal . " ke " . $kotaTujuan . " pada tanggal " . $tanggal . " untuk " . $jumlah . " orang dengan kelas " . $kelas,
                'list' => array(
                        array(
                            'thumbnailImageUrl' => 'https://devdocs.line.me/images/carousel.png',
                            'title' => 'testing title',
                            'text' => 'testing description',
                            'actions' => array(
                                array(
                                    'type' => 'uri',
                                    'label' => 'Beli via Tiket.com',
                                    'uri' => 'http://www.google.com'
                                )
                            )
                        ),
                        array(
                            'thumbnailImageUrl' => 'https://devdocs.line.me/images/carousel.png',
                            'title' => 'testing title',
                            'text' => 'testing description',
                            'actions' => array(
                                array(
                                    'type' => 'uri',
                                    'label' => 'Beli via Tiket.com',
                                    'uri' => 'http://www.google.com'
                                )
                            )
                        )
                    )
                );

            return $ret;

        } else {
            return "";
        }

    } else if($source['type'] === "room" || $source['type'] === "group"){
        return "";
    } else {
        return "Silahkan panggil aku terlebih dahulu dengan @tibot atau ketik '@tibot help' untuk bantuan";
    }
}
