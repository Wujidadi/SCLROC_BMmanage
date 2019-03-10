<?php
    /*頁面及連線設定*/
    header("Content-Type: text/html; charset=utf-8");                           //宣告頁面字元集與編碼
    require("conn.php");                                                        //引入連線引用檔

    $id = (string)$_GET['id'];
    $nowTime = date("Y-m-d H:i:s");
    $br_query  = "UPDATE `circulation` SET `cRt` = '" . $nowTime . "' WHERE `circulation`.`cId` = $id";
    $br_result = mysqli_query($db_link, $br_query);

    $json = array(
        "fullFormat" => $nowTime,
        "abbrFormat" => substr($nowTime, 0, 10)
    );
    echo json_encode($json);

    mysqli_close($db_link);
?>