<?php
    /*頁面及連線設定*/
    header("Content-Type: text/html; charset=utf-8");                           //宣告頁面字元集與編碼
    require("conn.php");                                                        //引入連線引用檔

    $n = (string)$_GET['n'];

    $mem_query  = "SELECT * FROM `member` WHERE `mNb` = '$n' AND `mDr` IS NULL";
    $mem_result = mysqli_query($db_link, $mem_query);
    $mem_number = mysqli_num_rows($mem_result);
    
    $soc_query  = "SELECT * FROM `society` WHERE `sNb` = '$n' AND `sDr` IS NULL";
    $soc_result = mysqli_query($db_link, $soc_query);
    $soc_number = mysqli_num_rows($soc_result);

    $cql_query  = "SELECT * FROM `circulation` WHERE `cMn` = '$n' AND `cRt` IS NULL";
    $cql_result = mysqli_query($db_link, $cql_query);
    $cql_number = mysqli_num_rows($cql_result);

    if ($mem_number > 0 || $soc_number > 0) {
        if ($cql_number > 0) {
            while ($data = mysqli_fetch_assoc($cql_result)) {
                $json[] = array(
                    "ID"    => $data['cId'],
                    "bNum"  => $data['cBn'],
                    "bName" => $data['cBm'],
                    "mNum"  => $data['cMn'],
                    "mName" => $data['cMm'],
                    "bTime" => $data['cBt'],
                    "rTime" => $data['cRt']
                );
            }
        } else {
            $json = "";
        }
        echo json_encode($json);
    } else {
        echo '';
    }

    mysqli_close($db_link);
?>