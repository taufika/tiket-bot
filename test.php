<?php

$url = "http://www.tiket.com/kereta-api/cari?d=BD&a=BJR&date=2016-11-10&ret_date=&adult=1&infant=0";

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

// get al row
foreach($dom->getElementsbyTagName('tr') as $node){

    $tr[] = $node;
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
    $kelas = trim($textVal[20]);

    if( isset($textVal[26]) ){

        $remark = trim($textVal[26]);
        $url = $node->childNodes->item(12)->childNodes->item(1)->childNodes->item(1)->attributes->item(0)->value;

        echo strtolower($kelas)."<br>";

    }


}

?>