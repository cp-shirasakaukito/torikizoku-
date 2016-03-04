<?php
/**
 * Created by PhpStorm.
 * User: ukito
 * Date: 16/01/02
 * Time: 0:22
 */

$urlshortener = "https://www.googleapis.com/urlshortener/v1/url?key=";
$key_ip = "AIzaSyAjgDegr9NlRZ1o-Ldhr2V3xBHQK8MZFBU";
$key_ref = "AIzaSyC9tMuVmJFLNu0g3OI1crLPZEwE7ViB11k";
$key = $key_ip;

$long_url["longUrl"] = "https://maps.googleapis.com/maps/api/staticmap?size=500x400&markers=color:gray%7Clabel:A%7C東京都新宿区新宿4丁目1－13－9F+&markers=color:gray%7Clabel:B%7C東京都渋谷区宇田川町31－3+第3田中ビル3階+&markers=color:gray%7Clabel:C%7C東京都千代田区有楽町1－6－8－5F+&markers=color:gray%7Clabel:D%7C東京都豊島区南池袋1－23－6－11F+&markers=color:gray%7Clabel:E%7C東京都立川市曙町2－11－7+立川リージェントビル3F+&markers=color:gray%7Clabel:F%7C東京都渋谷区宇田川町25－5%E3%80%80センタービル4F+&markers=color:green%7Clabel:G%7C東京都豊島区東池袋1－20－6+プラザイン池袋3階+&markers=color:gray%7Clabel:H%7C東京都渋谷区神南1－19－3%E3%80%80ハイマンテン神南ビルB1F+&markers=color:gray%7Clabel:I%7C東京都新宿区新宿3－6－7－3F+&markers=color:gray%7Clabel:J%7C東京都新宿区歌舞伎町1－17－12－6F+";
$longurl = json_encode($long_url);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$urlshortener.$key);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_POST,true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $longurl);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($longurl))
);
$response = curl_exec($ch) or die("error" . curl_error($ch));

$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
$result = json_decode($response, true);
var_dump($result);