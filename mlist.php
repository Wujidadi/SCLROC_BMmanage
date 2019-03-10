<?php
    /*頁面及連線設定*/
    header("Content-Type: text/html; charset=utf-8");                           //宣告頁面字元集與編碼
    require("conn.php");                                                        //引入連線引用檔

    $list_query  = "SELECT * FROM `mlist`";
    $list_result = mysqli_query($db_link, $list_query);
    $list_number = mysqli_num_rows($list_result);

    while ($data[] = mysqli_fetch_assoc($list_result));                         //直接取盡查詢結果並轉為陣列
    //echo $data[1000]['mNb'] . ' ' , $data[1000]['mNm'];

    $num = strtoupper($_GET['num']);
    if (strlen($num) > 0) {
        for ($i = 0; $i < count($data); $i++) {
            if ($num == $data[$i]['msNb']) {
                $arr = array(
                    'nm' => $data[$i]['msNm'],
                    'sp' => $data[$i]['msSp'],
                    'fg' => $data[$i]['msOf']
                );
                break;
            } else {
                $arr = "";
            }
        }
        echo json_encode($arr);
    } else {
        echo "";
    }

    mysqli_close($db_link);
?>