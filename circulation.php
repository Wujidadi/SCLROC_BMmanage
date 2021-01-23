<?php
    /*頁面及連線設定*/
    header("Content-Type: text/html; charset=utf-8");                           //宣告頁面字元集與編碼
    require("conn.php");                                                        //引入連線引用檔

    session_start();                                                            //啟動 session

    if (isset($_COOKIE['login_status'])) {
        setcookie("login_status", "Loging in!", time() + 7200);                 //延長登入 cookie 時間至本次重新整理後的 2 小時
    } else {
        unset($_SESSION['loginMember']);
        unset($_SESSION['loginName']);
        unset($_SESSION['memberLevel']);
    }

    /*設立管理者登入旗標*/
    if (isset($_SESSION['memberLevel']) && $_SESSION['memberLevel'] == 'admin') {
        $adminFlag = "true";
    } else {
        $adminFlag = "false";
    }

    /*檢查借還書紀錄資料表中書名欄為空的紀錄，並將之補滿*/
    $empty_query  = "SELECT * FROM `circulation` WHERE `cBm` = ''";             //查詢書名欄為空的借還書紀錄
    $empty_result = mysqli_query($db_link, $empty_query);                       //執行查詢
    $empty_number = mysqli_num_rows($empty_result);                             //計算書名欄為空的借還書紀錄總數
    if ($empty_number > 0) {
        while ($data = mysqli_fetch_assoc($empty_result)) {
            $ct_query  = "SELECT * FROM `book` WHERE `bNb` = '{$data['cBn']}'"; //依書號查詢該本書的書名
            $ct_result = mysqli_query($db_link, $ct_query);                     //執行查詢
            // echo $ct_query;
            while ($fdata = mysqli_fetch_assoc($ct_result)) {
                $fqr  = "UPDATE `circulation` SET `cBm` = '{$fdata['bNm']}'";   //將查詢得到的書名更新至借還書紀錄
                $fqr .= " WHERE `cId` = '{$data['cId']}'";
                // echo $fqr . "<br>";
                $frt = mysqli_query($db_link, $fqr);                            //執行查詢
            }
        }
    }

    /*資料庫查詢設定－查詢所有借還書紀錄*/
    $total_query  = 'SELECT * FROM `circulation`';                              //查詢所有借還書資料並依 ID 倒序排列
    $total_result = mysqli_query($db_link, $total_query);                       //查詢所有借還書紀錄
    $total_number = mysqli_num_rows($total_result);                             //計算借還書紀錄總數

    $reverse_sql = ' ORDER BY `cId` DESC';                                      //倒序排列語句
    /*動態決定正序或倒序排列資料，這裡用到了下方設置的 qt 計數器*/
    if (!isset($_GET['qt'])) $_GET['qt'] = 0;                                   //$_GET['qt'] 初始值設成 0，第一次載入時為倒序排列，設 > 0 則反之
    $reverse_str = ((isset($_GET['reverse']) AND $_GET['reverse'] == 1) || $_GET['qt'] == 0) ? $reverse_sql : "";
    //echo $_GET['qt'];

    $unreturned_sql = ' AND `cRt` IS NULL';                                     //選取未還書語句
    $unreturned_str = (isset($_GET['unrt'])) ? $unreturned_sql : "";

    $default_from_date = date("Y") . "-01-01";                                  //預設查詢起始日期
    $default_till_date = date("Y-m-d");                                         //預設查詢截止日期
    //echo $default_from_date . "<br>" . $default_to_date;
    $from_date  = (isset($_GET['FromTime'])) ? $_GET['FromTime'] : $default_from_date;
    $till_date  = (isset($_GET['TillTime'])) ? $_GET['TillTime'] : $default_till_date;
    $till_date .= " 23:59:59";                                                  //查詢截止日期要算到晚上 23:59:59

    /*依所設定的起訖日期查詢資料庫*/
    $extant_query  = "SELECT * FROM `circulation` WHERE `cBt` BETWEEN '$from_date' AND '$till_date'" . $unreturned_str . $reverse_str;
    //echo $extant_query;
    $extant_result = mysqli_query($db_link, $extant_query);
    $extant_number = mysqli_num_rows($extant_result);

    /*設定資料分頁*/
    include "ini.php";                                                          //引入寫在外部檔案內的預設每頁筆數
    $page_number = 1;                                                           //預設頁數
    if (isset($_GET['rows'])) {                                                 //若使用者已輸入每頁筆數
        if ($_GET['rows'] != '') {                                              //且此筆數不為空值
            $page_row = $_GET['rows'];                                          //依使用者所輸入的每頁筆數設置每頁顯示筆數
        } else if ($_GET['rows'] == '') {                                       //但若使用者輸入的筆數為空值
            $_GET['rows'] = $page_row_circulation;                              //以預設每頁筆數取代該空值
            $page_row = $_GET['rows'];                                          //同時以此預設每頁筆數為準來顯示本頁資料
        }
    } else {
        $page_row = $page_row_circulation;                                      //若使用者根本沒有輸入每頁筆數，直接套用外部檔案的預設每頁筆數
    }
    if ((isset($_GET['page'])) && ($_GET['page'] != '')) {
        $page_number = $_GET['page'];                                           //依使用者所選頁數值更新頁數
    } else if (isset($_POST['PageNumber'])) {
        $page_number = $_POST['PageNumber'];                                    //依彈窗表單送出時所在頁面更新頁數
    } else {
        $page_number = 1;                                                       //未設定頁數時，直接令頁數為 1
    }
    $start_row    = $page_row * ($page_number - 1);                             //設定每頁從第 N 筆開始顯示，N = (頁數 - 1) * 每頁筆數
    $limit_query  = $extant_query . ' LIMIT ' . $start_row . ', ' . $page_row;  //查詢語法加上限制筆數，由第 N 筆起顯示所設定的筆數
    $limit_result = mysqli_query($db_link, $limit_query);                       //依限制條件查詢
    $extant_pages = ceil($extant_number / $page_row);                           //計算借還書紀錄總頁數 = (查詢筆數 ÷ 每頁筆數) 並無條件進位

    /*取得當前資料庫中「ID」的最大值*/
    $id_max_query = 'SELECT MAX(`cId`) FROM `circulation`';
    $id_max_result = mysqli_query($db_link, $id_max_query);
    $id_max = @mysqli_fetch_row($id_max_result);

    if (isset($_POST['PassFlag'])) {
        /*將彈窗表單傳回的各項值，重新命名為名稱稍短、且可直接在雙引號中引用的變數，順便解決了有些值為 NULL 的問題*/
        $PcId    = $_POST['cId'];
        $PcBnum  = ($_POST['cBookNumber'] == '')   ? 'NULL' : "'" . strtoupper($_POST['cBookNumber'])   . "'";
        $PcBname = ($_POST['cBookName'] == '')     ? 'NULL' : "'" . $_POST['cBookName']   . "'";
        $PcMnum  = ($_POST['cMemberNumber'] == '') ? 'NULL' : "'" . strtoupper($_POST['cMemberNumber']) . "'";
        $PcMname = ($_POST['cMemberName'] == '')   ? 'NULL' : "'" . $_POST['cMemberName'] . "'";
        $PcBT    = ($_POST['cBorrowTime'] == '')   ? 'NULL' : "'" . $_POST['cBorrowTime'] . "'";
        $PcRT    = ($_POST['cReturnTime'] == '')   ? 'NULL' : "'" . $_POST['cReturnTime'] . "'";

        /*傳值旗標為「修改」時更新資料庫*/
        if ($_POST['PassFlag'] == '修改') {
            $edit_query  = "UPDATE `circulation` SET ";
            $edit_query .= "`cBn` = $PcBnum, ";
            $edit_query .= "`cBm` = $PcBname, ";
            $edit_query .= "`cMn` = $PcMnum, ";
            $edit_query .= "`cMm` = $PcMname, ";
            $edit_query .= "`cBt` = $PcBT, ";
            $edit_query .= "`cRt` = $PcRT ";
            $edit_query .= "WHERE `cId` = $PcId";
            //echo $edit_query;
            $edit_result = mysqli_query($db_link, $edit_query);
        }

        /*傳值旗標為「刪除」時刪除該筆資料*/
        else if ($_POST['PassFlag'] == '刪除') {
            $delete_query  = "DELETE FROM `circulation` WHERE `cId` = $PcId";
            //echo $delete_query;
            $delete_result = mysqli_query($db_link, $delete_query);
        }
        /*若更新資料失敗則顯示錯誤訊息，成功則重新整理頁面*/
        if ((mysqli_errno($db_link)) && (mysqli_errno($db_link) != 0)) {
            $error_number = mysqli_errno($db_link);
        } else {
            header('refresh: 0');
        }
    } else {
        $edit_query = '';                                                       //傳值旗標不存在時清空更新資料查詢語法字串
    }

    /*傳值旗標為「新增」時向資料庫新增一至多筆資料*/
    if (isset($_POST['aPassFlag'])) {
        if ($_POST['aPassFlag'] == '新增') {
            for ($i = 0; $i < $_POST['remain']; $i++) {
                if (isset($_POST['brbn-'. $i]) && $_POST['brbn-'. $i] != '') {
                    $edit_query  = "INSERT INTO `circulation` (`cId`, `cBn`, `cBm`, `cMn`, `cMm`, `cBt`) VALUES ";
                    $edit_query .= "(" . $_POST['brid-'. $i] . ", ";
                    $edit_query .= "'" . strtoupper($_POST['brbn-'. $i]) . "', ";
                    $edit_query .= "'" . $_POST['brbm-'. $i] . "', ";
                    $edit_query .= "'" . strtoupper($_POST['cbMn']) . "', ";
                    $edit_query .= "'" . $_POST['cbMm'] . "', ";
                    $edit_query .= "'" . $_POST['brbt-'. $i] . "');";
                    //echo $edit_query . "<br>";
                    mysqli_query($db_link, $edit_query);
                }
            }
        }
        /*若更新資料失敗則顯示錯誤訊息，成功則重新整理頁面*/
        if ((mysqli_errno($db_link)) && (mysqli_errno($db_link) != 0)) {
            $error_number = mysqli_errno($db_link);
        } else {
            header('refresh: 0');
        }
    } else {
        $edit_query = '';                                                       //傳值旗標不存在時清空更新資料查詢語法字串
    }

    include "searchItem.php";                                                   //引入搜尋選單值檔案

    if (isset($_GET['Search'])) {
        $sLen = strlen($_GET['Search']);
        if (preg_match("/ {{$sLen}}/", $_GET['Search'])) {
            $_GET['Search'] = "";                                               //若使用者輸入字串全為空白，視同無搜尋字串
        }
    }

    /*若使用者有設定搜尋條件，計算符合條件的筆數及頁數*/
    if (isset($_GET['Option'])) {
        /*若使用者先已選定搜尋選項，下拉式搜尋選單顯示為該選項*/
        for ($i = 0; $i < count($circulationSearchItem); $i++) {
            if ($_GET['Option'] == $circulationSearchItem[$i]['str']) {
                $searchWords = $circulationSearchItem[$i]['sql'];
                break;
            }
        }

        /*結合搜尋條件的查詢語法*/
        $searchPrefix  = "SELECT * FROM `circulation` WHERE ";
        if ($_GET['Search'] != '') {
            /*防範隱碼攻擊*/
            $search_keyword = filter_var($_GET['Search'], FILTER_SANITIZE_MAGIC_QUOTES);
            if ($_GET['Option'] == 'mnum') {
                $searchTarget = " LIKE '" . $search_keyword . "' AND `cBt` BETWEEN '$from_date' AND '$till_date'";
            } else {
                $searchTarget  = " LIKE '%" . $search_keyword . "%' AND `cBt` BETWEEN '$from_date' AND '$till_date'";
            }
        } else {
            $searchWords = "";
            $searchTarget = "`cBt` BETWEEN '$from_date' AND '$till_date'";
        }
        $search_query  = $searchPrefix . $searchWords . $searchTarget . $unreturned_str . $reverse_str;

        /*測試語法是否正確*/
        //echo $search_keyword . "<br>" . $search_query . "<br>" . $_GET['Option'] . "<br>" . $searchWords;

        $search_result = mysqli_query($db_link, $search_query);
        $search_number = mysqli_num_rows($search_result);
        $search_pages  = ceil($search_number / $page_row);                      //計算符合搜尋條件的借還書紀錄總頁數 = (結果數 ÷ 每頁筆數) 並無條件進位

        /*搜尋條件加上筆數及頁數限制*/
        $search_limit_query  = $search_query . ' LIMIT ' . $start_row . ', ' . $page_row;
        $search_limit_result = mysqli_query($db_link, $search_limit_query);
        $search_limit_number = mysqli_num_rows($search_limit_result);
    }

    $_GET['qt']++;                                                              //qt 計數器加 1
