<?php
    /*頁面及連線設定*/
    header("Content-Type: text/html; charset=utf-8");                           //宣告頁面字元集與編碼
    require("conn.php");                                                        //引入連線引用檔

    $all_member_query  = "SELECT * FROM `member`";
    $all_member_result = mysqli_query($db_link, $all_member_query);
    $all_member_number = mysqli_num_rows($all_member_result);

    $all_society_query  = "SELECT * FROM `society`";
    $all_society_result = mysqli_query($db_link, $all_society_query);
    $all_society_number = mysqli_num_rows($all_society_result);

    $i = 1;
    $t = strtotime(date("Y-m-d"));
    const oy = 86400 * 365;

    include "ini.php";                                                          //引入寫在外部檔案內的最近一次執行程式日期

    if (strtotime(date("Y-m-d")) != strtotime($toDate)) {
        while ($mData = mysqli_fetch_assoc($all_member_result)) {
            if ($mData['mOf'] == 0 && $mData['mHm'] == 0  && $mData['mDm'] == 0 && ($t - strtotime($mData['mPd']) > oy)) {
                $rewrite_member_query  = "UPDATE `member` SET `mOf` = '1' WHERE `mId`  = '{$mData['mId']}'";
                $rewrite_mlistm_query  = "UPDATE `mlist` SET `msOf` = '1' WHERE `msNb` = '{$mData['mNb']}'";
                // echo $rewrite_member_query . "<br>" . $rewrite_mlistm_query . "<br><br>";
                $rewrite_member_result = mysqli_query($db_link, $rewrite_member_query);
                $rewrite_mlistm_result = mysqli_query($db_link, $rewrite_mlistm_query);
            }
            if ($mData['mOf'] == 0 && $mData['mHm'] == 0  && $mData['mDm'] == 0 && ($t - strtotime($mData['mPd']) < oy && $t - strtotime($mData['mPd']) > 0)) {
                $rewrite_mlistm_query  = "UPDATE `mlist` SET `msOf` = '2' WHERE `msNb` = '{$mData['mNb']}'";
                // echo $rewrite_mlistm_query . "<br><br>";
                $rewrite_mlistm_result = mysqli_query($db_link, $rewrite_mlistm_query);
            }
        }
        while ($sData = mysqli_fetch_assoc($all_society_result)) {
            if ($sData['sOf'] == 0 && ($t - strtotime($sData['sPd']) > oy)) {
                $rewrite_society_query  = "UPDATE `society` SET `sOf` = '1' WHERE `sId` = '{$sData['sId']}'";
                $rewrite_mlists_query   = "UPDATE `mlist` SET `msOf` = '2' WHERE `msNb` = '{$sData['sNb']}'";
                // echo $rewrite_society_query . "<br>" . $rewrite_mlists_query . "<br><br>";
                $rewrite_society_result = mysqli_query($db_link, $rewrite_society_query);
                $rewrite_mlists_result  = mysqli_query($db_link, $rewrite_mlists_query);
            }
            if ($sData['sOf'] == 0 && ($t - strtotime($mData['sPd']) < oy && $t - strtotime($mData['sPd']) > 0)) {
                $rewrite_mlists_query  = "UPDATE `mlist` SET `msOf` = '2' WHERE `msNb` = '{$sData['sNb']}'";
                // echo $rewrite_mlists_query . "<br><br>";
                $rewrite_mlists_result = mysqli_query($db_link, $rewrite_mlists_query);
            }
        }
        $init = file("ini.php") or die("Unable to open file!");                 //將外部設定檔的內容取出為陣列
        $init[10] = "    \$toDate = \"" . date("Y-m-d") . "\";\n";              //改寫預設頁數的初始值
        $inittext = implode('', $init);                                         //將取出的陣列重新組合在一起
        file_put_contents("ini.php", $inittext);                                //寫入修改過的檔案內容
    }
?>