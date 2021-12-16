<?php
$data = json_decode(file_get_contents("./data.json"),true);
$status_server = $data["status"]; //Đây là trạng thái của hệ thống cron
if($data["status"] == "checking"){ //Cron đang chạy
    echo "Server đang kiểm tra";
}
else if($data["status"] == "done"){ //Cron đã thực hiện
    echo "Dữ liệu này là dữ liệu mới nhất";
}
else if($data["status"] == "failed" || $data["status"] == "error_server"){ //Cron báo lỗi
    //Có thể thêm code gửi mail cho admin báo lỗi ở đây
    echo "Server đang gặp lỗi";
}
//// Việc lấy dữ liệu không liên quan đến các trạng thái, vì file này như 1 bộ nhớ cache

$last_excute = $data["last_excute"]; // Thời gian cron chạy cuối cùng

// Tính toán thời gian, đã thực thi bao lâu qua time remaining
$now = getdate()[0];
$time_remaining = $now - $last_excute;
$min = floor($time_remaining / 60);
$sec = floor($time_remaining % 60);
echo "Thực thi lần cuối cách đây $min phút $sec giây";
foreach ($data["data"]["transactionHistoryList"] as $value) {
    $description = mb_strtolower($value["description"]);
    if(strpos($description,"tbolach5")){
        $username = explode('tbolach5 ',$description)[1];
        //echo $username;
        $money = $value["creditAmount"];
        $time_exchange = $value["transactionDate"];
        echo "$username-$money-$time_exchange";
    }
}

?>