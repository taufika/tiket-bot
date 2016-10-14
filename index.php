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

require_once('LINEBotTiny.php');
require_once('firebaseLib.php');

// $url = "https://cari-tiket-kereta.firebaseio.com";
// $token = "";

// $firebase = new \Firebase\FirebaseLib($url,$token);
// $kota = $firebase->get("stasiun/bandung");
// print_r($kota);


$channelAccessToken = 'wXNwka0cv5nHXaxH8gdAUzE0sLfOqVSV0RaORkWUgdDdXmHn1V2ESqcMwWBH4Mdv+96AqCaewXoBfPJB/sQADtgoi959EjaSoXvFqeMGtBnMLLXJyJVEjOCpNgYQbvNQw5OENcRm6wPuPK+LJB0YdgdB04t89/1O/w1cDnyilFU=';
$channelSecret = 'ceddb49f9818734f7da2c6cebf522694';

$client = new LINEBotTiny($channelAccessToken, $channelSecret);
foreach ($client->parseEvents() as $event) {
    switch ($event['type']) {

        case 'message':
        
            $message = $event['message'];
            $source = $event['source'];
            $sourceType = $source['type'];

            if($sourceType === "user"){
                $to = $source['userId'];
            } else if ($sourceType === "room"){
                $to = $source['roomId'];
            } else if ($sourceType === "group"){
                $to = $source['groupId'];
            }

            switch ($message['type']) {

                case 'text':

                    $theMessage = processMessage($message, $source);
                    if( is_string($theMessage) && $theMessage !== ""){

                        if( explode(" ", $theMessage)[0] == "Maaf,") {

                            $client->pushMessage(array(
                                'to' => $to,
                                'messages' => array(
                                    array(
                                        'type' => 'text',
                                        'text' => $theMessage,
                                    )
                                )
                            ));
                        } else {

                            $client->replyMessage(array(
                                'replyToken' => $event['replyToken'],
                                'messages' => array(
                                    array(
                                        'type' => 'text',
                                        'text' => $theMessage,
                                    )
                                )
                            ));
                        }
                    } else if ( !is_string($theMessage) ){

                        // if return is object
                        $client->pushMessage(array(
                            'to' => $to,
                            'messages' => array(
                                array(
                                    'type' => 'text',
                                    'text' => $theMessage['greeting'],
                                ),

                                array(
                                    'type' => 'template',
                                    'altText' => 'List tiket. Untuk lebih lengkap kunjungi http://cari-tiket-kereta.firebaseapp.com',
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
                    error_log("Unsupported message type: " . $message['type']);
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
            // firebase!!!!!!!!!!!!!!!!!!!!
            // firebase token etc
            $url = "https://cari-tiket-kereta.firebaseio.com";
            $token = "";

            $firebase = new \Firebase\FirebaseLib($url,$token);
            $kota = $firebase->get("stasiun");

            $i = 1;
            foreach(json_decode($kota, true) as $key => $val){
                $text .= "\r\n ".$i.". ".$key;
                $i++;
            }

            return $text;
        } else {

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
            $tanggal = str_replace("/","-", $tanggal);

            $jumlah = 1;
            if( stripos($messageLower, " untuk ") !== false){
                $jumlah = explode(" ", explode(" untuk ", $messageLower)[1])[0];
            }

            $kelas = "apapun";
            if( stripos($messageLower, " dengan kelas ") !== false){
                $kelas = explode(" dengan kelas ", $messageLower)[1];
            }
        
            unlink($fileName);

            // firebase!!!!!!!!!!!!!!!!!!!!
            // firebase token etc
            $url = "https://cari-tiket-kereta.firebaseio.com";
            $token = "";

            // init firebase
            $firebase = new \Firebase\FirebaseLib($url,$token);
            $kotaAsalArr = json_decode($firebase->get("stasiun/" . $kotaAsal), true);
            $kotaTujuanArr = json_decode($firebase->get("stasiun/" . $kotaTujuan), true);

            if ($kotaAsalArr != null && $kotaTujuanArr !== null){

                $kereta = [];

                foreach($kotaAsalArr as $k_a){

                    foreach($kotaTujuanArr as $k_t){

                        $k_a = explode("#",$k_a)[0];
                        $k_t = explode("#",$k_t)[0];
                        $tanggal = date("Y-m-d", strtotime($tanggal));

                        $url = "http://www.tiket.com/kereta-api/cari?d=" . $k_a . "&a=" . $k_t . "&date=" . $tanggal . "&ret_date=&adult=" . $jumlah . "&infant=0";

                        //print($url);

                        $ch = curl_init();

                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_HEADER, 0);

                        // Should cURL return or print out the data? (true = return, false = print)
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        $output = curl_exec($ch);
                        curl_close($ch);

                        $cutOutput = explode("</tbody", explode('<tbody id="tbody_depart">', $output)[1] )[0];

                        // error_log($cutOutput);

                        $dom = new DOMDocument('1.0', 'UTF-8');
                        $internalErrors = libxml_use_internal_errors(true);
                        $dom->loadHTML($cutOutput);

                        $tr = [];

                        // get al row
                        foreach($dom->getElementsbyTagName('tr') as $node){

                            array_push($tr, $node);
                        }

                        // get all td
                        foreach($tr as $node){
                            
                            $textVal = explode("\n", $node->textContent);
                            $nama = trim($textVal[1]);
                            $subClass = trim($textVal[2]);
                            $waktuBerangkat = trim($textVal[5]);
                            $stasiunBerangkat = trim($textVal[6]);
                            $durasi = trim($textVal[14]);
                            $waktuSampai = trim($textVal[9]);
                            $stasiunSampai = trim($textVal[10]);
                            $harga = trim($textVal[17]);
                            $realHarga = (int) str_replace(".", "", explode("IDR ", $harga)[1]);
                            $class = trim($textVal[20]);

                            if( isset($textVal[26]) ){

                                $remark = trim($textVal[26]);
                                $url = $node->childNodes->item(12)->childNodes->item(1)->childNodes->item(1)->attributes->item(0)->value;

                                // echo strtolower($class)."<br>";
                                array_push($kereta, array(
                                    'nama' => $nama,
                                    'subClass' => $subClass,
                                    'class' => $class,
                                    'waktuBerangkat' => $waktuBerangkat,
                                    'stasiunBerangkat' => $stasiunBerangkat,
                                    'waktuSampai' => $waktuSampai,
                                    'stasiunSampai' => $stasiunSampai,
                                    'durasi' => $durasi,
                                    'harga' => $harga,
                                    'realHarga' => $realHarga,
                                    'url' => $url
                                ));
                            }
                        }
                    }
                }

                usort($kereta, function($item1, $item2){
                    if($item1['realHarga'] == $item2['realHarga']) return 0;
                    return $item1['realHarga'] < $item2['realHarga'] ? 1 : -1;
                });

                $ret = array(
                    'greeting' => "Menampilkan 5 hasil pencarian tiket dari " . $kotaAsal . " ke " . $kotaTujuan . " pada tanggal " . $tanggal . " untuk " . $jumlah . " orang dengan kelas " . $kelas,
                    'list' => array()
                );

                // iterate kereta
                if(sizeof($kereta) > 0){

                    $max = min( array(5, sizeof($kereta)) );

                    for($i = 0; $i < $max; $i++){

                        if($kelas == "apapun" || strtolower($kelas) == strtolower($el['class']) ){

                            $title = $kereta[$i]['nama'] . " \r\n- " . $kereta[$i]['class'];
                            $text = $kereta[$i]['stasiunBerangkat'] . " (" . $kereta[$i]['waktuBerangkat'] . ") ke " . $kereta[$i]['stasiunSampai'] . " (" . str_replace(" (+1 Hari)","",$kereta[$i]['waktuSampai']) . ")";
                            $beli = 'Beli (' . $kereta[$i]['harga'] . ')';
                            $uri = $kereta[$i]['url'];

                            $isi = 
                            array(
                                'title' => $title,
                                'text' => $text,
                                'actions' => array(
                                    array(
                                        'type' => 'uri',
                                        'label' => $beli,
                                        'uri' => $uri
                                    )
                                )
                            );

                            array_push($ret['list'], $isi);

                        }
                    }

                    // if filter return 0
                    if( sizeof($ret['list']) > 0 ){
                        return $ret;
                    } else {
                        return "Maaf, tidak ada kereta untuk perjalanan yang kamu inginkan. Coba cari pada tanggal atau rute yang berbeda.";
                    }
                } else {
                    return "Maaf, tidak ada kereta untuk perjalanan yang kamu inginkan. Coba cari pada tanggal atau rute yang berbeda.";
                }

            } else {

                return "Ups, salah satu kota yang dicantumkan tidak terdaftar! Untuk melihat daftar kota yang terdaftar kirimkan '@tibot list'";
            }

        } else {
            return "";
        }

    } else if($source['type'] === "room" || $source['type'] === "group"){
        return "";
    } else {
        return "Silahkan panggil aku terlebih dahulu dengan @tibot atau ketik '@tibot help' untuk bantuan";
    }
}
