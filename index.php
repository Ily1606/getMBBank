<?php
if (!file_exists("./config.php")) {
    die("Please config.php");
}
include_once("./config.php");

function login($username, $password, $captchaText)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://online.mbbank.com.vn/retail_web/internetbanking/doLogin',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "userId": "' . $username . '",
            "password": "' . md5($password) . '",
            "captcha": "' . $captchaText . '",
            "sessionId": null,
            "refNo": "6fc291182d34a5167e1e9a72c9070531-2021121521221061",
            "deviceIdCommon": "c1gslvi1-0000-0000-0000-2021121518290580"
        }',
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic QURNSU46QURNSU4=",
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}
function createTaskCaptcha($base64_img)
{
    global $keyanticaptcha;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.anti-captcha.com/createTask',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "clientKey":"' . $keyanticaptcha . '",
            "task":
                {
                    "type":"ImageToTextTask",
                    "body":"' . $base64_img . '"
                }
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json'
        ),
    ));

    $response = json_decode(curl_exec($curl), true);

    curl_close($curl);
    return $response["taskId"];
}
function checkProgressCaptcha($id)
{
    global $keyanticaptcha;
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.anti-captcha.com/getTaskResult',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "clientKey":"' . $keyanticaptcha . '",
            "taskId": ' . $id . '
        }',
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json'
        ),
    ));

    $response = json_decode(curl_exec($curl), true);

    curl_close($curl);
    if ($response["status"] != "ready") {
        sleep(5);
        return checkProgressCaptcha($id);
    } else {
        return $response["solution"]["text"];
    }
}
function getCaptcha()
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://online.mbbank.com.vn/retail-web-internetbankingms/getCaptchaImage',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
  "refNo": "2021121519165529",
  "deviceIdCommon": "c1gslvi1-0000-0000-0000-2021121518290580",
  "sessionId": ""
}',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic QURNSU46QURNSU4='
        ),
    ));

    $response = json_decode(curl_exec($curl), true);

    curl_close($curl);
    return $response["imageString"];
}
function getTransactionHistory($session)
{
    $curl = curl_init();
    $time_begin = date('d/m/Y', strtotime("-1 day"));
    $time_end = date("d/m/Y");
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://online.mbbank.com.vn/retail_web/common/getTransactionHistory',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "accountNo": "' . $session["cust"]["defaultAccount"]["acctNo"] . '",
            "fromDate": "'.$time_begin.'",
            "toDate": "'.$time_end.'",
            "historyNumber": "",
            "historyType": "DATE_RANGE",
            "type": "ACCOUNT",
            "sessionId": "' . $session["sessionId"] . '",
            "refNo": "' . $session["refNo"] . '",
            "deviceIdCommon": "' . $session["cust"]["deviceId"] . '"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic QURNSU46QURNSU4=' //là base64 của ADMIN:ADMIN, đây là hằng số rồi
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}
$data = json_decode(file_get_contents("./data.json"), true);
if ($data["status"] == "checking" && getdate()[0] - $data["last_excute"] < 300) {
    // Có cron đang thực thi, trả về dữ liệu tạm thời
    insertToDB($data["data"]);
    echo json_encode($data["data"]);
    echo "Load from cache!";
} else {
    $data["status"] = "checking";
    $data["last_excute"] = getdate()[0];
    file_put_contents("./data.json", json_encode($data));
    //mở model_session.json, đây là mẫu dữ liệu khi login thành công, lấy các thông tin cần thiết từ mẫu này
    $session = file_get_contents("./model_session.json");
    $session = json_decode($session, true);
    $datatrans = json_decode(getTransactionHistory($session), true);
    $number_login_failed = 0;
    if ($datatrans["result"]["responseCode"] != "00") {
        //Session sai, thực hiện lại đăng nhập, và ghi session mới vào file session
        $data["status"] = "checking";
        file_put_contents("./data.json", json_encode($data));
        handleLogin();
    } else {
        handleGetTransactionHistory($datatrans);
    }
}
function handleGetTransactionHistory($data)
{
    //Xử lý code trong này
    insertToDB($data);
    $data_raw = json_encode($data);
    echo $data_raw;
    $new_data = build_saveData($data);
    file_put_contents("./data.json", json_encode($new_data));
}
function insertToDB($data){
    //$conn = new Mysql()
    foreach ($data["transactionHistoryList"] as $value) {
        $description = mb_strtolower($value["description"]);
        if(strpos($description,"tbolach5")){
            $username = explode('tbolach5 ',$description)[1];
            //echo $username;
            $money = $value["creditAmount"];
            $time_exchange = $value["transactionDate"];
            echo "$username-$money-$time_exchange";
        }
    }
}
function build_saveData($data)
{
    $new_data = [];
    $new_data["status"] = "done";
    $new_data["last_excute"] = getdate()[0];
    $new_data["data"] = $data;
    return $new_data;
}
function handleLogin()
{
    global $username, $password, $number_login_failed, $data;
    $base64_captcha_img = getCaptcha();
    $taskID = createTaskCaptcha($base64_captcha_img);
    $captchaText = checkProgressCaptcha($taskID);
    $session_raw = login($username, $password, $captchaText);
    $session = json_decode($session_raw, true);
    if ($session["result"]["responseCode"] == "00") {
        file_put_contents("./model_session.json", $session_raw);
        $datatrans = json_decode(getTransactionHistory($session), true);
        //Xử lý tiếp
        handleGetTransactionHistory($datatrans);
    } else if ($session["result"]["responseCode"] == "GW283") {
        //Login fail ở đây
        $number_login_failed++;
        if ($number_login_failed <= 5) {
            handleLogin();
            echo "Login failed! Trying againt...";
        } else {
            $data["status"] = "failed";
            $new_data["last_excute"] = getdate()[0];
            file_put_contents("./data.json", json_encode($data));
            echo "Login failed!";
        }
    } else {
        $data["status"] = "error_server";
        $data["last_excute"] = getdate()[0];
        file_put_contents("./data.json", json_encode($data));
        echo "Some thing went wrong";
    }
}
//mở model_session_error.json, đây là mẫu khi phiên hết hạn