?>

<!DOCTYPE html>
<html>
<head>
    <!--宣告 html 頁面編碼-->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <!--宣告為 RWD 響應式網頁-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--宣告頁面標題，依借還書紀錄數目動態變化-->
    <title>借還書處理 (現有<?php echo $total_number; ?>筆借還書紀錄)</title>

    <!--引入頁面圖示-->
    <link rel="icon" href="favicon.ico">

    <!--引入 CSS 設定檔-->
    <link rel=stylesheet type="text/css" href="style.css">

    <!--引入 local jQuery-->
    <script src="../jQuery/jquery-3.3.1.js"></script>
    <!--jQuery from Microsoft CDN-->
    <!-- <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.js"></script> -->
    <!--jQuery from Google CDN-->
    <!-- <script src="http://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.js"></script> -->

    <!--引入 local jQuery UI-->
    <link rel=stylesheet type="text/css" href="../jQuery_UI/jquery-ui.min.css">
    <script src="../jQuery_UI/jquery-ui.min.js"></script>
</head>

<?php /*頁面載入或重新載入時，啟用 ESC 可跳出彈窗功能；而若彈窗表單所送出的 ScrollView 值已存在，畫面跳至以該值為頂點之處*/ ?>
<body id="memberBody" onload="<?php if (isset($_POST['PassFlag'])) echo "scrollView('" . $_POST['ScrollView'] . "'); "; ?>error_display();" onkeydown="modal_esc(event)">
    <?php
        if (isset($_POST['PassFlag'])) unset($_POST['PassFlag']);               //清除傳值旗標
    ?>

    <!--隱藏區域，存放 php 拋出的錯誤訊息，以利 JavaScript 存取-->
    <p id="ErrorInfo">
        <?php
            if (isset($error_number) && ($error_number) != 0) {
                echo $error_number;
            } else {
                echo "No Error!";
            }
        ?>
    </p>

    <!--隱藏區域，存放目前資料庫中「ID」的最大值-->
    <p id="IDMax">
        <?php
            echo $id_max[0];
        ?>
    </p>

    <h1 id="TitleCirculation">圖書借閱及歸還管理</h1>

    <?php if ($adminFlag == "true") { ?>
    <!--新增借還書紀錄資料按鈕-->
    <img id="addCirculationButton" src="add_br_button.png" onmouseout="this.src='add_br_button.png'" onmouseover="this.src='add_br_button_invert.png'" onclick="add_display()">
    <?php } ?>

    <!--選擇筆數及頁數表單-->
    <form action="" method="GET" id="CirculationSearcher">
        <table id="circulationSearchBox">
            <tr>
                <td style="width: 462px; text-align-last: justify">
                    <!--搜尋表單-->
                    <input type="text" id="cSearch" name="Search" value="<?php if (isset($_GET["Search"])) echo $_GET["Search"]; ?>">
                    <select id="cOption" name="Option">
                        <?php
                            /*在下拉式選單中依序列出搜尋條件*/
                            for ($i = 0; $i < count($circulationSearchItem); $i++) {
                                echo "<option value=\"" . $circulationSearchItem[$i]['str'] . "\"";
                                if ((isset($_GET["Option"])) && ($circulationSearchItem[$i]['str'] == $_GET["Option"])) echo "selected";
                                echo ">" . $circulationSearchItem[$i]['item'] . "</option>";
                            }
                        ?>
                    </select>
                </td>
                <td style="width: 50px"><label><input type="checkbox" id="cReverse" name="reverse" value="1"<?php if ((isset($_GET['reverse']) && $_GET['reverse'] == 1) || $_GET['qt'] == 1) echo "checked"; ?>>倒序</label></td>
                <?php /*說明：此處設定「倒序」勾選框在頁面首次載入時就要呈現被勾選的狀態，這由 $_GET['qt'] 計數器控制，
                        原應在計數器 = 0 時呈現被勾選狀態，但初次加載完成後上方的程式碼將計數器值加了 1，因此這裡必須指定計數器 = 1 時被勾選*/; ?>
            </tr>
            <tr>
                <td class="cTimePicker">
                    借閱日期區間：
                    <input type="date" id="cFromTime" name="FromTime" class="TimePicker" style="text-align-last: center" value="<?php if (isset($_GET['FromTime'])) echo $_GET['FromTime']; else echo $default_from_date; ?>"> ～
                    <input type="date" id="cTillTime" name="TillTime" class="TimePicker" style="text-align-last: center" value="<?php if (isset($_GET['TillTime'])) echo $_GET['TillTime']; else echo $default_till_date; ?>">
                </td>
                <td><label><input type="checkbox" id="cUnreturn" name="unrt" value="1"<?php if (isset($_GET['unrt']) && $_GET['unrt'] == 1) echo "checked"; ?>>未還</label></td>
            </tr>
            <tr>
                <td class="cSearchResult">
                    <?php
                        /*未設定搜尋條件時，使用所有未除帳書籍的筆數及頁數，否則使用符合搜尋條件的書籍之筆數及頁數，來展示頁面*/
                        if ((!isset($_GET['Search'])) || (!isset($_GET['Option']))) {
                            $display_num  = $extant_number;
                            $result_pages = $extant_pages;
                        } else {
                            $display_num  = $search_number;
                            $result_pages = $search_pages;
                        }

                        echo "總共 $display_num 筆，每頁顯示 <input type=\"number\" name=\"rows\" id=\"rows\" min=\"1\" value=\"";
                        if (isset($_GET['rows'])) {
                            echo $_GET['rows'];
                        } else {
                            echo $page_row;
                        }

                        echo "\"> 筆，共 $result_pages 頁";
                        // echo "要去第 <input type=\"number\" name=\"page\" id=\"page\" min=\"1\" value=\"";
                        // if (!isset($_GET['page'])) {
                        //     echo 1;
                        // } else if ($_GET['page'] > $result_pages) {
                        //     if ($result_pages > 0) echo $result_pages;
                        //     else echo 1;
                        // } else {
                        //     for ($i = 1; $i <= $result_pages; $i++) {
                        //         if (isset($_GET['page']) && ($i == $_GET['page'])) {
                        //             echo $i;
                        //         } else if (isset($_POST['PageNumber']) && ($i == $_POST['PageNumber'])) {
                        //             echo $i;
                        //         } else if (($i == $page_number) && (!isset($_GET['page']) && !isset($_POST['PageNumber']))) {
                        //             echo $page_number;
                        //         }
                        //     }
                        // }
                        // echo "\"> 頁";

                        /*當使用者有更動每頁顯示筆數時，才更動外部檔案中設定的預設每頁筆數*/
                        if ((isset($_GET['rows'])) && ($_GET['rows'] != '') && ($_GET['rows'] != $page_row_circulation)) {
                            $init = file("ini.php") or die("Unable to open file!");                 //將外部設定檔的內容取出為陣列
                            $init[6] = "    \$page_row_circulation = " . $_GET['rows'] . ";\n";     //改寫預設頁數的初始值
                            $inittext = implode('', $init);                                         //將取出的陣列重新組合在一起
                            file_put_contents("ini.php", $inittext);                                //寫入修改過的檔案內容
                        }
                    ?>
                </td>
                <td style="padding-left: 0.25em">
                    <input type="submit" id="cSubmit" value="搜尋">
                </td>
            </tr>
        </table>

        <?php /*為了一開始就能令資料表依據變數自動倒序排列（而非直接寫死在頂端的 SQL 語句裡），設置一個計數器，
                當此計數器 = 0 時，使搜尋語句能依我們設定的變數而定，計數器 >= 1 時才由表單送出值來決定。*/ ?>
        <input type="hidden" name="qt" value="<?php if (isset($_GET['qt'])) echo $_GET['qt']; ?>">
    </form>

    <?php
        // /*更改搜尋條件時，若原先的所在頁數超過新搜尋結果的總頁數，顯示警告訊息並提示*/
        // if ($page_number > $result_pages) {
        //     echo "<div class=\"overPage\">當前所在頁數超過搜索總頁數！<br>請再按一次「搜尋」就可以跳到搜索結果第 1 頁囉～</div>";
        // };

        /*未設定任何條件時，顯示符合初始條件的借還書紀錄，否則依設定的條件顯示*/
        if ((!isset($_GET['Search']) || ($_GET['Search'] == '')) || (!isset($_GET['Option']) || ($_GET['Option'] == ''))) {
            $display_result = $limit_result;
        } else {
            $display_result = $search_limit_result;
        }

        $dateSeparator = "/";                                   //年月日分隔字元
        $mgFlag = 1;                                            //曆法旗標（0 = 西元紀年，1 = 民國紀年）
        echo "<htag id=\"calendar\">$mgFlag</htag>";

        /*搜尋結果 > 0 時才顯示表格*/
        if (mysqli_num_rows($display_result) > 0) {
            /*以表格顯示借還書紀錄*/
            echo "<table id=\"CirculationData\">
                <!--各欄-->
                <col id=\"cControlCol\">
                <col id=\"cBookNumberCol\">
                <col id=\"cBookNameCol\">
                <col id=\"cMemberNumberCol\">
                <col id=\"cMemberNameCol\">
                <col id=\"cBorrowTimeCol\">
                <col id=\"cReturnTimeCol\">

                <!--表頭-->
                <thead>
                    <tr id=\"cHead\">
                        <th id=\"cControlHead\"></th>
                        <th id=\"cBookNumberHead\">書號</th>
                        <th id=\"cBookNameHead\">書名</th>
                        <th id=\"cMemberNumberHead\">會號</th>
                        <th id=\"cMemberNameHead\">姓名</th>
                        <th id=\"cBorrowTimeHead\">借閱日期</th>
                        <th id=\"cReturnTimeHead\">歸還日期</th>
                    </tr>
                </thead>

                <tbody>";
                    /*將會員資料依限制條件分頁後輸出為陣列*/
                    while ($data = mysqli_fetch_assoc($display_result)) {
                        /*切割借閱日期為年、月、日並計算民國紀年*/
                        $btYear   = substr($data['cBt'], 0, 4) . $dateSeparator;
                        $mgbtYear = (int)substr($data['cBt'], 0, 4) - 1911 . $dateSeparator;
                        $btMonth  = substr($data['cBt'], 5, 2) . $dateSeparator;
                        $btDay    = substr($data['cBt'], 8, 2);
                        $btYear   = ($mgFlag == 1) ? $mgbtYear : $btYear;       //依據曆法旗標決定使用西元紀年或民國紀年
                        $bTime    = $btYear . $btMonth . $btDay;                //串接借閱年月日為字串

                        /*切割歸還日期為年、月、日並計算民國紀年*/
                        $rtYear   = substr($data['cRt'], 0, 4) . $dateSeparator;
                        $mgrtYear = (int)substr($data['cRt'], 0, 4) - 1911 . $dateSeparator;
                        $rtMonth  = substr($data['cRt'], 5, 2) . $dateSeparator;
                        $rtDay    = substr($data['cRt'], 8, 2);
                        $rtYear   = ($mgFlag == 1) ? $mgrtYear : $rtYear;       //依據曆法旗標決定使用西元紀年或民國紀年
                        $rTime    = $rtYear . $rtMonth . $rtDay;                //串接歸還年月日為字串

                        /*計算借書天數*/
                        $borrowDay = floor((strtotime(date("Y-m-d")) - strtotime(substr($data['cBt'], 0, 10))) / 86400);

                        echo "<htag id=\"scid" . $data['cId'] . "\">" . $data['cId'] . "</htag>";
                        echo "<htag id=\"scbn" . $data['cId'] . "\">" . $data['cBn'] . "</htag>";
                        echo "<htag id=\"scbm" . $data['cId'] . "\">" . $data['cBm'] . "</htag>";
                        echo "<htag id=\"scmn" . $data['cId'] . "\">" . $data['cMn'] . "</htag>";
                        echo "<htag id=\"scmm" . $data['cId'] . "\">" . $data['cMm'] . "</htag>";
                        echo "<htag id=\"scbt" . $data['cId'] . "\">" . $data['cBt'] . "</htag>";
                        echo "<htag id=\"scrt" . $data['cId'] . "\">" . $data['cRt'] . "</htag>";

                        echo "<tr id=\"circulation" . $data['cId'] . "\"";
                        if ($data['cRt'] == "" || $data['cRt'] == '0000-00-00 00:00:00') {
                            if ($borrowDay > 28) {
                                echo " class=\"overdue\"";
                            } else if ($borrowDay > 21) {
                                echo " class=\"soondue\"";
                            }
                        }
                        echo ">";     //每列賦予 ID
                            echo "<td class=\"cControlCell\">";
                            if ($adminFlag == "true") {
                                echo "<span class=\"TooltipBox\">
                                    <img src=\"edit.png\" alt=\"修改\" class=\"ImageButton\" onclick=\"modal_display('修改', $('#scid{$data['cId']}').text(), $('#scbn{$data['cId']}').text(), $('#scbm{$data['cId']}').text(), $('#scmn{$data['cId']}').text(), $('#scmm{$data['cId']}').text(), $('#scbt{$data['cId']}').text(), $('#scrt{$data['cId']}').text())\" onmouseover=\"modal_status('修改'); getWindowTop()\">
                                    <span class=\"TooltipText\">修改</span>
                                </span>
                                <span class=\"TooltipBox\">
                                    <img src=\"delete.png\" alt=\"刪除\" class=\"ImageButton\" onclick=\"modal_display('刪除', $('#scid{$data['cId']}').text(), $('#scbn{$data['cId']}').text(), $('#scbm{$data['cId']}').text(), $('#scmn{$data['cId']}').text(), $('#scmm{$data['cId']}').text(), $('#scbt{$data['cId']}').text(), $('#scrt{$data['cId']}').text())\" onmouseover=\"modal_status('刪除'); getWindowTop()\">
                                    <span class=\"TooltipText\">刪除</span>
                                </span>";
                            }
                            echo "</td>
                            <td class=\"cBookNumberCell\"><span onclick=\"window.open('book.php?Search={$data['cBn']}&Option=書號')\">{$data['cBn']}</span></td>
                            <td class=\"cBookNameCell\"><span onclick=\"window.open('book.php?Search={$data['cBm']}&Option=書名')\">{$data['cBm']}</span></td>";
                            if (substr($data['cMn'], 0, 1) == 'S') {
                                $s_filename = "society";
                            } else {
                                $s_filename = "member";
                            }
                            echo "<td class=\"cMemberNumberCell\"><span onclick=\"window.open('$s_filename.php?Search={$data['cMn']}&Option=num')\">{$data['cMn']}</span></td>
                            <td class=\"cMemberNameCell\"><span onclick=\"window.open('$s_filename.php?Search={$data['cMm']}&Option=name')\">{$data['cMm']}</span></td>
                            <td class=\"cBorrowTimeCell\">" . $bTime . "</td>
                            <td class=\"cReturnTimeCell\" id=\"crt" . $data['cId'] . "\">";
                            if ($data['cRt'] == "" || !preg_match("/^\d{4}-\d{2}-\d{2}/", $data['cRt'])) {
                                if ($adminFlag == "true") {
                                    echo "<button type=\"button\" onclick=\"book_return('" . $data['cId'] . "')\">還書</button>";
                                }
                            } else {
                                echo $rTime;
                            }
                            echo "</td>
                        </tr>";
                    }
                echo "</tbody>
            </table>

            <div class=\"pageBoxPack\">";

            /*使用者有選定每頁顯示筆數或頁數時，下方頁數選擇鈕的參數等於其值，否則等於預設值*/
            if (isset($_GET['rows'])) $boxRow = $_GET['rows']; else $boxRow = $page_row;

            /*使用者有輸入搜尋條件時，下方頁數選擇鈕的參數加入該條件，否則忽略*/
            if (isset($_GET['Search'])) $searchStr = "Search=" . $search_keyword . "&Option=" . $_GET['Option']; else $searchStr = "";

            /*依當前頁數與其他頁數的距離，決定頁數選擇鈕是否顯示及顯示的型態*/
            for ($i = 1; $i <= $result_pages; $i++) {
                if ($result_pages > 30) {
                    if ($i == $page_number) {
                        $pageBox_Display = "pageBoxFocus";
                    }
                    else if (abs($i - $page_number) < 5) {
                        $pageBox_Display = "pageBox";
                    }
                    else if ($i == 1) {
                        $pageBox_Display = "pageFirst";
                    }
                    else if ($i == $result_pages) {
                        $pageBox_Display = "pageLast";
                    }
                    else if ((abs($i - $page_number) % 10 == 0) && (abs($i - $page_number) < 50)) {
                        $pageBox_Display = "pagePerTen";
                    }
                    else if (abs($i - $page_number) % 50 == 0) {
                        $pageBox_Display = "pagePerFifty";
                    }
                    else if (abs($i - $page_number) % 100 == 0) {
                        $pageBox_Display = "pagePerHundred";
                    }
                    else {
                        $pageBox_Display = false;
                    }
                } else {
                    if ($i == $page_number) {
                        $pageBox_Display = "pageBoxFocus";
                    }
                    else if ($i == 1) {
                        $pageBox_Display = "pageFirst";
                    }
                    else if ($i == $result_pages) {
                        $pageBox_Display = "pageLast";
                    }
                    else {
                        $pageBox_Display = "pageBox";
                    }
                }
                if ($pageBox_Display) {
                    echo "<div class=\"pgNum $pageBox_Display\">";
                    if ($i != $page_number) {
                        $rev = (isset($_GET['reverse']) || !isset($_GET['qt']) || $_GET['qt'] == 1) ? "&reverse=1"  : "";
                        $urt = (isset($_GET['unrt']))     ? "&unrt=1"     : "";
                        $frt = (isset($_GET['FromTime'])) ? "&FromTime=" . $_GET['FromTime'] : "";
                        $tit = (isset($_GET['TillTime'])) ? "&TillTime=" . $_GET['TillTime'] : "";
                        $qtn = (isset($_GET['qt']))       ? "&qt="       . $_GET['qt']       : "";
                        echo "<a href=\"circulation.php?$searchStr$rev$frt$tit$urt&rows=$boxRow&page=$i$qtn\">$i</a>";
                    } else {
                        echo $i;
                    }
                    echo "</div>";
                }
            }
            echo "</div>";
        }
    ?>

    <!--修改及刪除資料所用的彈窗表單-->
    <div id="CirculationEditBackground" class="ModalBackground">
        <div id="CirculationEditContent" class="ModalContent">

            <!--彈窗標題-->
            <h2 id="CirculationEditTitle" class="EditTitle"></h2>

            <!--彈窗右上角的關閉按鈕（×）-->
            <span id="CirculationModalClose" class="ModalClose" onclick="modal_close()">&times;</span>

            <!--刪除彈窗訊息-->
            <p id="DeleteMessage">確定要刪除這筆借還書紀錄嗎？</p>

            <!--資料表單-->
            <form action="" method="POST" id="CirculationModalForm" autocomplete="off" onsubmit="reveal()">
                <input type="text" name="cId" id="cId" class="CirculationDataID" readonly>

                <table id="CirculationModalFrame" class="ModalFrame">
                    <col style="width: 18%">
                    <col style="width: 5%">
                    <col style="width: 77%">
                    <tr>
                        <td class="fieldLabel">書號</td>
                        <td>：</td>
                        <td><input type="text" name="cBookNumber" id="cBookNumber" required onkeyup="show_book(this.value, 'cBookName')" pattern="^[ABCabc]\d{4}"></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">書名</td>
                        <td>：</td>
                        <td><input type="text" name="cBookName" id="cBookName"></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">會號</td>
                        <td>：</td>
                        <td><input type="text" name="cMemberNumber" id="cMemberNumber" required onkeyup="show_member(this.value, 'cMemberName')"></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">會員姓名</td>
                        <td>：</td>
                        <td><input type="text" name="cMemberName" id="cMemberName"></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">借閱日期</td>
                        <td>：</td>
                        <td><input type="text" name="cBorrowTime" id="cBorrowTime"></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">歸還日期</td>
                        <td>：</td>
                        <td><input type="text" name="cReturnTime" id="cReturnTime"></td>
                    </tr>
                </table>

                <!--確認及取消按鈕-->
                <div class="Pack">
                    <input type="submit" name="eConfirm" id="eConfirm" class="ModalButton" value="確定">
                    <input type="button" name="eCancel" id="eCancel" class="ModalButton" value="取消" onclick="modal_close()">
                    <input type="hidden" name="PageNumber" id="PageNumber" value="<?php echo $page_number; ?>">
                    <input type="hidden" name="ScrollView" id="ScrollView">
                    <input type="hidden" name="PassFlag" id="PassFlag">
                </div>
            </form>
        </div>
    </div>

    <!--個人借還書處理表單-->
    <div id="CirculationDealBackground" class="ModalBackground">
        <div id="CirculationDealContent" class="ModalContent">

            <!--彈窗標題-->
            <h2 id="CirculationDealTitle" class="EditTitle">借還書處理</h2>

            <!--彈窗右上角的關閉按鈕（×）-->
            <span id="CirculationDealModalClose" class="ModalClose" onclick="modal_close()">&times;</span>

            <!--資料表單-->
            <form action="" method="POST" id="CirculationDealForm" autocomplete="off">
                <table id="CirculationDealFrame">
                    <tr>
                        <td class="greyTitle">會號</td>
                        <td class="inputTitle"><input type="text" name="cbMn" id="cbMn" onkeyup="show_member(value, 'cbMm'); circultion_display_num(value)" required pattern="^[Ss\d]\d{0,3}" tabindex="1"></td>
                        <td class="greyTitle">姓名</td>
                        <td class="inputTitle"><input type="text" name="cbMm" id="cbMm" readonly><span id="memberStatus" style="display: none"></span><span id="memberSpecs" style="display: none"></span></td>
                    </tr>
                </table>

                <!--確認及取消按鈕-->
                <div id="cjPack" class="Pack">
                    <input type="submit" name="aConfirm" id="aConfirm" class="ModalButton" tabindex="9" value="確定">
                    <input type="button" name="aCancel" id="aCancel" class="ModalButton" tabindex="10" value="取消" onclick="modal_close()">
                    <input type="hidden" name="aPassFlag" id="aPassFlag">
                </div>
            </form>
        </div>
    </div>

    <!--底部版權版本資訊-->
    <div id="includePage"></div>

    <script src="circulation.js"></script>
</body>
</html>
<?php mysqli_close($db_link); ?>
