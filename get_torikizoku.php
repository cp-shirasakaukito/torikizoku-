<?php
/**
 * Created by PhpStorm.
 * User: ukito
 * Date: 15/12/03
 * Time: 21:36
 */

require_once("simple_html_dom.php");
//mb_language("Japanese");

//表示文言のテンプレート設定
$no_info = "混雑状況が提供されていません";
$no_wait = "チャンスです！待ち時間なし！";
$wait = "待ってる人達：";
$error_msg = "エラー";

$epark_url = "http://epark.jp/list?freeword=";
$gmap_url = "https://maps.googleapis.com/maps/api/staticmap?size=500x400&";

//eparkから鳥貴族のHTMLを取得
//検索する地域を抽出
if($_POST["text"] === "tori"){
    $area = "渋谷";
} else {
    $area = substr($_POST["text"],5);
}
$freeword = urlencode("鳥貴族 ".$area);
$epark_url .=$freeword;

//slackで発行されたURL
//$incoming_url = "https://hooks.slack.com/services/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";
$html_source = file_get_contents($epark_url);
$html_object = str_get_html($html_source);

/*
 * 情報を整形する　目標の形！
 * array(
 *  0 => array(
 *   "store_name" => "store_name",
 *   "address" => "address",
 *   "status" => "status",
 *   "text" => "text"
 *  ),
 *  1....
 * )
 * */
//店舗名一覧を取得
$store_name = $html_object->find('.result_material section h2 a');
$address = $html_object->find('span.subadoress');
/*
 * ※鳥貴族の中でも混雑状況を提供しない店舗もある
 * その場合は.main_status の子にdiv.alertが存在しない
 * 提供している店舗は.main_status の子にdiv.alertが存在する
 * */
$status_wrapper = $html_object->find('div.main_status');


$count_store = count($store_name);
$store_info = array();
for($i=0; $i < $count_store;$i++){
    $store_info[$i]["name"] = $store_name[$i]->plaintext;
    $store_info[$i]["address"] = $address[$i]->plaintext;

    $status_attr_0 = $status_wrapper[$i]->first_child()->attr;//classがclearなら混雑情報なし、status_titleなら情報あり
    switch($status_attr_0["class"]) {
        case "clear": //混雑情報提供なし
            $store_info[$i]["status"] = 0;
            $store_info[$i]["text"] = $no_info;
            break;
        case "status_title": //混雑状況あり
            $status_attr_1 = $status_wrapper[$i]->children(1)->attr;
            switch($status_attr_1["class"]){ //
                case "alert"://待ち時間なし　鳥貴族以外では待ち時間あるケースあり（鳥貴族以外対応時は要修正）
                    $store_info[$i]["status"] = 1;
                    $store_info[$i]["text"] = $no_wait;
                    break;
                case "time_wait"://待ち時間あり
                    $status = $status_wrapper[$i]->children(1)->last_child();
                    $store_info[$i]["status"] = 2;
                    $store_info[$i]["text"] = $wait.$status->plaintext;
                    break;
                default://エラーが発生
                    $store_info[$i]["status"] = -1;
                    $store_info[$i]["text"] = $error_msg;
                    break;
            }
    }
}
/*
 * slackのpayload形式に合わせる
 * array(
 *  "text" => "渋谷鳥貴族混雑状況",
 *  "attachments" => array(
 *      array(
 *          "fallback" => "$店名",
 *          "color" => "$提供なし(#bdbdbd)、まつ(warning), エラー(danger)、待たない(good)"
 *          "pretext" => "$店名",
 *          "text" => "$住所",
 *          "fields" => array(
 *              "title" => "混雑状況",
 *              "value" => "*混雑情報ステータステキスト"
 *          )
 *      )
 *  )
 * )
 * */

$payload = array(
    "text" =>"鳥貴族の混雑状況",
    "attachments" => array()
);
foreach($store_info as $key => $value){
    switch ($value["status"]){
        case "-1":
            $color = "warning";
            $marker_color = "orange";
            break;
        case "0":
            $color = "#bdbdbd";
            $marker_color = "gray";
            break;
        case "1":
            $color = "good";
            $marker_color = "green";
            break;
        case "2":
            $color = "danger";
            $marker_color = "red";
            break;
        default:
            $color = "danger";
            $marker_color = "red";
            break;
    }
    $label = chr(65+$key);
    $marker_info = "markers=color:".$marker_color."|label:".$label."|".urlencode($value["address"]);
    $gmap_url .= $marker_info;
    if($value !== end($store_info)){
        $gmap_url .= "&";
    }
    $payload["attachments"][$key] = array(
        "fallback" => $label.$value["name"],
        "pretext" => $label.$value["name"],
        "text" => $value["address"],
        "color" => $color,
        "fields" => array(
            array(
                "title" => "混雑状況",
                "value" => $value["text"]
            )
        )
    );
}

$urlshortener = "https://www.googleapis.com/urlshortener/v1/url?key=";
$key = "AIzaSyAjgDegr9NlRZ1o-Ldhr2V3xBHQK8MZFBU";

//slackに投稿をPOST
$longurl["longUrl"] = $gmap_url;
$longurl = json_encode($longurl);

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
$result = json_decode($response, true);

//$payload["text"] = $gmap_url;
$payload["text"] = $result["id"];
$payload = json_encode($payload);
echo $payload;






