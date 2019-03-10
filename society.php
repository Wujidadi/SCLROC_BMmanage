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

    /*資料庫查詢設定－查詢所有團體會員*/
    $total_query   = 'SELECT * FROM `society`';                                 //設定搜尋條件為所有團體會員
    $total_result  = mysqli_query($db_link, $total_query);                      //查詢所有團體會員
    $total_number  = mysqli_num_rows($total_result);                            //計算團體會員總數

    /*資料庫查詢設定－查詢在籍團體會員*/
    $active_query  = 'SELECT * FROM `society` WHERE `sOf` != 1';                //剔除不在籍團體會員
    $active_result = mysqli_query($db_link, $active_query);                     //查詢所有在籍團體會員
    $active_number = mysqli_num_rows($active_result);                           //計算在籍團體會員總數

    /*勾選「含已退會或未續會團體會員」時搜尋所有團體會員，否則僅搜尋在籍團體會員*/
    if (isset($_GET['osi']) && $_GET['osi'] == 1) {
        $extant_query   = $total_query;
        $extant_result  = $total_result;
        $extant_number  = $total_number;
        $searchFinalStr = "";
    } else {
        $extant_query  = $active_query;
        $extant_result = $active_result;
        $extant_number = $active_number;
        $searchFinalStr = " AND `sOf` != 1";
    }

    /*設定資料分頁*/
    include "ini.php";                                                          //引入寫在外部檔案內的預設每頁筆數
    $page_number = 1;                                                           //預設頁數
    if (isset($_GET['rows'])) {                                                 //若使用者已輸入每頁筆數
        if ($_GET['rows'] != '') {                                              //且此筆數不為空值
            $page_row = $_GET['rows'];                                          //依使用者所輸入的每頁筆數設置每頁顯示筆數
        } else if ($_GET['rows'] == '') {                                       //但若使用者輸入的筆數為空值
            $_GET['rows'] = $page_row_society;                                  //以預設每頁筆數取代該空值
            $page_row = $_GET['rows'];                                          //同時以此預設每頁筆數為準來顯示本頁資料
        }
    } else {
        $page_row = $page_row_society;                                          //若使用者根本沒有輸入每頁筆數，直接套用外部檔案的預設每頁筆數
    }
    if ((isset($_GET['page'])) && ($_GET['page'] != '')) {
        $page_number = $_GET['page'];                                           //依使用者所選頁數值更新頁數
    } else if (isset($_POST['PageNumber'])) {
        $page_number = $_POST['PageNumber'];                                    //依彈窗表單送出時所在頁面更新頁數
    } else {
        $page_number = 1;                                                       //直接令頁數為 1
    }
    $start_row    = $page_row * ($page_number - 1);                             //設定每頁從第 N 筆開始顯示，N = (頁數 - 1) * 每頁筆數
    $limit_query  = $extant_query . ' LIMIT ' . $start_row . ', ' . $page_row;  //查詢語法加上限制筆數，由第 N 筆起顯示所設定的筆數
    $limit_result = mysqli_query($db_link, $limit_query);                       //依限制條件查詢
    $extant_pages = ceil($extant_number / $page_row);                           //計算會員資料總頁數 = (查詢筆數 ÷ 每頁筆數) 並無條件進位

    /*取得當前資料庫中「ID」的最大值*/
    $id_max_query = 'SELECT MAX(`sId`) FROM `society`';
    $id_max_result = mysqli_query($db_link, $id_max_query);
    $id_max = @mysqli_fetch_row($id_max_result);

    /*取得當前資料庫中「會號」的最大值（不含前綴「S」*/
    $num_max_query = 'SELECT MAX(CAST(SUBSTRING(`sNb`, 2, 2) AS INT)) FROM `society`';
    $num_max_result = mysqli_query($db_link, $num_max_query);
    $num_max = @mysqli_fetch_row($num_max_result);

    $todaytime = strtotime(date("Y-m-d"));
    $yeartosec = 86400 * 365;

    if (isset($_POST['PassFlag'])) {
        /*將彈窗表單傳回的各項值，重新命名為名稱稍短、且可直接在雙引號中引用的變數，順便解決了有些值為 NULL 的問題*/
        $PsId = $_POST['sId'];
        $PsNumber = ($_POST['sNumber'] == '') ? 'NULL' : "'" . $_POST['sNumber'] . "'";
        $PsOldNumber = ($_POST['sOldNumber'] == '') ? 'NULL' : "'" . $_POST['sOldNumber'] . "'";
        $PsNumberNote = ($_POST['sNumberNote'] == '') ? 'NULL' : "'" . $_POST['sNumberNote'] . "'";
        $PsName = ($_POST['sName'] == '') ? 'NULL' : "'" . $_POST['sName'] . "'";
        $PsForShort = ($_POST['sForShort'] == '') ? 'NULL' : "'" . $_POST['sForShort'] . "'";
        $PsUnifiedCode = ($_POST['sUnifiedCode'] == '') ? 'NULL' : "'" . $_POST['sUnifiedCode'] . "'";
        $PsZipCode = ($_POST['sZipCode'] == '') ? 'NULL' : "'" . $_POST['sZipCode'] . "'";
        $PsAddress = ($_POST['sAddress'] == '') ? 'NULL' : "'" . $_POST['sAddress'] . "'";
        $PsOfficeNumber1 = ($_POST['sOfficeNumber1'] == '') ? 'NULL' : "'" . $_POST['sOfficeNumber1'] . "'";
        $PsOfficeNumber2 = ($_POST['sOfficeNumber2'] == '') ? 'NULL' : "'" . $_POST['sOfficeNumber2'] . "'";
        $PsFaxNumber = ($_POST['sFaxNumber'] == '') ? 'NULL' : "'" . $_POST['sFaxNumber'] . "'";
        $PsPiCName = ($_POST['sPiCName'] == '') ? 'NULL' : "'" . $_POST['sPiCName'] . "'";
        $PsPiCTitle = ($_POST['sPiCTitle'] == '') ? 'NULL' : "'" . $_POST['sPiCTitle'] . "'";
        $PsPiCNote = ($_POST['sPiCNote'] == '') ? 'NULL' : "'" . $_POST['sPiCNote'] . "'";
        $PsRpnName = ($_POST['sRpnName'] == '') ? 'NULL' : "'" . $_POST['sRpnName'] . "'";
        $PsRpnTitle = ($_POST['sRpnTitle'] == '') ? 'NULL' : "'" . $_POST['sRpnTitle'] . "'";
        $PsRpnNote = ($_POST['sRpnNote'] == '') ? 'NULL' : "'" . $_POST['sRpnNote'] . "'";
        $PsRpnExt = ($_POST['sRpnExt'] == '') ? 'NULL' : "'" . $_POST['sRpnExt'] . "'";
        $PsRpnPhone = ($_POST['sRpnPhone'] == '') ? 'NULL' : "'" . $_POST['sRpnPhone'] . "'";
        $PsRpnEmail = ($_POST['sRpnEmail'] == '') ? 'NULL' : "'" . $_POST['sRpnEmail'] . "'";
        $PsLs1Name = ($_POST['sLs1Name'] == '') ? 'NULL' : "'" . $_POST['sLs1Name'] . "'";
        $PsLs1Title = ($_POST['sLs1Title'] == '') ? 'NULL' : "'" . $_POST['sLs1Title'] . "'";
        $PsLs1Note = ($_POST['sLs1Note'] == '') ? 'NULL' : "'" . $_POST['sLs1Note'] . "'";
        $PsLs1Ext = ($_POST['sLs1Ext'] == '') ? 'NULL' : "'" . $_POST['sLs1Ext'] . "'";
        $PsLs1Phone = ($_POST['sLs1Phone'] == '') ? 'NULL' : "'" . $_POST['sLs1Phone'] . "'";
        $PsLs1Email = ($_POST['sLs1Email'] == '') ? 'NULL' : "'" . $_POST['sLs1Email'] . "'";
        $PsLs2Name = ($_POST['sLs2Name'] == '') ? 'NULL' : "'" . $_POST['sLs2Name'] . "'";
        $PsLs2Title = ($_POST['sLs2Title'] == '') ? 'NULL' : "'" . $_POST['sLs2Title'] . "'";
        $PsLs2Note = ($_POST['sLs2Note'] == '') ? 'NULL' : "'" . $_POST['sLs2Note'] . "'";
        $PsLs2Ext = ($_POST['sLs2Ext'] == '') ? 'NULL' : "'" . $_POST['sLs2Ext'] . "'";
        $PsLs2Phone = ($_POST['sLs2Phone'] == '') ? 'NULL' : "'" . $_POST['sLs2Phone'] . "'";
        $PsLs2Email = ($_POST['sLs2Email'] == '') ? 'NULL' : "'" . $_POST['sLs2Email'] . "'";
        $PsLs3Name = ($_POST['sLs3Name'] == '') ? 'NULL' : "'" . $_POST['sLs3Name'] . "'";
        $PsLs3Title = ($_POST['sLs3Title'] == '') ? 'NULL' : "'" . $_POST['sLs3Title'] . "'";
        $PsLs3Note = ($_POST['sLs3Note'] == '') ? 'NULL' : "'" . $_POST['sLs3Note'] . "'";
        $PsLs3Ext = ($_POST['sLs3Ext'] == '') ? 'NULL' : "'" . $_POST['sLs3Ext'] . "'";
        $PsLs3Phone = ($_POST['sLs3Phone'] == '') ? 'NULL' : "'" . $_POST['sLs3Phone'] . "'";
        $PsLs3Email = ($_POST['sLs3Email'] == '') ? 'NULL' : "'" . $_POST['sLs3Email'] . "'";
        $PsJoinday = ($_POST['sJoinday'] == '') ? 'NULL' : "'" . $_POST['sJoinday'] . "'";
        $PsJoindayFlag = ($_POST['sJoindayFlag'] == '') ? 'NULL' : "'" . $_POST['sJoindayFlag'] . "'";
        $PsLastPayDay = ($_POST['sLastPayDay'] == '') ? 'NULL' : "'" . $_POST['sLastPayDay'] . "'";
        if ($_POST['sLastPayDay'] == '') {
            $PsLastPayDay = 'NULL';
            $PsOutFlagDate = 0;
        } else {
            $PsLastPayDay = "'" . $_POST['sLastPayDay'] . "'";
            $PsOutFlagDate = $todaytime - strtotime($_POST['sLastPayDay']);
        }
        $PsNote = ($_POST['sNote'] == '') ? 'NULL' : "'" . str_replace(chr(13).chr(10), "<br>", $_POST['sNote']) . "'";
        $PsOutFlag = ((isset($_POST['sOutFlag'])) && ($_POST['sOutFlag'] == 1)) ? 1 : 0;

        /*傳值旗標為「修改」時更新資料庫*/
        if ($_POST['PassFlag'] == '修改') {
            /*修改團體會員資料表`society`中的資料*/
            $edit_query  = "UPDATE `society` SET ";
            $edit_query .= "`sNb` = $PsNumber, ";
            $edit_query .= "`sNbOd` = $PsOldNumber, ";
            $edit_query .= "`sNbNt` = $PsNumberNote, ";
            $edit_query .= "`sNm` = $PsName, ";
            $edit_query .= "`sFs` = $PsForShort, ";
            $edit_query .= "`sUc` = $PsUnifiedCode, ";
            $edit_query .= "`sZc` = $PsZipCode, ";
            $edit_query .= "`sAd` = $PsAddress, ";
            $edit_query .= "`sOn1` = $PsOfficeNumber1, ";
            $edit_query .= "`sOn2` = $PsOfficeNumber2, ";
            $edit_query .= "`sFn` = $PsFaxNumber, ";
            $edit_query .= "`sPcNm` = $PsPiCName, ";
            $edit_query .= "`sPcTt` = $PsPiCTitle, ";
            $edit_query .= "`sPcNt` = $PsPiCNote, ";
            $edit_query .= "`sRpNm` = $PsRpnName, ";
            $edit_query .= "`sRpTt` = $PsRpnTitle, ";
            $edit_query .= "`sRpNt` = $PsRpnNote, ";
            $edit_query .= "`sRpEx` = $PsRpnExt, ";
            $edit_query .= "`sRpPn` = $PsRpnPhone, ";
            $edit_query .= "`sRpEa` = $PsRpnEmail, ";
            $edit_query .= "`sLs1Nm` = $PsLs1Name, ";
            $edit_query .= "`sLs1Tt` = $PsLs1Title, ";
            $edit_query .= "`sLs1Nt` = $PsLs1Note, ";
            $edit_query .= "`sLs1Ex` = $PsLs1Ext, ";
            $edit_query .= "`sLs1Pn` = $PsLs1Phone, ";
            $edit_query .= "`sLs1Ea` = $PsLs1Email, ";
            $edit_query .= "`sLs2Nm` = $PsLs2Name, ";
            $edit_query .= "`sLs2Tt` = $PsLs2Title, ";
            $edit_query .= "`sLs2Nt` = $PsLs2Note, ";
            $edit_query .= "`sLs2Ex` = $PsLs2Ext, ";
            $edit_query .= "`sLs2Pn` = $PsLs2Phone, ";
            $edit_query .= "`sLs2Ea` = $PsLs2Email, ";
            $edit_query .= "`sLs3Nm` = $PsLs3Name, ";
            $edit_query .= "`sLs3Tt` = $PsLs3Title, ";
            $edit_query .= "`sLs3Nt` = $PsLs3Note, ";
            $edit_query .= "`sLs3Ex` = $PsLs3Ext, ";
            $edit_query .= "`sLs3Pn` = $PsLs3Phone, ";
            $edit_query .= "`sLs3Ea` = $PsLs3Email, ";
            $edit_query .= "`sJd` = $PsJoinday, ";
            $edit_query .= "`sJdFg` = $PsJoindayFlag, ";
            $edit_query .= "`sNt` = $PsNote, ";
            $edit_query .= "`sPd` = $PsLastPayDay, ";
            $edit_query .= "`sOf` = $PsOutFlag ";
            $edit_query .= "WHERE `sId` = $PsId";
            //echo $edit_query;
            $edit_result = mysqli_query($db_link, $edit_query);

            /*修改會員清單資料表`mlist`中的資料*/
            if ($PsOutFlagDate > $yeartosec) {
                $edit_list_query  = "UPDATE `mlist` SET `msNm` = $PsName, `msFg` = 1, `msOf` = '1' WHERE `msNb` = $PsNumber";
            } else if ($PsOutFlagDate > 0 && $PsOutFlagDate < $yeartosec) {
                $edit_list_query  = "UPDATE `mlist` SET `msNm` = $PsName, `msFg` = 0, `msOf` = '2' WHERE `msNb` = $PsNumber";
            } else {
                $edit_list_query  = "UPDATE `mlist` SET `msNm` = $PsName, `msFg` = 0, `msOf` = '0' WHERE `msNb` = $PsNumber";
            }
            //echo $edit_list_query;
            $edit_list_result = mysqli_query($db_link, $edit_list_query);

            /*更新借還書紀錄資料表*/
            $change_select_query  = "UPDATE `circulation` SET `cMm` = $PsName WHERE `cMn` = $PsNumber";
            $change_select_result = mysqli_query($db_link, $change_select_query);
        }
        /*傳值旗標為「新增」時新增資料到資料庫*/
        else if ($_POST['PassFlag'] == '新增') {
            /*新增完整資料到團體會員資料表`society`*/
            $insert_query  = "INSERT INTO `society` (`sId`, `sNb`, `sNbOd`, `sNbNt`, `sNm`, `sFs`, `sUc`, `sZc`, `sAd`, `sOn1`, `sOn2`, `sFn`, `sPcNm`, `sPcTt`, `sPcNt`, `sRpNm`, `sRpTt`, `sRpNt`, `sRpEx`, `sRpPn`, `sRpEa`, `sLs1Nm`, `sLs1Tt`, `sLs1Nt`, `sLs1Ex`, `sLs1Pn`, `sLs1Ea`, `sLs2Nm`, `sLs2Tt`, `sLs2Nt`, `sLs2Ex`, `sLs2Pn`, `sLs2Ea`, `sLs3Nm`, `sLs3Tt`, `sLs3Nt`, `sLs3Ex`, `sLs3Pn`, `sLs3Ea`, `sJd`, `sJdFg`, `sNt`, `sPd`, `sOf`) VALUES ";
            $insert_query .= "(NULL, $PsNumber, $PsOldNumber, $PsNumberNote, $PsName, $PsForShort, $PsUnifiedCode, $PsZipCode, $PsAddress, $PsOfficeNumber1, $PsOfficeNumber2, $PsFaxNumber, $PsPiCName, $PsPiCTitle, $PsPiCNote, $PsRpnName, $PsRpnTitle, $PsRpnNote, $PsRpnExt, $PsRpnPhone, $PsRpnEmail, $PsLs1Name, $PsLs1Title, $PsLs1Note, $PsLs1Ext, $PsLs1Phone, $PsLs1Email, $PsLs2Name, $PsLs2Title, $PsLs2Note, $PsLs2Ext, $PsLs2Phone, $PsLs2Email, $PsLs3Name, $PsLs3Title, $PsLs3Note, $PsLs3Ext, $PsLs3Phone, $PsLs3Email, $PsJoinday, $PsJoindayFlag, $PsNote, $PsLastPayDay, $PsOutFlag)";
            //echo $insert_query;
            $insert_result = mysqli_query($db_link, $insert_query);

            /*僅新增會號及姓名到會員清單資料表`mlist`*/
            $insert_list_query  = "INSERT INTO `mlist` (`msId`, `msNb`, `msNm`, `msFg`) VALUES (NULL, $PsNumber, $PsForShort, 1)";
            //echo $insert_list_query;
            $insert_list_result = mysqli_query($db_link, $insert_list_query);
        }
        /*傳值旗標為「刪除」時刪除該筆資料*/
        else if ($_POST['PassFlag'] == '刪除') {
            /*從團體會員資料表`society`中刪除資料*/
            $delete_query  = "DELETE FROM `society` WHERE `sId` = $PsId";
            //echo $delete_query;
            $delete_result = mysqli_query($db_link, $delete_query);

            /*從會員清單資料表`mlist`中刪除資料*/
            $delete_list_query  = "DELETE FROM `mlist` WHERE `msNb` = $PsNumber";
            //echo $delete_list_query;
            $delete_list_result = mysqli_query($db_link, $delete_list_query);
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

    include "flagItem.php";                                                     //引入附註及旗標選單值檔案
    include "searchItem.php";                                                   //引入搜尋選單值檔案

    $Search_r = (isset($_GET['Search'])) ? $_GET['Search'] : "";
    $Option_r = (isset($_GET['Option'])) ? $_GET['Option'] : "all";
    $sLen = strlen($Search_r);
    if (preg_match("/ {{$sLen}}/", $Search_r)) {
        $Search_r = $_GET['Search'] = "";                                       //若使用者輸入字串全為空白，視同無搜尋字串
    }

    /*若使用者有設定搜尋條件，計算符合條件的筆數及頁數*/
    /*特別處理搜尋為「入會日期」時的 SQL 命令字串內容*/
    $jdk = 13;
    $societySearchItem[$jdk]['sql'] = '`sJd`';
    if (preg_match("/\d{4}-\d{2}-\d{2}/", $Search_r)) {
        $societySearchItem[$jdk]['sql_cd'] = ' AND (`sJdFg` != \'noDay\' OR `sJdFg` IS NULL)';
    } else {
        $societySearchItem[$jdk]['sql_cd'] = '';
    }
    /*入會日期、上次繳費日期的搜尋字串須特別處理，將其陣列索引值先提取出來*/
    $dateOption = array(13, 14);

    /*若使用者先已選定搜尋選項，下拉式搜尋選單顯示為該選項*/
    for ($i = 0; $i < count($societySearchItem ); $i++) {
        if ($Option_r == $societySearchItem [$i]['str']) {
            if (in_array($i, $dateOption)) {
                /*轉換使用者輸入日期中的「/」或「.」為「-」*/
                $Search_r = str_replace(array("/", "."), "-", $Search_r);
                /*使用者輸入的月或日不足 2 位數時補零*/
                if (preg_match("/\d{4}-\d{1,2}-\d{1,2}/", $Search_r)) {
                    $dateanlz = sscanf($Search_r, "%d-%d-%d");
                    $dateanlz[1] = str_pad($dateanlz[1], 2, "0", STR_PAD_LEFT);
                    $dateanlz[2] = str_pad($dateanlz[2], 2, "0", STR_PAD_LEFT);
                    $Search_r = implode("-", $dateanlz);
                } else if (preg_match("/\d{4}-\d{1,2}/", $Search_r)) {
                    $dateanlz = sscanf($Search_r, "%d-%d");
                    $dateanlz[1] = str_pad($dateanlz[1], 2, "0", STR_PAD_LEFT);
                    $Search_r = implode("-", $dateanlz);
                } else if (preg_match("/\d{1,2}-\d{1,2}/", $Search_r)) {
                    $dateanlz = sscanf($Search_r, "%d-%d");
                    $dateanlz[0] = str_pad($dateanlz[0], 2, "0", STR_PAD_LEFT);
                    $dateanlz[1] = str_pad($dateanlz[1], 2, "0", STR_PAD_LEFT);
                    $Search_r = implode("-", $dateanlz);
                } else {
                    $Search_r = $Search_r;
                }
            }
            $searchWords = $societySearchItem[$i]['sql'];
            $searchSuffix = $societySearchItem[$i]['sql_cd'];
        }
    }

    /*結合搜尋條件的查詢語法*/
    $searchPrefix = "SELECT * FROM `society` WHERE ";
    if ($Search_r != "") {
        /*防範隱碼攻擊*/
        $search_keyword = filter_var($Search_r, FILTER_SANITIZE_MAGIC_QUOTES);

        if ($Option_r == 'num') {
            $searchTarget = " LIKE '" . $search_keyword . "'";
        } else {
            $searchTarget = " LIKE '%" . $search_keyword . "%'";
        }
    } else {
        $search_keyword = "";
        $searchWords = "1";
        $searchTarget = "";
    }
    $search_query = $searchPrefix . $searchWords . $searchTarget . $searchSuffix . $searchFinalStr;

    /*測試語法是否正確*/
    //echo $search_query . "<br>" . $Option_r . "<br>" . $searchWords . "<br>";

    $search_result = mysqli_query($db_link, $search_query);
    $search_number = mysqli_num_rows($search_result);
    $search_pages  = ceil($search_number / $page_row);                  //計算符合搜尋條件的會員總數 = (結果數 ÷ 每頁筆數) 並無條件進位

    if ($start_row > $search_number) {
        if ($search_pages > 1) {
            $start_row = $page_row * ($page_number - 2);
        } else {
            $start_row = 0;
        }
    }

    /*搜尋條件加上筆數及頁數限制*/
    $search_limit_query  = $search_query . ' LIMIT ' . $start_row . ', ' . $page_row;
    //echo $search_limit_query . "<br>";
    $search_limit_result = mysqli_query($db_link, $search_limit_query);
    $search_limit_number = mysqli_num_rows($search_limit_result);

    $toDay = date("Y-m-d");
?>

<!DOCTYPE html>
<html>
<head>
    <!--宣告 html 頁面編碼-->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <!--宣告為 RWD 響應式網頁-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--宣告頁面標題，依會員人數動態變化-->
    <title>團體會員資料管理 (現有<?php echo $extant_number; ?>個團體會員)</title>

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
<body id="societyBody" onload="<?php if (isset($_POST['PassFlag'])) echo "scrollView('" . $_POST['ScrollView'] . "'); "; ?>error_display(); hide_text()" onkeydown="modal_esc(event)">
    <?php
        if (isset($_POST['PassFlag'])) unset($_POST['PassFlag']);               //清除傳值旗標
    ?>

    <!--隱藏區域，存放 php 拋出的錯誤訊息，以利 JavaScript 存取-->
    <p id="ErrorInfo">
        <?php
            if (isset($error_number) && ($error_number) != 0) echo $error_number;
            else echo "No Error!";
        ?>
    </p>

    <!--隱藏區域，存放目前資料庫中「ID」的最大值-->
    <p id="IDMax"><?php echo $id_max[0]; ?></p>

    <h1 id="TitleMember">團體會員資料管理</h1>

    <!--新增會員資料按鈕-->
    <?php
        $defaultScriptStr = "";
        for ($i = 1; $i < 42; $i++) {
            $defaultScriptStr .= "'', ";
        }
        $defaultScriptStr .= "0";
        //echo $defaultScriptStr;
        $defaultScriptIndex = ($id_max[0] + 1) . ", 'S" . ($num_max[0] + 1) . "', " . $defaultScriptStr;
        //echo $defaultScriptIndex

        if ($adminFlag == "true") {
            echo "<img id=\"addSocietyButton\" src=\"insert_button.png\" onmouseout=\"this.src='insert_button.png'\" onmouseover=\"this.src='insert_button_invert.png'\" onclick=\"modal_display('新增', " . $defaultScriptIndex . "); modal_status('新增')\">";
        }
    ?>
    <?php if ($adminFlag == "true") { ?>
        <!--選擇筆數、頁數及搜尋表單-->
        <form action="" method="GET" id="SocietySearcher">
            <table id="societySearchBox">
                <tr style="height: 2em">
                    <td colspan="2" style="text-align: center">
                        <!--搜尋功能-->
                        <input type="text" id="sSearch" name="Search" value="<?php if (isset($_GET["Search"])) echo $_GET["Search"]; ?>">
                        <select id="sOption" name="Option">
                            <?php
                                /*在下拉式選單中依序列出搜尋條件*/
                                for ($i = 0; $i < count($societySearchItem); $i++) {
                                    echo "<option value=\"" . $societySearchItem[$i]['str'] . "\"";
                                    if ((isset($_GET["Option"])) && ($societySearchItem[$i]['str'] == $_GET["Option"])) echo "selected";
                                    echo ">" . $societySearchItem[$i]['item'] . "</option>";
                                }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-size: 0pt; text-align: left">
                        <label style="font-size: 0.85rem"><input type="checkbox" name="osi" id="outSocietyIncluded" value="1" <?php if ((isset($_GET['osi']) && ($_GET['osi'] == 1))) echo "checked"; ?>>含已退會或未續會團體會員</label>
                    </td>
                    <td style="width: 34%; text-align: center">
                        <input id="sSubmit" type="submit" value="搜尋">
                    </td>
                </tr>
                <tr>
                    <td id="societyRowsAndPages" colspan="2">
                        <!--選擇筆數及頁數功能-->
                        <?php
                            /*未設定搜尋條件時，使用所有會員資料的筆數及頁數，否則使用符合搜尋條件的會員資料之筆數及頁數，來展示頁面*/
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
                            // echo "，要去第 <input type=\"number\" name=\"page\" id=\"page\" min=\"1\" value=\"";
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
                            if ((isset($_GET['rows'])) && ($_GET['rows'] != '') && ($_GET['rows'] != $page_row_society)) {
                                $init = file("ini.php") or die("Unable to open file!");             //將外部設定檔的內容取出為陣列
                                $init[8] = "    \$page_row_society = " . $_GET['rows'] . ";\n";     //改寫預設頁數的初始值
                                $inittext = implode('', $init);                                     //將取出的陣列重新組合在一起
                                file_put_contents("ini.php", $inittext);                            //寫入修改過的檔案內容
                            }
                        ?>
                    </td>
                </tr>
            </table>
        </form>
    <?php } ?>

    <?php
        // /*更改搜尋條件時，若原先的所在頁數超過新搜尋結果的總頁數，顯示警告訊息並提示*/
        // if ($page_number > $result_pages) {
        //     echo "<div class=\"overPage\">當前所在頁數超過搜索總頁數！<br>請再按一次「搜尋」就可以跳到搜索結果第 1 頁囉～</div>";
        // };

        /*未設定搜尋條件時，顯示所有仍在籍的團體會員資料，否則顯示符合搜尋條件的團體會員資料*/
        // if ((!isset($_GET['Search']) || ($_GET['Search'] == '')) || (!isset($_GET['Option']) || ($_GET['Option'] == ''))) {
        //     $display_result = $limit_result;
        // } else {
            $display_result = $search_limit_result;
            //$display_result = $extant_result;
        //}

        $dateSeparator = "/";                                   //年月日分隔字元
        $mgFlag = 1;                                            //曆法旗標（0 = 西元紀年，1 = 民國紀年）

        /*已登入管理者身分時才顯示資料，否則顯示禁止瀏覽訊息*/
        if ($adminFlag == "true") {
            /*搜尋結果 > 0 時才顯示表格*/
            if (mysqli_num_rows($display_result) > 0) {
                /*以表格顯示會員資料*/
                echo "<table id=\"SocietyData\">
                    <!--各欄-->
                    <col id=\"sControlCol\">
                    <col id=\"sNumberCol\">
                    <col id=\"sNameCol\">
                    <col id=\"sShortNameCol\">
                    <col id=\"sUCodeCol\">
                    <col id=\"sAddressCol\">
                    <col id=\"sOfficeNumberCol\">
                    <col id=\"sFaxNumberCol\">
                    <col id=\"sPiCCol\">
                    <col id=\"sRepresentCol\">
                    <col id=\"sRepresentExtCol\">
                    <col id=\"sRepresentPhoneCol\">
                    <col id=\"sRepresentEmailCol\">
                    <col id=\"sLiaisonCol\">
                    <col id=\"sLiaisonExtCol\">
                    <col id=\"sLiaisonPhoneCol\">
                    <col id=\"sLiaisonEmailCol\">
                    <col id=\"sJoindayCol\">
                    <col id=\"sEdCol\">
                    <col id=\"sNoteCol\">

                    <!--表頭-->
                    <thead>
                        <tr id=\"Hd\">
                            <th id=\"sControlHd\"></th>
                            <th id=\"sNumberHd\">會號</th>
                            <th id=\"sNameHd\">名稱</th>
                            <th id=\"sShortNameHd\">簡稱</th>
                            <th id=\"sUCodeHd\">統編</th>
                            <th id=\"sAddressHd\">地址</th>
                            <th id=\"sOfficeNumberHd\">電話</th>
                            <th id=\"sFaxNumberHd\">傳真</th>
                            <th id=\"sPiCHd\">負責人</th>
                            <th id=\"sRepresentHd\">代表人</th>
                            <th id=\"sRepresentExtHd\">分機</th>
                            <th id=\"sRepresentPhoneHd\">手機</th>
                            <th id=\"sRepresentEmailHd\">Email</th>
                            <th id=\"sLiaisonHd\">聯絡人</th>
                            <th id=\"sLiaisonExtHd\">分機</th>
                            <th id=\"sLiaisonPhoneHd\">手機</th>
                            <th id=\"sLiaisonEmailHd\">Email</th>
                            <th id=\"sJoindayHd\">入會日期</th>
                            <th id=\"sEdHd\">會籍到期</th>
                            <th id=\"sNoteHd\">備註</th>
                        </tr>
                    </thead>

                    <tbody>";
                        /*將會員資料依限制條件分頁後輸出為陣列*/
                        while ($row = mysqli_fetch_assoc($display_result)) {
                            /*切割會員入會日期為年、月、日並計算民國紀年*/
                            $jdYear   = substr($row['sJd'], 0, 4) . $dateSeparator;
                            $mgjdYear = (int)substr($row['sJd'], 0, 4) - 1911 . $dateSeparator;
                            $jdMonth  = substr($row['sJd'], 5, 2) . $dateSeparator;
                            $jdDay    = substr($row['sJd'], 8, 2);

                            /*處理會員入會日期的異常狀況*/
                            if ($row['sJdFg'] == 'onlyYear') {                      //僅有入會年的情況
                                $jdMonth = $jdDay = "";
                                $jdYear = str_replace($dateSeparator, "", $jdYear);
                                $mgjdYear = str_replace($dateSeparator, "", $mgjdYear);
                            } else if ($row['sJdFg'] == 'noDay') {                  //沒有入會日的情況
                                $jdDay = "";
                                $jdMonth = str_replace($dateSeparator, "", $jdMonth);
                            } else if ($row['sJd'] == '') {                         //入會日期為空的情況
                                $jdYear = $mgjdYear = $jdMonth = $jdDay = "";
                            }

                            $jdYear = ($mgFlag == 1) ? $mgjdYear : $jdYear;         //依據西元或民國旗標決定使用西元紀年或民國紀年
                            $joinday = $jdYear . $jdMonth . $jdDay;                 //串接入會年月日為字串

                            /*切割會籍到期日期為年、月、日並計算民國紀年*/
                            if ($row['sPd'] != '') {
                                $edYear   = substr($row['sPd'], 0, 4) . $dateSeparator;
                                $mgedYear = (int)substr($row['sPd'], 0, 4) - 1911 . $dateSeparator;
                                $edMonth  = substr($row['sPd'], 5, 2) . $dateSeparator;
                                $edDay    = substr($row['sPd'], 8, 2);

                                $edYear = ($mgFlag == 1) ? $mgedYear : $edYear;     //依據西元或民國旗標決定使用西元紀年或民國紀年
                                $edDate = $edYear . $edMonth . $edDay;              //串接會籍到期年月日為字串
                            } else {
                                $edDate = '';
                            }

                            /*英文郵遞區號不加在地址之前*/
                            $zipCode = (strlen(mb_substr($row['sAd'], 0, 1, 'utf8')) < 2) ? "" : $row['sZc'];

                            /*備註欄出現的日期依曆法旗標進行轉換*/
                            $noteTransDate = $row['sNt'];
                            $cemg = ($mgFlag == 1) ? 1911 : 0;
                            /*轉換「YYYY-mm-dd」格式的日期*/
                            if (preg_match_all("/\d{4}-\d{1,2}-\d{1,2}/", $noteTransDate, $noteFullDate)) {
                                foreach ($noteFullDate as $fullDate) {
                                    foreach ($fullDate as $key => $value) {
                                        $valueanlz = sscanf($value, "%d-%d-%d");
                                        $valueanlz[0] -= $cemg;
                                        /*月日補零*/
                                        $valueanlz[1]  = str_pad($valueanlz[1], 2, "0", STR_PAD_LEFT);
                                        $valueanlz[2]  = str_pad($valueanlz[2], 2, "0", STR_PAD_LEFT);
                                        $value_r = implode("/", $valueanlz);
                                        $noteTransDate = str_replace($value, $value_r, $noteTransDate);
                                    }
                                }
                            }
                            /*轉換「YYYY-mm」格式的日期*/
                            if (preg_match_all("/\d{4}-\d{1,2}/", $noteTransDate, $noteMonth)) {
                                foreach ($noteMonth as $month) {
                                    foreach ($month as $key => $valm) {
                                        $valueanlz = sscanf($valm, "%d-%d");
                                        $valueanlz[0] -= $cemg;
                                        /*月份補零*/
                                        $valueanlz[1]  = str_pad($valueanlz[1], 2, "0", STR_PAD_LEFT);
                                        $valm_r = implode("/", $valueanlz);
                                        $noteTransDate = str_replace($valm, $valm_r, $noteTransDate);
                                    }
                                }
                            }
                            /*轉換「YYYY 年」格式的日期*/
                            if (preg_match_all("/\d{4}年/", $noteTransDate, $noteYear)) {
                                foreach ($noteYear as $year) {
                                    foreach ($year as $key => $fullYr) {
                                        preg_match("/\d{4}/", $fullYr, $yrarray);
                                        foreach ($yrarray as $key => $val) {
                                            $valanlz = sscanf($val, "%d");
                                            $valanlz[0] -= $cemg;
                                            $val_r = implode("", $valanlz);
                                            $noteTransDate = str_replace($val, $val_r, $noteTransDate);
                                        }
                                    }
                                }
                            }

                            $data[] = array(                                        //會員資料各欄位轉為陣列
                                'ID'               => $row['sId'],
                                '會號'             => $row['sNb'],
                                '舊格式會號'       => $row['sNbOd'],
                                '會號附註'         => $row['sNbNt'],
                                '名稱'             => $row['sNm'],
                                '簡稱'             => $row['sFs'],
                                '統編'             => $row['sUc'],
                                '郵編'             => $zipCode,
                                '原始郵編'         => $row['sZc'],
                                '地址'             => $row['sAd'],
                                '電話1'            => $row['sOn1'],
                                '電話2'            => $row['sOn2'],
                                '傳真'             => $row['sFn'],
                                '負責人姓名'       => $row['sPcNm'],
                                '負責人頭銜'       => $row['sPcTt'],
                                '負責人附註'       => $row['sPcNt'],
                                '代表人姓名'       => $row['sRpNm'],
                                '代表人頭銜'       => $row['sRpTt'],
                                '代表人附註'       => $row['sRpNt'],
                                '代表人分機'       => $row['sRpEx'],
                                '代表人手機'       => $row['sRpPn'],
                                '代表人Email'      => $row['sRpEa'],
                                '聯絡人1姓名'      => $row['sLs1Nm'],
                                '聯絡人1頭銜'      => $row['sLs1Tt'],
                                '聯絡人1附註'      => $row['sLs1Nt'],
                                '聯絡人1分機'      => $row['sLs1Ex'],
                                '聯絡人1手機'      => $row['sLs1Pn'],
                                '聯絡人1Email'     => $row['sLs1Ea'],
                                '聯絡人2姓名'      => $row['sLs2Nm'],
                                '聯絡人2頭銜'      => $row['sLs2Tt'],
                                '聯絡人2附註'      => $row['sLs2Nt'],
                                '聯絡人2分機'      => $row['sLs2Ex'],
                                '聯絡人2手機'      => $row['sLs2Pn'],
                                '聯絡人2Email'     => $row['sLs2Ea'],
                                '聯絡人3姓名'      => $row['sLs3Nm'],
                                '聯絡人3頭銜'      => $row['sLs3Tt'],
                                '聯絡人3附註'      => $row['sLs3Nt'],
                                '聯絡人3分機'      => $row['sLs3Ex'],
                                '聯絡人3手機'      => $row['sLs3Pn'],
                                '聯絡人3Email'     => $row['sLs3Ea'],
                                '原始備註'         => $row['sNt'],
                                '備註'             => $noteTransDate,
                                '原始入會日期'     => $row['sJd'],
                                '入會日期'         => $joinday,
                                '入會日期旗標'     => $row['sJdFg'],
                                '原始會籍到期日期' => $row['sPd'],
                                '會籍到期日期'     => $edDate,
                                '退會旗標'         => $row['sOf'],
                                '更新時間'         => $row['sMt']
                            );
                        }

                        /*陣列長度*/
                        $length = count($data);

                        /*將取出的陣列資料輸出成表格*/
                        for ($i = 0; $i < $length; $i++) {
                            /*將會員資料各欄位串接成 modal_display() 的引數*/
                            $scriptIndex = $data[$i]['ID'] . ", '{$data[$i]['會號']}', '{$data[$i]['舊格式會號']}', '{$data[$i]['會號附註']}', '{$data[$i]['名稱']}', '{$data[$i]['簡稱']}', '{$data[$i]['統編']}', '{$data[$i]['原始郵編']}', '{$data[$i]['地址']}', '{$data[$i]['電話1']}', '{$data[$i]['電話2']}', '{$data[$i]['傳真']}', '{$data[$i]['負責人姓名']}', '{$data[$i]['負責人頭銜']}', '{$data[$i]['負責人附註']}', '{$data[$i]['代表人姓名']}', '{$data[$i]['代表人頭銜']}', '{$data[$i]['代表人附註']}', '{$data[$i]['代表人分機']}', '{$data[$i]['代表人手機']}', '{$data[$i]['代表人Email']}', '{$data[$i]['聯絡人1姓名']}', '{$data[$i]['聯絡人1頭銜']}', '{$data[$i]['聯絡人1附註']}', '{$data[$i]['聯絡人1分機']}', '{$data[$i]['聯絡人1手機']}', '{$data[$i]['聯絡人1Email']}', '{$data[$i]['聯絡人2姓名']}', '{$data[$i]['聯絡人2頭銜']}', '{$data[$i]['聯絡人2附註']}', '{$data[$i]['聯絡人2分機']}', '{$data[$i]['聯絡人2手機']}', '{$data[$i]['聯絡人2Email']}', '{$data[$i]['聯絡人3姓名']}', '{$data[$i]['聯絡人3頭銜']}', '{$data[$i]['聯絡人3附註']}', '{$data[$i]['聯絡人3分機']}', '{$data[$i]['聯絡人3手機']}', '{$data[$i]['聯絡人3Email']}', '{$data[$i]['原始入會日期']}', '{$data[$i]['入會日期旗標']}', '{$data[$i]['原始會籍到期日期']}', '{$data[$i]['原始備註']}', '{$data[$i]['退會旗標']}'";

                            echo "
                            <tr id=\"society" . $data[$i]['ID'] . "\">";        /*每列賦予 ID*/
                                echo "<td class=\"sControlCl\">
                                    <span class=\"TooltipBox\">
                                        <img src=\"edit.png\" alt=\"修改\" class=\"ImageButton\" onclick=\"modal_display('修改', $scriptIndex)\" onmouseover=\"modal_status('修改', $scriptIndex); getWindowTop()\">
                                        <span class=\"TooltipText\">修改</span>
                                    </span>
                                    <span class=\"TooltipBox\">
                                        <img src=\"delete.png\" alt=\"刪除\" class=\"ImageButton\" onclick=\"modal_display('刪除', $scriptIndex)\" onmouseover=\"modal_status('刪除', $scriptIndex); getWindowTop()\">
                                        <span class=\"TooltipText\">刪除</span>
                                    </span>
                                </td>";
                                if ($data[$i]['會號'] == "" && $data[$i]['舊格式會號'] != "") {
                                    $snum = $data[$i]['舊格式會號'];
                                    $snumjs = $snum;
                                    $snumft = " class=\"redSnum\"";
                                } else if ($data[$i]['會號'] != "" && $data[$i]['舊格式會號'] != "") {
                                    $snum = $data[$i]['會號'] . "<br><span class=\"redSnum\">" . $data[$i]['舊格式會號'] . "</span>";
                                    $snumjs = $data[$i]['會號'];
                                    $snumft = "";
                                } else {
                                    $snum = $data[$i]['會號'];
                                    $snumjs = $snum;
                                    $snumft = "";
                                }
                                echo "<td class=\"sNumberCl\"><span" . $snumft . " onclick=\"window.open('circulation.php?Search={$snumjs}&Option=mnum&FromTime=1984-12-23&TillTime=$toDay&unrt=1')\">" . $snum;
                                if ($data[$i]['會號附註'] != "") {
                                    echo "<br><span class=\"annotation numberAnno\">(" . $data[$i]['會號附註'] . ")</span>";
                                }
                                echo "</span></td>";
                                $nameColor = ($data[$i]['退會旗標'] == 0) ? "blueName" : "greenName";
                                echo "<td class=\"sNameCl $nameColor TooltipBox\"><span onclick=\"window.open('circulation.php?Search={$data[$i]['簡稱']}&Option=mname&FromTime=1984-12-23&TillTime=$toDay&unrt=1')\">" . $data[$i]['名稱'] . "</span></td>";
                                echo "<td class=\"sShortNameCl\"><span onclick=\"window.open('circulation.php?Search={$data[$i]['簡稱']}&Option=mname&FromTime=1984-12-23&TillTime=$toDay&unrt=1')\">" . $data[$i]['簡稱'] . "</span></td>";
                                echo "<td class=\"sUCodeCl\">" . $data[$i]['統編'] . "</span></td>";
                                echo "<td class=\"sAddressCl\">" . $data[$i]['郵編'] . $data[$i]['地址'] . "</td>";
                                echo "<td class=\"sOfficeNumberCl\">" . $data[$i]['電話1'];
                                if ($data[$i]['電話2'] != "") {
                                    echo "<br>" . $data[$i]['電話2'];
                                }
                                echo "</td>
                                <td class=\"sFaxNumberCl\">" . $data[$i]['傳真'] . "</td>
                                <td class=\"sPiCCl\">";
                                if ($data[$i]['負責人頭銜'] != '') echo "<span class=\"officeTitle\">" . $data[$i]['負責人頭銜'] . "</span><br>";
                                echo $data[$i]['負責人姓名'];
                                if ($data[$i]['負責人附註'] != '') echo "<br><span class=\"officeNote\">(" . $data[$i]['負責人附註'] . ")</span>";
                                echo "</td>
                                <td class=\"sRepresentCl\">";
                                if ($data[$i]['代表人頭銜'] != '') echo "<span class=\"officeTitle\">" . $data[$i]['代表人頭銜'] . "</span><br>";
                                echo $data[$i]['代表人姓名'];
                                if ($data[$i]['代表人附註'] != '') echo "<br><span class=\"officeNote\">(" . $data[$i]['代表人附註'] . ")</span>";
                                echo "</td>
                                <td class=\"sRepresentExtCl\">" . $data[$i]['代表人分機'] . "</td>
                                <td class=\"sRepresentPhoneCl\">" . $data[$i]['代表人手機'] . "</td>
                                <td class=\"sRepresentEmailCl\">" . $data[$i]['代表人Email'] . "</td>";

                                // <th id=\"sLiaisonHd\">聯絡人</th>
                                // <th id=\"sLiaisonExtHd\">分機</th>
                                // <th id=\"sLiaisonPhoneHd\">手機</th>
                                // <th id=\"sLiaisonEmailHd\">Email</th>

                                echo "<td class=\"sLiaisonCl\">";
                                    $liaison1Name  = ($data[$i]['聯絡人1附註'] != '') ? "<span class=\"nameWithNotes TooltipBox\">" . $data[$i]['聯絡人1姓名'] : $data[$i]['聯絡人1姓名'];
                                    $liaison1Title = ($data[$i]['聯絡人1頭銜'] != '') ? "<span class=\"liaisonTitle\">" . $data[$i]['聯絡人1頭銜'] . "</span>" : "";
                                    $liaison1Note  = ($data[$i]['聯絡人1附註'] != '') ? "<span class=\"TooltipText\">" . $data[$i]['聯絡人1附註'] . "</span></span>" : "";
                                    $liaison2Name  = ($data[$i]['聯絡人2附註'] != '') ? "<span class=\"nameWithNotes TooltipBox\">" . $data[$i]['聯絡人2姓名'] : $data[$i]['聯絡人2姓名'];
                                    $liaison2Title = ($data[$i]['聯絡人2頭銜'] != '') ? "<br><span class=\"liaisonTitle\">" . $data[$i]['聯絡人2頭銜'] . "</span>" : "";
                                    $liaison2Note  = ($data[$i]['聯絡人2附註'] != '') ? "<span class=\"TooltipText\">" . $data[$i]['聯絡人2附註'] . "</span></span>" : "";
                                    $liaison3Name  = ($data[$i]['聯絡人3附註'] != '') ? "<span class=\"nameWithNotes TooltipBox\">" . $data[$i]['聯絡人3姓名'] : $data[$i]['聯絡人3姓名'];
                                    $liaison3Title = ($data[$i]['聯絡人3頭銜'] != '') ? "<br><span class=\"liaisonTitle\">" . $data[$i]['聯絡人3頭銜'] . "</span>" : "";
                                    $liaison3Note  = ($data[$i]['聯絡人3附註'] != '') ? "<span class=\"TooltipText\">" . $data[$i]['聯絡人3附註'] . "</span></span>" : "";
                                    echo $liaison1Title . $liaison1Name . $liaison1Note;
                                    if ($data[$i]['聯絡人2頭銜'] == '' && $data[$i]['聯絡人2姓名'] != '') echo "<br>";
                                    echo $liaison2Title . $liaison2Name . $liaison2Note;
                                    if ($data[$i]['聯絡人3頭銜'] == '' && $data[$i]['聯絡人3姓名'] != '') echo "<br>";
                                    echo $liaison3Title . $liaison3Name . $liaison3Note;
                                echo "</td>";
                                echo "<td class=\"sLiaisonExtCl\">" . $data[$i]['聯絡人1分機'];
                                    if ($data[$i]['聯絡人2姓名'] != '') echo "<br>" . $data[$i]['聯絡人2分機'];
                                    if ($data[$i]['聯絡人3姓名'] != '') echo "<br>" . $data[$i]['聯絡人3分機'];
                                echo "</td>";
                                echo "<td class=\"sLiaisonPhoneCl\">" . $data[$i]['聯絡人1手機'];
                                    if ($data[$i]['聯絡人2姓名'] != '') {
                                        if ($data[$i]['聯絡人3手機'] != '') echo "<br>" . $data[$i]['聯絡人2手機'];
                                        else if ($data[$i]['聯絡人3手機'] == '') echo "<br>" . $data[$i]['聯絡人2手機'] . "<br>";
                                    }
                                    if ($data[$i]['聯絡人3姓名'] != '') echo "<br>" . $data[$i]['聯絡人3手機'];
                                echo "</td>";
                                echo "<td class=\"sLiaisonEmailCl\">" . $data[$i]['聯絡人1Email'];
                                    if ($data[$i]['聯絡人2姓名'] != '') {
                                        if ($data[$i]['聯絡人3Email'] != '') echo "<br>" . $data[$i]['聯絡人2Email'];
                                        else if ($data[$i]['聯絡人3Email'] == '') echo "<br>" . $data[$i]['聯絡人2Email'] . "<br>";
                                    }
                                    if ($data[$i]['聯絡人3姓名'] != '') echo "<br>" . $data[$i]['聯絡人3Email'];
                                echo "</td>";
                                echo "<td class=\"sJoindayCl\">" . $data[$i]['入會日期'] . "</td>";
                                echo "<td class=\"sEdCl\">" . $data[$i]['會籍到期日期'] . "</td>";
                                echo "<td class=\"sNoteCl\">" . $data[$i]['備註'] . "</td>
                            </tr>";
                        }
                    echo "
                    </tbody>
                </table>

                <div class=\"pageBoxPack\">";

                /*使用者有選定每頁顯示筆數或頁數時，下方頁數選擇鈕的參數等於其值，否則等於預設值*/
                if (isset($_GET['rows'])) $boxRow = $_GET['rows']; else $boxRow = $page_row;

                /*使用者有輸入搜尋條件時，下方頁數選擇鈕的參數加入該條件，否則忽略*/
                if (isset($_GET['Search'])) {
                    $optionStr = (isset($_GET['Option'])) ? $_GET['Option'] : "all";
                    $searchStr = "Search=" . $search_keyword . "&Option=" . $optionStr;
                } else {
                    $searchStr = "";
                }

                /*使用者勾選「含已退會或未續會會員」或「含已故會員」時，將相關參數加入頁數選擇鈕的目標路徑*/
                if (isset($_GET['osi'])) {
                    $searchFinalURL = "&osi=" . $_GET['osi'];
                } else {
                    $searchFinalURL = "";
                }

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
                            echo "<a href=\"society.php?$searchStr$searchFinalURL&rows=$boxRow&page=$i\">$i</a>";
                        } else {
                            echo $i;
                        }
                        echo "</div>";
                    }
                }
                echo "</div>";
            }
        } else {
            echo "<div class=\"adminOnly\">本頁面限管理員瀏覽！</div>";
        }
    ?>

    <!--修改、新增及刪除資料所用的彈窗表單-->
    <div id="SocietyEditBackground" class="ModalBackground">
        <div id="SocietyEditContent" class="ModalContent">

            <!--彈窗標題-->
            <h2 id="SocietyEditTitle" class="EditTitle"></h2>

            <!--彈窗右上角的關閉按鈕（×）-->
            <span id="SocietyModalClose" class="ModalClose" onclick="modal_close()">&times;</span>

            <!--刪除彈窗訊息-->
            <p id="DeleteMessage">確定要刪除這筆團體會員資料嗎？</p>

            <!--資料表單-->
            <form action="" method="POST" id="SocietyModalForm" autocomplete="off" onsubmit="reveal()">
                <input type="text" name="sId" id="sId" class="SocietyDataID" readonly>

                <table id="SocietyModalFrame1" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%">會號</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 30.5%"><input type="text" name="sNumber" id="sNumber" class="mustfill" placeholder="會號" required></td>
                        <td class="noteLabel" style="width: 9%">舊會號</td>
                        <td class="colon noteLabelColon" style="width: 2.5%">：</td>
                        <td style="width: 18.5%" title="1990年以前的舊格式會號">
                            <input type="text" name="sOldNumber" id="sOldNumber" placeholder="舊格式會號">
                        </td>
                        <td class="noteLabel" style="width: 6.5%">附註</td>
                        <td class="colon noteLabelColon" style="width: 2.5%">：</td>
                        <td style="width: 21%" title="需要特別註明的會號相關訊息">
                            <input type="text" name="sNumberNote" id="sNumberNote" placeholder="會號附註">
                        </td>
                    </tr>
                </table>
                <table id="SocietyModalFrame2" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%">名稱</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 90.5%"><input type="text" name="sName" id="sName" class="mustfill" placeholder="團體全名" required></td>
                    </tr>
                </table>
                <table id="SocietyModalFrame3" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%">簡稱</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 58.5%">
                            <input type="text" name="sForShort" id="sForShort" class="mustfill" placeholder="團體簡稱">
                        </td>
                        <td class="fieldLabel" style="width: 8%; padding-left: 0.5rem">統編</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 21%">
                            <input type="text" name="sUnifiedCode" id="sUnifiedCode" class="mustfill" placeholder="統一編號">
                        </td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="SocietyModalFrame4" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%">地址</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 15.5%"><input type="text" name="sZipCode" id="sZipCode" style="width: 100%" class="mustfill" placeholder="郵遞區號"></td>
                        <td style="width: 75%"><input type="text" name="sAddress" id="sAddress" style="width: 100%" class="mustfill" placeholder="地址"></td>
                    </tr>
                </table>
                <table id="SocietyModalFrame5" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%">電話</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 26.67%"><input type="text" name="sOfficeNumber1" id="sOfficeNumber1" style="width: 100%" class="mustfill" placeholder="電話#1"></td>
                        <td style="width: 26.67%"><input type="text" name="sOfficeNumber2" id="sOfficeNumber2" style="width: 100%" placeholder="電話#2"></td>
                        <td class="fieldLabel" style="width: 7.5%; padding-left: 0.45em">傳真</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 26.66%"><input type="text" name="sFaxNumber" id="sFaxNumber" style="width: 100%" class="mustfill" placeholder="傳真"></td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="SocietyModalFrame6" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 9.5%">負責人</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 23.167%"><input type="text" name="sPiCName" id="sPiCName" style="width: 100%" class="mustfill" placeholder="負責人姓名"></td>
                        <td class="noteLabel" style="width: 6.5%; padding-left: 0.6em">職稱</td>
                        <td class="colon noteLabelColon" style="width: 2.5%">：</td>
                        <td style="width: 23.167%"><input type="text" name="sPiCTitle" id="sPiCTitle" style="width: 100%" class="mustfill" placeholder="負責人職稱"></td>
                        <td class="noteLabel" style="width: 6.5%">附註</td>
                        <td class="colon noteLabelColon" style="width: 2.5%">：</td>
                        <td style="width: 23.166%"><input type="text" name="sPiCNote" id="sPiCNote" style="width: 100%" placeholder="附註訊息"></td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="SocietyModalFrame7" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 9.5%">代表人</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 87.5%">
                            <table style="margin: auto; padding: auto; width: 100%">
                                <tr>
                                    <td style="width: 26.47657%"><input type="text" name="sRpnName" id="sRpnName" style="width: 100%" class="mustfill" placeholder="代表人姓名"></td>
                                    <td class="noteLabel" style="width: 7.42857%; padding-left: 0.65em">職稱</td>
                                    <td class="colon noteLabelColon" style="width: 2.85714%">：</td>
                                    <td style="width: 26.47657%"><input type="text" name="sRpnTitle" id="sRpnTitle" style="width: 100%" class="mustfill" placeholder="職稱"></td>
                                    <td class="noteLabel" style="width: 7.42857%">附註</td>
                                    <td class="colon noteLabelColon" style="width: 2.85714%">：</td>
                                    <td style="width: 26.47544%"><input type="text" name="sRpnNote" id="sRpnNote" style="width: 100%" placeholder="附註訊息"></td>
                                </tr>
                            </table>
                            <table style="margin: 2px auto; padding: auto; width: 100%">
                                <tr>
                                    <td class="noteLabel" style="width: 6%; padding-left: 0.1em">分機</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 11%"><input type="text" name="sRpnExt" id="sRpnExt" style="width: 100%" placeholder="分機"></td>
                                    <td class="noteLabel" style="width: 7.2%; padding-left: 0.55em">手機</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 18%"><input type="text" name="sRpnPhone" id="sRpnPhone" style="width: 100%" placeholder="手機"></td>
                                    <td class="noteLabel" style="width: 9%">Email</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 40.7%"><input type="text" name="sRpnEmail" id="sRpnEmail" style="width: 100%" placeholder="Email"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="SocietyModalFrame8" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 9.5%; text-align-last: center">聯絡人<br><span class="annotation">(1)</span></td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 87.5%">
                            <table style="margin: auto; padding: auto; width: 100%">
                                <tr>
                                    <td style="width: 26.47657%"><input type="text" name="sLs1Name" id="sLs1Name" style="width: 100%" class="mustfill" placeholder="聯絡人姓名#1"></td>
                                    <td class="noteLabel" style="width: 7.42857%; padding-left: 0.65em">職稱</td>
                                    <td class="colon noteLabelColon" style="width: 2.85714%">：</td>
                                    <td style="width: 26.47657%"><input type="text" name="sLs1Title" id="sLs1Title" style="width: 100%" class="mustfill" placeholder="職稱"></td>
                                    <td class="noteLabel" style="width: 7.42857%">附註</td>
                                    <td class="colon noteLabelColon" style="width: 2.85714%">：</td>
                                    <td style="width: 26.47544%"><input type="text" name="sLs1Note" id="sLs1Note" style="width: 100%" placeholder="附註訊息"></td>
                                </tr>
                            </table>
                            <table style="margin: 2px auto; padding: auto; width: 100%">
                                <tr>
                                    <td class="noteLabel" style="width: 6%; padding-left: 0.1em">分機</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 11%"><input type="text" name="sLs1Ext" id="sLs1Ext" style="width: 100%" placeholder="分機"></td>
                                    <td class="noteLabel" style="width: 7.2%; padding-left: 0.55em">手機</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 18%"><input type="text" name="sLs1Phone" id="sLs1Phone" style="width: 100%" placeholder="手機"></td>
                                    <td class="noteLabel" style="width: 9%">Email</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 40.7%"><input type="text" name="sLs1Email" id="sLs1Email" style="width: 100%" placeholder="Email"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="SocietyModalFrame9" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 9.5%; text-align-last: center">聯絡人<br><span class="annotation">(2)</span></td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 87.5%">
                            <table style="margin: auto; padding: auto; width: 100%">
                                <tr>
                                    <td style="width: 26.47657%"><input type="text" name="sLs2Name" id="sLs2Name" style="width: 100%" placeholder="聯絡人姓名#2"></td>
                                    <td class="noteLabel" style="width: 7.42857%; padding-left: 0.65em">職稱</td>
                                    <td class="colon noteLabelColon" style="width: 2.85714%">：</td>
                                    <td style="width: 26.47657%"><input type="text" name="sLs2Title" id="sLs2Title" style="width: 100%" placeholder="職稱"></td>
                                    <td class="noteLabel" style="width: 7.42857%">附註</td>
                                    <td class="colon noteLabelColon" style="width: 2.85714%">：</td>
                                    <td style="width: 26.47544%"><input type="text" name="sLs2Note" id="sLs2Note" style="width: 100%" placeholder="附註訊息"></td>
                                </tr>
                            </table>
                            <table style="margin: 2px auto; padding: auto; width: 100%">
                                <tr>
                                    <td class="noteLabel" style="width: 6%; padding-left: 0.1em">分機</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 11%"><input type="text" name="sLs2Ext" id="sLs2Ext" style="width: 100%" placeholder="分機"></td>
                                    <td class="noteLabel" style="width: 7.2%; padding-left: 0.55em">手機</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 18%"><input type="text" name="sLs2Phone" id="sLs2Phone" style="width: 100%" placeholder="手機"></td>
                                    <td class="noteLabel" style="width: 9%">Email</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 40.7%"><input type="text" name="sLs2Email" id="sLs2Email" style="width: 100%" placeholder="Email"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="SocietyModalFrame10" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 9.5%; text-align-last: center">聯絡人<br><span class="annotation">(3)</span></td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 87.5%">
                            <table style="margin: auto; padding: auto; width: 100%">
                                <tr>
                                    <td style="width: 26.47657%"><input type="text" name="sLs3Name" id="sLs3Name" style="width: 100%" placeholder="聯絡人姓名#2"></td>
                                    <td class="noteLabel" style="width: 7.42857%; padding-left: 0.65em">職稱</td>
                                    <td class="colon noteLabelColon" style="width: 2.85714%">：</td>
                                    <td style="width: 26.47657%"><input type="text" name="sLs3Title" id="sLs3Title" style="width: 100%" placeholder="職稱"></td>
                                    <td class="noteLabel" style="width: 7.42857%">附註</td>
                                    <td class="colon noteLabelColon" style="width: 2.85714%">：</td>
                                    <td style="width: 26.47544%"><input type="text" name="sLs3Note" id="sLs3Note" style="width: 100%" placeholder="附註訊息"></td>
                                </tr>
                            </table>
                            <table style="margin: 2px auto; padding: auto; width: 100%">
                                <tr>
                                    <td class="noteLabel" style="width: 6%; padding-left: 0.1em">分機</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 11%"><input type="text" name="sLs3Ext" id="sLs3Ext" style="width: 100%" placeholder="分機"></td>
                                    <td class="noteLabel" style="width: 7.2%; padding-left: 0.55em">手機</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 18%"><input type="text" name="sLs3Phone" id="sLs3Phone" style="width: 100%" placeholder="手機"></td>
                                    <td class="noteLabel" style="width: 9%">Email</td>
                                    <td class="colon noteLabelColon" style="width: 2.7%">：</td>
                                    <td style="width: 40.7%"><input type="text" name="sLs3Email" id="sLs3Email" style="width: 100%" placeholder="Email"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="SocietyModalFrame11" class="ModalFrame">
                    <col style="width: 19.5%">
                    <col style="width: 3%">
                    <col style="width: 47.5%">
                    <col style="width: 11.5%">
                    <col style="width: 2.5%">
                    <col style="width: 16%">
                    <tr>
                        <td class="fieldLabel">入會日期</td>
                        <td class="colon">：</td>
                        <td><input type="date" name="sJoinday" id="sJoinday" class="mustfill" required></td>
                        <td class="noteLabel">異常旗標</td>
                        <td class="colon noteLabelColon">：</td>
                        <td>
                            <select name="sJoindayFlag" id="sJoindayFlag">
                                <?php
                                    for ($i = 0; $i < 4; $i++) {
                                        echo "<option value=\"" . $dateFlag[$i]['val'] . "\">" . $dateFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldLabel"><span>上次繳費日期</span></td>
                        <td class="colon">：</td>
                        <td colspan="4"><input type="date" name="sLastPayDay" id="sLastPayDay" class="mustfill"></td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="SocietyModalFrame12" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%">備註</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 90.5%"><textarea name="sNote" id="sNote" wrap="hard"></textarea></td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="SocietyModalFrame13" class="ModalFrame">
                    <tr>
                        <td style="text-align: center">
                            <label><input type="checkbox" name="sOutFlag" id="sOutFlag" value="1">退會或不續會</label>
                        </td>
                    </tr>
                </table>

                <!--確認及取消按鈕-->
                <div class="Pack">
                    <input type="submit" name="eConfirm" id="eConfirm" class="ModalButton" value="確定">
                    <input type="button" name="eCancel" id="eCancel" class="ModalButton" value="取消" onclick="modal_close()">    <!--js 函數即使設定為 var fn = function() {} 的形式，仍可用 fn() 的方式呼叫-->
                    <input type="hidden" name="PageNumber" id="PageNumber" value="<?php echo $page_number; ?>">
                    <input type="hidden" name="ScrollView" id="ScrollView">
                    <input type="hidden" name="PassFlag" id="PassFlag">
                </div>

                <!--利用腳本設定傳值旗標（修改或插入）-->
                <script>
                    function modal_status(s) {
                        $("#PassFlag").val(s);
                    }
                </script>
            </form>
        </div>
    </div>

    <!--底部版權版本資訊-->
    <div id="includePage"></div>

    <script src="society.js"></script>
</body>
</html>
<?php
    mysqli_close($db_link);
?>