<?php
/**
 * Created by PhpStorm.
 * User: ukito
 * Date: 15/12/03
 * Time: 21:36
 */

require_once("simple_html_dom.php");
mb_language("Japanese");

//表示文言のテンプレート設定
$no_info = "混雑状況が提供されていません";
$no_wait = "チャンスです！待ち時間なし！";
$wait = "待ってる人達：";
$error_msg = "エラー";

//slackで発行されたURL
$incoming_url = "https://hooks.slack.com/services/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";

//eparkから鳥貴族のHTMLを取得
$test_epark_url = "http://epark.jp/list/tokyo/category_1";
$real_epark_url = "http://epark.jp/list?freeword=%E9%B3%A5%E8%B2%B4%E6%97%8F%E3%80%80%E6%B8%8B%E8%B0%B7";
$epark_url = $test_epark_url;


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
    "text" => "渋谷鳥貴族混雑状況",
    "attachments" => array()
);
foreach($store_info as $key => $value){
    switch ($value["status"]){
        case "-1":
            $color = "danger";
            break;
        case "0":
            $color = "#bdbdbd";
            break;
        case "1":
            $color = "good";
            break;
        case "2":
            $color = "warning";
            break;
        default:
            $color = "danger";
            break;
    }
    $payload["attachments"][$key] = array(
        "fallback" => $value["name"],
        "pretext" => $value["name"],
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
$payload = json_encode($payload);
echo $payload;



//slackに投稿をPOST
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$incoming_url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST,true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$output = curl_exec($ch) or die("error" . curl_error($ch));



