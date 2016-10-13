<?php

$kelas = "apapun";
$kotaAsal = "Bandung";
$kotaTujuan = "Banjar";
$tanggal = "10/11/2016";
$jumlah = 1;

$kereta = [];

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
            'url' => $url
        ));
    }
}

$cards = [];

// iterate kereta
foreach($kereta as $el){

    if($kelas == "apapun" || strtolower($kelas) == strtolower($el['class']) ){

        array_push($cards, array(
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
        ));

    }
}

$ret = array(
    'greeting' => "Menampilkan hasil pencarian tiket dari " . $kotaAsal . " ke " . $kotaTujuan . " pada tanggal " . $tanggal . " untuk " . $jumlah . " orang dengan kelas " . $kelas,
    'list' => $cards
);

print_r($ret);

?>