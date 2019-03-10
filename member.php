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

    /*資料庫查詢設定－查詢所有會員*/
    $total_query   = 'SELECT * FROM `member`';                                  //設定搜尋條件為所有會員資料
    $total_result  = mysqli_query($db_link, $total_query);                      //查詢所有會員
    $total_number  = mysqli_num_rows($total_result);                            //計算會員總數

    /*篩選不在籍、榮譽或已故會員的相關語法*/
    $searchPrefix = "SELECT * FROM `member` WHERE ";
    $nd = " AND ";
    $onlyOutMemberExcepted  = "`mOf` != 1";
    $onlyDeadMemberExcepted = "`mDm` != 1";
    $bothExcepted = $onlyOutMemberExcepted . $nd . $onlyDeadMemberExcepted;
    $bothIncluded = "";

    /*資料庫查詢設定－查詢在籍會員*/
    $active_query  = $searchPrefix . $bothExcepted;                             //剔除退會或不續費、已故會員，其餘為在籍會員
    $active_query .= ' ORDER BY `mNb` * 1';                                     //依會號排序在籍會員，此處因會號為 varchar 型態，為了自然排序，將會號乘以 1（也可以除以 1）
    $active_result = mysqli_query($db_link, $active_query);                     //查詢所有在籍會員
    $active_number = mysqli_num_rows($active_result);                           //計算在籍會員總數

    /*資料庫查詢設定－查詢在世會員（在籍 + 不在籍且未故）*/
    $live_query  = $searchPrefix . $onlyDeadMemberExcepted;                     //剔除已故會員
    $live_query .= ' ORDER BY `mNb` * 1';                                       //依會號排序
    $live_result = mysqli_query($db_link, $live_query);                         //查詢所有在世會員
    $live_number = mysqli_num_rows($live_result);                               //計算在世會員總數

    /*資料庫查詢設定－查詢在籍 + 已故會員*/
    $keep_query  = $searchPrefix . $onlyOutMemberExcepted;                      //剔除退會或不續費會員
    $keep_query .= ' ORDER BY `mNb` * 1';                                       //依會號排序
    $keep_result = mysqli_query($db_link, $keep_query);                         //查詢所有在籍 + 已故會員
    $keep_number = mysqli_num_rows($keep_result);                               //計算在籍 + 已故會員總數

    /*依不在籍或已故會員勾選狀況決定不同的查詢語法*/
    if (isset($_GET['omi']) && ($_GET['omi'] == 1)) {
        if (isset($_GET['dmi']) && ($_GET['dmi'] == 1)) {
            $extant_query   = $total_query;
            $extant_number  = $total_number;
            $searchFinalStr = $bothIncluded;
        } else {
            $extant_query   = $live_query;
            $extant_number  = $live_number;
            $searchFinalStr = $nd . $onlyDeadMemberExcepted;
        }
    } else if (isset($_GET['dmi']) && ($_GET['dmi'] == 1)) {
        $extant_query   = $keep_query;
        $extant_number  = $keep_number;
        $searchFinalStr = $nd . $onlyOutMemberExcepted;
    } else {
        $extant_query   = $active_query;
        $extant_number  = $active_number;
        $searchFinalStr = $nd . $bothExcepted;
    }

    /*設定資料分頁*/
    include "ini.php";                                                          //引入寫在外部檔案內的預設每頁筆數
    $page_number = 1;                                                           //預設頁數
    if (isset($_GET['rows'])) {                                                 //若使用者已輸入每頁筆數
        if ($_GET['rows'] != '') {                                              //且此筆數不為空值
            $page_row = $_GET['rows'];                                          //依使用者所輸入的每頁筆數設置每頁顯示筆數
        } else if ($_GET['rows'] == '') {                                       //但若使用者輸入的筆數為空值
            $_GET['rows'] = $page_row_member;                                   //以預設每頁筆數取代該空值
            $page_row = $_GET['rows'];                                          //同時以此預設每頁筆數為準來顯示本頁資料
        }
    } else {
        $page_row = $page_row_member;                                           //若使用者根本沒有輸入每頁筆數，直接套用外部檔案的預設每頁筆數
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
    $id_max_query = 'SELECT MAX(`mId`) FROM `member`';
    $id_max_result = mysqli_query($db_link, $id_max_query);
    $id_max = @mysqli_fetch_row($id_max_result);

    /*取得當前資料庫中「會號」的最大值*/
    $num_max_query = 'SELECT MAX(CAST(`mNb` AS INT)) FROM `member`';
    $num_max_result = mysqli_query($db_link, $num_max_query);
    $num_max = @mysqli_fetch_row($num_max_result);

    $todaytime = strtotime(date("Y-m-d"));
    $yeartosec = 86400 * 365;

    if (isset($_POST['PassFlag'])) {
        /*將彈窗表單傳回的各項值，重新命名為名稱稍短、且可直接在雙引號中引用的變數，順便解決了有些值為 NULL 的問題*/
        $PmId = $_POST['mId'];
        $PmNumber = ($_POST['mNumber'] == '') ? 'NULL' : "'" . $_POST['mNumber'] . "'";
        $PmNumberNote = ($_POST['mNumberNote'] == '') ? 'NULL' : "'" . $_POST['mNumberNote'] . "'";
        $PmName = ($_POST['mName'] == '') ? 'NULL' : "'" . $_POST['mName'] . "'";
        $PmNameNote = ($_POST['mNameNote'] == '') ? 'NULL' : "'" . $_POST['mNameNote'] . "'";
        $PmAlias = ($_POST['mAlias'] == '') ? 'NULL' : "'" . $_POST['mAlias'] . "'";
        $PmPseudonym = ($_POST['mPseudonym'] == '') ? 'NULL' : "'" . $_POST['mPseudonym'] . "'";
        $PmGender = ($_POST['mGender'] == '') ? 'NULL' : "'" . $_POST['mGender'] . "'";
        $PmBirthday = ($_POST['mBirthday'] == '') ? 'NULL' : "'" . $_POST['mBirthday'] . "'";
        $PmBirthdayFlag = ($_POST['mBirthdayFlag'] == '') ? 'NULL' : "'" . $_POST['mBirthdayFlag'] . "'";
        $PmAncestralHome1 = ($_POST['mAncestralHome1'] == '') ? 'NULL' : "'" . $_POST['mAncestralHome1'] . "'";
        $PmAncestralHomeNote1 = ($_POST['mAncestralHomeNote1'] == '') ? 'NULL' : "'" . $_POST['mAncestralHomeNote1'] . "'";
        $PmAncestralHomeFlag1 = ($_POST['mAncestralHomeFlag1'] == '') ? 'NULL' : "'" . $_POST['mAncestralHomeFlag1'] . "'";
        $PmAncestralHome2 = ($_POST['mAncestralHome2'] == '') ? 'NULL' : "'" . $_POST['mAncestralHome2'] . "'";
        $PmAncestralHomeNote2 = ($_POST['mAncestralHomeNote2'] == '') ? 'NULL' : "'" . $_POST['mAncestralHomeNote2'] . "'";
        $PmAncestralHomeFlag2 = ($_POST['mAncestralHomeFlag2'] == '') ? 'NULL' : "'" . $_POST['mAncestralHomeFlag2'] . "'";
        $PmZipCode1 = ($_POST['mZipCode1'] == '') ? 'NULL' : "'" . $_POST['mZipCode1'] . "'";
        $PmAddress1 = ($_POST['mAddress1'] == '') ? 'NULL' : "'" . $_POST['mAddress1'] . "'";
        $PmAddressNote1 = ($_POST['mAddressNote1'] == '') ? 'NULL' : "'" . $_POST['mAddressNote1'] . "'";
        $PmAddressFlag1 = ($_POST['mAddressFlag1'] == '') ? 'NULL' : "'" . $_POST['mAddressFlag1'] . "'";
        $PmZipCode2 = ($_POST['mZipCode2'] == '') ? 'NULL' : "'" . $_POST['mZipCode2'] . "'";
        $PmAddress2 = ($_POST['mAddress2'] == '') ? 'NULL' : "'" . $_POST['mAddress2'] . "'";
        $PmAddressNote2 = ($_POST['mAddressNote2'] == '') ? 'NULL' : "'" . $_POST['mAddressNote2'] . "'";
        $PmAddressFlag2 = ($_POST['mAddressFlag2'] == '') ? 'NULL' : "'" . $_POST['mAddressFlag2'] . "'";
        $PmHomeNumber1 = ($_POST['mHomeNumber1'] == '') ? 'NULL' : "'" . $_POST['mHomeNumber1'] . "'";
        $PmHomeNumberFlag1 = ($_POST['mHomeNumberFlag1'] == '') ? 'NULL' : "'" . $_POST['mHomeNumberFlag1'] . "'";
        $PmHomeNumber2 = ($_POST['mHomeNumber2'] == '') ? 'NULL' : "'" . $_POST['mHomeNumber2'] . "'";
        $PmHomeNumberFlag2 = ($_POST['mHomeNumberFlag2'] == '') ? 'NULL' : "'" . $_POST['mHomeNumberFlag2'] . "'";
        $PmOfficeNumber1 = ($_POST['mOfficeNumber1'] == '') ? 'NULL' : "'" . $_POST['mOfficeNumber1'] . "'";
        $PmOfficeNumberNote1 = ($_POST['mOfficeNumberNote1'] == '') ? 'NULL' : "'" . $_POST['mOfficeNumberNote1'] . "'";
        $PmOfficeNumberFlag1 = ($_POST['mOfficeNumberFlag1'] == '') ? 'NULL' : "'" . $_POST['mOfficeNumberFlag1'] . "'";
        $PmOfficeNumber2 = ($_POST['mOfficeNumber2'] == '') ? 'NULL' : "'" . $_POST['mOfficeNumber2'] . "'";
        $PmOfficeNumberNote2 = ($_POST['mOfficeNumberNote2'] == '') ? 'NULL' : "'" . $_POST['mOfficeNumberNote2'] . "'";
        $PmOfficeNumberFlag2 = ($_POST['mOfficeNumberFlag2'] == '') ? 'NULL' : "'" . $_POST['mOfficeNumberFlag2'] . "'";
        $PmFaxNumber = ($_POST['mFaxNumber'] == '') ? 'NULL' : "'" . $_POST['mFaxNumber'] . "'";
        $PmPhoneNumber1 = ($_POST['mPhoneNumber1'] == '') ? 'NULL' : "'" . $_POST['mPhoneNumber1'] . "'";
        $PmPhoneNumber2 = ($_POST['mPhoneNumber2'] == '') ? 'NULL' : "'" . $_POST['mPhoneNumber2'] . "'";
        $PmEmailAddress1 = ($_POST['mEmailAddress1'] == '') ? 'NULL' : "'" . $_POST['mEmailAddress1'] . "'";
        $PmEmailAddressNote1 = ($_POST['mEmailAddressNote1'] == '') ? 'NULL' : "'" . $_POST['mEmailAddressNote1'] . "'";
        $PmEmailAddress2 = ($_POST['mEmailAddress2'] == '') ? 'NULL' : "'" . $_POST['mEmailAddress2'] . "'";
        $PmEmailAddressNote2 = ($_POST['mEmailAddressNote2'] == '') ? 'NULL' : "'" . $_POST['mEmailAddressNote2'] . "'";
        $PmEmailAddress3 = ($_POST['mEmailAddress3'] == '') ? 'NULL' : "'" . $_POST['mEmailAddress3'] . "'";
        $PmEmailAddressNote3 = ($_POST['mEmailAddressNote3'] == '') ? 'NULL' : "'" . $_POST['mEmailAddressNote3'] . "'";
        $PmNote = ($_POST['mNote'] == '') ? 'NULL' : "'" . str_replace(chr(13).chr(10), "<br>", $_POST['mNote']) . "'";
        $PmJoinday = ($_POST['mJoinday'] == '') ? 'NULL' : "'" . $_POST['mJoinday'] . "'";
        $PmJoindayFlag = ($_POST['mJoindayFlag'] == '') ? 'NULL' : "'" . $_POST['mJoindayFlag'] . "'";
        if ($_POST['mExpirationDate'] == '') {
            $PmExpirationDate = 'NULL';
            $PmOutFlagDate = 0;
        } else {
            $PmExpirationDate = "'" . $_POST['mExpirationDate'] . "'";
            $PmOutFlagDate = $todaytime - strtotime($_POST['mExpirationDate']);
        }
        $PmOutFlag = ((isset($_POST['mOutFlag'])) && ($_POST['mOutFlag'] == 1)) ? 1 : 0;
        $PmHonorMemberFlag = ((isset($_POST['mHonorMemberFlag'])) && ($_POST['mHonorMemberFlag'] == 1)) ? 1 : 0;
        $PmDeadMemberFlag = ((isset($_POST['mDeadMemberFlag'])) && ($_POST['mDeadMemberFlag'] == 1)) ? 1 : 0;
        $PmSpecFlag = ((isset($_POST['mSpecFlag'])) && ($_POST['mSpecFlag'] == 1)) ? 1 : 0;

        /*傳值旗標為「修改」時更新資料庫*/
        if ($_POST['PassFlag'] == '修改') {
            /*修改會員資料表`member`中的資料*/
            $edit_query  = "UPDATE `member` SET ";
            $edit_query .= "`mNb` = $PmNumber, ";
            $edit_query .= "`mNbNt` = $PmNumberNote, ";
            $edit_query .= "`mNm` = $PmName, ";
            $edit_query .= "`mNmNt` = $PmNameNote, ";
            $edit_query .= "`mAn` = $PmAlias, ";
            $edit_query .= "`mPm` = $PmPseudonym, ";
            $edit_query .= "`mGd` = $PmGender, ";
            $edit_query .= "`mBd` = $PmBirthday, ";
            $edit_query .= "`mBdFg` = $PmBirthdayFlag, ";
            $edit_query .= "`mJd` = $PmJoinday, ";
            $edit_query .= "`mJdFg` = $PmJoindayFlag, ";
            $edit_query .= "`mAh1` = $PmAncestralHome1, ";
            $edit_query .= "`mAh1Nt` = $PmAncestralHomeNote1, ";
            $edit_query .= "`mAh1Fg` = $PmAncestralHomeFlag1, ";
            $edit_query .= "`mAh2` = $PmAncestralHome2, ";
            $edit_query .= "`mAh2Nt` = $PmAncestralHomeNote2, ";
            $edit_query .= "`mAh2Fg` = $PmAncestralHomeFlag2, ";
            $edit_query .= "`mZc1` = $PmZipCode1, ";
            $edit_query .= "`mAd1` = $PmAddress1, ";
            $edit_query .= "`mAd1Nt` = $PmAddressNote1, ";
            $edit_query .= "`mAd1Fg` = $PmAddressFlag1, ";
            $edit_query .= "`mZc2` = $PmZipCode2, ";
            $edit_query .= "`mAd2` = $PmAddress2, ";
            $edit_query .= "`mAd2Nt` = $PmAddressNote2, ";
            $edit_query .= "`mAd2Fg` = $PmAddressFlag2, ";
            $edit_query .= "`mHn1` = $PmHomeNumber1, ";
            $edit_query .= "`mHn1Fg` = $PmHomeNumberFlag1, ";
            $edit_query .= "`mHn2` = $PmHomeNumber2, ";
            $edit_query .= "`mHn2Fg` = $PmHomeNumberFlag2, ";
            $edit_query .= "`mOn1` = $PmOfficeNumber1, ";
            $edit_query .= "`mOn1Nt` = $PmOfficeNumberNote1, ";
            $edit_query .= "`mOn1Fg` = $PmOfficeNumberFlag1, ";
            $edit_query .= "`mOn2` = $PmOfficeNumber2, ";
            $edit_query .= "`mOn2Nt` = $PmOfficeNumberNote2, ";
            $edit_query .= "`mOn2Fg` = $PmOfficeNumberFlag2, ";
            $edit_query .= "`mFn` = $PmFaxNumber, ";
            $edit_query .= "`mPn1` = $PmPhoneNumber1, ";
            $edit_query .= "`mPn2` = $PmPhoneNumber2, ";
            $edit_query .= "`mEa1` = $PmEmailAddress1, ";
            $edit_query .= "`mEa1Nt` = $PmEmailAddressNote1, ";
            $edit_query .= "`mEa2` = $PmEmailAddress2, ";
            $edit_query .= "`mEa2Nt` = $PmEmailAddressNote2, ";
            $edit_query .= "`mEa3` = $PmEmailAddress3, ";
            $edit_query .= "`mEa3Nt` = $PmEmailAddressNote3, ";
            $edit_query .= "`mNt` = $PmNote, ";
            $edit_query .= "`mPd` = $PmExpirationDate, ";
            $edit_query .= "`mOf` = $PmOutFlag, ";
            $edit_query .= "`mHm` = $PmHonorMemberFlag, ";
            $edit_query .= "`mDm` = $PmDeadMemberFlag, ";
            $edit_query .= "`mSp` = $PmSpecFlag ";
            $edit_query .= "WHERE `mId` = $PmId";
            //echo $edit_query;
            $edit_result = mysqli_query($db_link, $edit_query);

            /*修改會員清單資料表`mlist`中的資料*/
            if ($PmOutFlagDate > $yeartosec) {
                $edit_list_query  = "UPDATE `mlist` SET `msNm` = $PmName, `msFg` = 0, `msSp` = $PmSpecFlag, `msOf` = '1' WHERE `msNb` = $PmNumber";
            } else if ($PmOutFlagDate > 0 && $PmOutFlagDate < $yeartosec) {
                $edit_list_query  = "UPDATE `mlist` SET `msNm` = $PmName, `msFg` = 0, `msSp` = $PmSpecFlag, `msOf` = '2' WHERE `msNb` = $PmNumber";
            } else {
                $edit_list_query  = "UPDATE `mlist` SET `msNm` = $PmName, `msFg` = 0, `msSp` = $PmSpecFlag, `msOf` = '0' WHERE `msNb` = $PmNumber";
            }
            //echo $PmOutFlagDate . "<br>";
            //echo $edit_list_query;
            $edit_list_result = mysqli_query($db_link, $edit_list_query);

            /*更新借還書紀錄資料表*/
            $change_select_query  = "UPDATE `circulation` SET `cMm` = $PmName WHERE `cMn` = $PmNumber";
            $change_select_result = mysqli_query($db_link, $change_select_query);
        }
        /*傳值旗標為「新增」時新增資料到資料庫*/
        else if ($_POST['PassFlag'] == '新增') {
            /*新增完整資料到會員資料表`member`*/
            $insert_query  = "INSERT INTO `member` (`mId`, `mNb`, `mNbNt`, `mNm`, `mNmNt`, `mAn`, `mPm`, `mGd`, `mBd`, `mBdFg`, `mJd`, `mJdFg`, `mAh1`, `mAh1Nt`, `mAh1Fg`, `mAh2`, `mAh2Nt`, `mAh2Fg`, `mZc1`, `mAd1`, `mAd1Nt`, `mAd1Fg`, `mZc2`, `mAd2`, `mAd2Nt`, `mAd2Fg`, `mHn1`, `mHn1Fg`, `mHn2`, `mHn2Fg`, `mOn1`, `mOn1Nt`, `mOn1Fg`, `mOn2`, `mOn2Nt`, `mOn2Fg`, `mFn`, `mPn1`, `mPn2`, `mEa1`, `mEa1Nt`, `mEa2`, `mEa2Nt`, `mEa3`, `mEa3Nt`, `mNt`, `mPd`, `mOf`, `mHm`, `mDm`, `mSp`) VALUES ";
            $insert_query .= "(NULL, $PmNumber, $PmNumberNote, $PmName, $PmNameNote, $PmAlias, $PmPseudonym, $PmGender, $PmBirthday, $PmBirthdayFlag, $PmJoinday, $PmJoindayFlag, $PmAncestralHome1, $PmAncestralHomeNote1, $PmAncestralHomeFlag1, $PmAncestralHome2, $PmAncestralHomeNote2, $PmAncestralHomeFlag2, $PmZipCode1, $PmAddress1, $PmAddressNote1, $PmAddressFlag1, $PmZipCode2, $PmAddress2, $PmAddressNote2, $PmAddressFlag2, $PmHomeNumber1, $PmHomeNumberFlag1, $PmHomeNumber2, $PmHomeNumberFlag2, $PmOfficeNumber1, $PmOfficeNumberNote1, $PmOfficeNumberFlag1, $PmOfficeNumber2, $PmOfficeNumberNote2, $PmOfficeNumberFlag2, $PmFaxNumber, $PmPhoneNumber1, $PmPhoneNumber2, $PmEmailAddress1, $PmEmailAddressNote1, $PmEmailAddress2, $PmEmailAddressNote2, $PmEmailAddress3, $PmEmailAddressNote3, $PmNote, $PmExpirationDate, $PmOutFlag, $PmHonorMemberFlag, $PmDeadMemberFlag, $PmSpecFlag)";
            //echo $insert_query;
            $insert_result = mysqli_query($db_link, $insert_query);

            /*僅新增會號及姓名到會員清單資料表`mlist`*/
            $insert_list_query  = "INSERT INTO `mlist` (`msId`, `msNb`, `msNm`, `msFg`, `msSp`) VALUES (NULL, $PmNumber, $PmName, 0, $PmSpecFlag)";
            //echo $insert_list_query;
            $insert_list_result = mysqli_query($db_link, $insert_list_query);
        }
        /*傳值旗標為「刪除」時刪除該筆資料*/
        else if ($_POST['PassFlag'] == '刪除') {
            /*從會員資料表`member`中刪除資料*/
            $delete_query  = "DELETE FROM `member` WHERE `mId` = $PmId";
            //echo $delete_query;
            $delete_result = mysqli_query($db_link, $delete_query);

            /*從會員清單資料表`mlist`中刪除資料*/
            $delete_list_query  = "DELETE FROM `mlist` WHERE `msNb` = $PmNumber";
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
    $memberSearchItem[$jdk]['sql'] = '`mJd`';
    if (preg_match("/\d{4}-\d{2}-\d{2}/", $Search_r)) {
        $memberSearchItem[$jdk]['sql_cd'] = ' AND (`mJdFg` != \'noDay\' OR `mJdFg` IS NULL)';
    } else {
        $memberSearchItem[$jdk]['sql_cd'] = '';
    }

    /*生日、入會日期、上次繳費日期的搜尋字串須特別處理，將其陣列索引值先提取出來*/
    $dateOption = array(3, 6, 13, 14);
    $fullDateOption = array(3, 13, 14);                                         //「生日(月日)」非完整日期格式

    /*搜尋選項為「榮譽會員」、「非榮譽會員」或「已故會員」時亦須特別處理，故也先提取其陣列索引值*/
    $memberSituation = array(15, 16, 17);

    /*若使用者先已選定搜尋選項，下拉式搜尋選單顯示為該選項*/
    for ($i = 0; $i < count($memberSearchItem); $i++) {
        if ($Option_r == $memberSearchItem[$i]['str']) {
            if (in_array($i, $dateOption)) {
                /*轉換使用者輸入日期中的「/」或「.」為「-」*/
                $Search_r = str_replace(array("/", "."), "-", $Search_r);
                /*使用者輸入的月或日不足 2 位數時補零*/
                if (in_array($i, $fullDateOption)) {
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
                } else if (($i == 6) && (preg_match("/\d{1,2}-\d{1,2}/", $Search_r))) {
                    $dateanlz = sscanf($Search_r, "%d-%d");
                    $dateanlz[0] = str_pad($dateanlz[0], 2, "0", STR_PAD_LEFT);
                    $dateanlz[1] = str_pad($dateanlz[1], 2, "0", STR_PAD_LEFT);
                    $Search_r = implode("-", $dateanlz);
                }
            }
            /*搜尋選項為「生日(月)」時，亦必須將使用者輸入的 1 位數月份補零*/
            else if (($i == 5) && (preg_match("/\d{1,2}/", $Search_r))) {
                sscanf($Search_r, "%d", $dateanlz);
                $dateanlz = str_pad($dateanlz, 2, "0", STR_PAD_LEFT);
                $Search_r = $dateanlz;
            }
            /*搜尋選項為「榮譽會員」、「非榮譽會員」或「已故會員」時清空搜尋欄位內容*/
            else if (in_array($i, $memberSituation)) {
                $Search_r = $_GET['Search'] = "";
            }
            $searchWords = $memberSearchItem[$i]['sql'];
            $searchSuffix = $memberSearchItem[$i]['sql_cd'];
            $inum = $i;
            break;
        }
    }

    /*結合搜尋條件的查詢語法*/
    if ($Search_r != "") {
        /*防範隱碼攻擊*/
        $search_keyword = filter_var($Search_r, FILTER_SANITIZE_MAGIC_QUOTES);

        if ($Option_r == 'num') {
            $searchTarget = " LIKE '" . $search_keyword . "'";
        } else if ($Option_r == 'hnm' || $Option_r == 'nhm') {
            $searchTarget = "";
        } else if ($Option_r == 'ddm') {
            $_GET['dmi'] = 1;
            $searchWords = "1";
            $searchTarget = "";
        } else {
            $searchTarget = " LIKE '%" . $search_keyword . "%'";
        }
    } else {
        $search_keyword = "";
        if ($Option_r == 'hnm' || $Option_r == 'nhm') {
            $searchTarget = "";
        } else if ($Option_r == 'ddm') {
            $_GET['dmi'] = 1;
            $searchTarget = "";
            $searchFinalStr = str_replace("AND `mDm` != 1", "", $searchFinalStr);
        } else {
            $searchWords = "1";
            $searchTarget = "";
        }
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
    <title>會員資料管理 (現有<?php echo $active_number; ?>位會員)</title>

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
<body id="memberBody" onload="<?php if (isset($_POST['PassFlag'])) echo "scrollView('" . $_POST['ScrollView'] . "'); "; ?>error_display(); hide_text()" onkeydown="modal_esc(event)">
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

    <!--用來找出視窗及表格寬高的程式碼-->
    <!--<p id="tablewh"></p>
    <script type="text/javascript">
        function table_wh() {
            var cwb = $("#memberBody")[0].clientWidth;
            var owb = $("#memberBody")[0].offsetWidth;
            var cwt = $("#MemberData")[0].clientWidth;
            var owt = $("#MemberData")[0].offsetWidth;
            var chb = $("#memberBody")[0].clientHeight;
            var ohb = $("#memberBody")[0].offsetHeight;
            var cht = $("#MemberData")[0].clientHeight;
            var oht = $("#MemberData")[0].offsetHeight;
            $("#tablewh").html(cwb + "<br>" + owb + "<br>" +  cwt + "<br>" + owt + "<br>" + chb + "<br>" + ohb + "<br>" + cht + "<br>" + oht);
        }
    </script>-->
    <h1 id="TitleMember">會員資料管理</h1>

    <!--新增會員資料按鈕-->
    <?php
        $defaultScriptStr = "";
        for ($i = 1; $i < 46; $i++) {
            $defaultScriptStr .= "'', ";
        }
        for ($j = 46; $j < 48; $j++) {
            $defaultScriptStr .= "0, ";
        }
        $defaultScriptStr .= "0";
        //echo $defaultScriptStr;
        $defaultScriptIndex = ($id_max[0] + 1) . ", '" . ($num_max[0] + 1) . "', " . $defaultScriptStr;
        //echo $defaultScriptIndex

        if ($adminFlag == "true") {
            echo "<img id=\"addMemberButton\" src=\"insert_button.png\" onmouseout=\"this.src='insert_button.png'\" onmouseover=\"this.src='insert_button_invert.png'\" onclick=\"modal_display('新增', " . $defaultScriptIndex . "); modal_status('新增')\">";
        }
    ?>
    <?php if ($adminFlag == "true") { ?>
        <!--選擇筆數、頁數及搜尋表單-->
        <form action="" method="GET" id="MemberSearcher">
            <table id="memberSearchBox">
                <tr style="height: 2em">
                    <td colspan="2" style="text-align: center">
                        <!--搜尋功能-->
                        <input type="text" id="mSearch" name="Search" value="<?php if (isset($_GET["Search"])) echo $_GET["Search"]; ?>">
                        <select id="mOption" name="Option" onchange="hide_text()">
                            <?php
                                /*在下拉式選單中依序列出搜尋條件*/
                                for ($i = 0; $i < count($memberSearchItem); $i++) {
                                    echo "<option value=\"" . $memberSearchItem[$i]['str'] . "\"";
                                    if ((isset($_GET["Option"])) && ($memberSearchItem[$i]['str'] == $_GET["Option"])) echo "selected";
                                    echo ">" . $memberSearchItem[$i]['item'] . "</option>";
                                }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-size: 0pt; text-align: left">
                        <label style="font-size: 0.85rem"><input type="checkbox" name="omi" id="outMemberIncluded" value="1" <?php if ((isset($_GET['omi']) && ($_GET['omi'] == 1))) echo "checked"; ?>>含已退會或未續會會員</label>
                        <label style="font-size: 0.85rem; margin-left: 1.5em"><input type="checkbox" name="dmi" id="deadMemberIncluded" value="1" <?php if ((isset($_GET['dmi']) && ($_GET['dmi'] == 1))) echo "checked"; ?>>含已故會員</label>
                    </td>
                    <td style="width: 34%; text-align: center">
                        <input id="mSubmit" type="submit" value="搜尋">
                    </td>
                </tr>
                <tr>
                    <td id="memberRowsAndPages" colspan="2">
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
                            if ((isset($_GET['rows'])) && ($_GET['rows'] != '') && ($_GET['rows'] != $page_row_member)) {
                                $init = file("ini.php") or die("Unable to open file!");             //將外部設定檔的內容取出為陣列
                                $init[4] = "    \$page_row_member = " . $_GET['rows'] . ";\n";      //改寫預設頁數的初始值
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

        /*未設定搜尋條件時，顯示所有仍在籍的會員資料，否則顯示符合搜尋條件的會員資料*/
        // if ((!isset($_GET['Search']) || ($_GET['Search'] == '')) || (!isset($_GET['Option']) || ($_GET['Option'] == ''))) {
        //     $display_result = $limit_result;
        // } else {
            $display_result = $search_limit_result;
        //}

        $dateSeparator = "/";                                   //年月日分隔字元
        $mgFlag = 1;                                            //曆法旗標（0 = 西元紀年，1 = 民國紀年）

        /*已登入管理者身分時才顯示資料，否則顯示禁止瀏覽訊息*/
        if ($adminFlag == "true") {
            /*搜尋結果 > 0 時才顯示表格*/
            if (mysqli_num_rows($display_result) > 0) {
                /*以表格顯示會員資料*/
                echo "<table id=\"MemberData\">
                    <!--各欄-->
                    <col id=\"ControlCol\">
                    <col id=\"NumberCol\">
                    <col id=\"NameCol\">
                    <col id=\"PseudonymCol\">
                    <col id=\"GenderCol\">
                    <col id=\"BirthdayCol\">
                    <col id=\"AncestralHomeCol\">
                    <col id=\"AddressCol\">
                    <col id=\"HomeNumberCol\">
                    <col id=\"OfficeNumberCol\">
                    <col id=\"FaxNumberCol\">
                    <col id=\"PhoneNumberCol\">
                    <col id=\"EmailCol\">
                    <col id=\"JoindayCol\">
                    <col id=\"EdCol\">
                    <col id=\"NoteCol\">

                    <!--表頭-->
                    <thead>
                        <tr id=\"Hd\">
                            <th id=\"ControlHd\"></th>
                            <th id=\"NumberHd\">會號</th>
                            <th id=\"NameHd\">姓名
                                <span id=\"PNHide\" class=\"colHide TooltipBox\" onclick=\"pn_hide()\">
                                    <img src=\"arrow_expand_right.png\">
                                    <span class=\"TooltipText\">顯示筆名</span>
                                </span>
                            </th>
                            <th id=\"PseudonymHd\">筆名</th>
                            <th id=\"GenderHd\">性別</th>
                            <th id=\"BirthdayHd\">生日
                                <span id=\"AHHide\" class=\"colHide TooltipBox\" onclick=\"ah_hide()\">
                                    <img src=\"arrow_expand_right.png\">
                                    <span class=\"TooltipText\">顯示籍貫</span>
                                </span>
                            </th>
                            <th id=\"AncestralHomeHd\">籍貫</th>
                            <th id=\"AddressHd\">地址</th>
                            <th id=\"HomeNumberHd\">電話(H)</th>
                            <th id=\"OfficeNumberHd\">電話(O)</th>
                            <th id=\"FaxNumberHd\">傳真</th>
                            <th id=\"PhoneNumberHd\">手機</th>
                            <th id=\"EmailHd\">Email</th>
                            <th id=\"JoindayHd\">入會日期</th>
                            <th id=\"EdHd\">會籍到期</th>
                            <th id=\"NoteHd\">備註</th>
                        </tr>
                    </thead>

                    <tbody>";
                        /*將會員資料依限制條件分頁後輸出為陣列*/
                        while ($row = mysqli_fetch_assoc($display_result)) {
                            /*切割會員生日為年、月、日並計算民國紀年*/
                            $bdYear   = substr($row['mBd'], 0, 4) . $dateSeparator;
                            $mgbdYear = (int)substr($row['mBd'], 0, 4) - 1911 . $dateSeparator;
                            $bdMonth  = substr($row['mBd'], 5, 2) . $dateSeparator;
                            $bdDay    = substr($row['mBd'], 8, 2);

                            /*處理會員生日的異常狀況*/
                            if ($row['mBdFg'] == 'noYear') {                        //沒有出生年的情況
                                $bdYear  = $mgbdYear = "";
                            } else if ($row['mBdFg'] == 'noDay') {                  //沒有出生日的情況
                                $bdDay   = "";
                                $bdMonth = str_replace($dateSeparator, "", $bdMonth);
                            } else if ($row['mBdFg'] == 'Feb30') {                  //生日打成 2 月 30 日的情況
                                $bdDay   = 30;
                            } else if ($row['mBd'] == '') {                         //生日為空的情況
                                $bdYear = $mgbdYear = $bdMonth = $bdDay = "";
                            }

                            $bdYear   = ($mgFlag == 1) ? $mgbdYear : $bdYear;       //依據曆法旗標決定使用西元紀年或民國紀年
                            $birthday = $bdYear . $bdMonth . $bdDay;                //串接出生年月日為字串

                            /*切割會員入會日期為年、月、日並計算民國紀年*/
                            $jdYear   = substr($row['mJd'], 0, 4) . $dateSeparator;
                            $mgjdYear = (int)substr($row['mJd'], 0, 4) - 1911 . $dateSeparator;
                            $jdMonth  = substr($row['mJd'], 5, 2) . $dateSeparator;
                            $jdDay    = substr($row['mJd'], 8, 2);

                            /*處理會員入會日期的異常狀況*/
                            if ($row['mJdFg'] == 'onlyYear') {                      //僅有入會年的情況
                                $jdMonth = $jdDay = "";
                                $jdYear = str_replace($dateSeparator, "", $jdYear);
                                $mgjdYear = str_replace($dateSeparator, "", $mgjdYear);
                            } else if ($row['mJdFg'] == 'noDay') {                  //沒有入會日的情況
                                $jdDay = "";
                                $jdMonth = str_replace($dateSeparator, "", $jdMonth);
                            } else if ($row['mJd'] == '') {                         //入會日期為空的情況
                                $jdYear = $mgjdYear = $jdMonth = $jdDay = "";
                            }

                            $jdYear = ($mgFlag == 1) ? $mgjdYear : $jdYear;         //依據西元或民國旗標決定使用西元紀年或民國紀年
                            $joinday = $jdYear . $jdMonth . $jdDay;                 //串接入會年月日為字串

                            /*切割會籍到期日期為年、月、日並計算民國紀年*/
                            if ($row['mPd'] != '') {
                                $edYear   = substr($row['mPd'], 0, 4) . $dateSeparator;
                                $mgedYear = (int)substr($row['mPd'], 0, 4) - 1911 . $dateSeparator;
                                $edMonth  = substr($row['mPd'], 5, 2) . $dateSeparator;
                                $edDay    = substr($row['mPd'], 8, 2);

                                $edYear = ($mgFlag == 1) ? $mgedYear : $edYear;     //依據西元或民國旗標決定使用西元紀年或民國紀年
                                $edDate = $edYear . $edMonth . $edDay;              //串接會籍到期年月日為字串
                            } else {
                                $edDate = '';
                            }

                            /*英文郵遞區號不加在地址之前*/
                            $zipCode1 = (strlen(mb_substr($row['mAd1'], 0, 1, 'utf8')) < 2) ? "" : $row['mZc1'];
                            $zipCode2 = (strlen(mb_substr($row['mAd2'], 0, 1, 'utf8')) < 2) ? "" : $row['mZc2'];

                            /*備註欄出現的日期依曆法旗標進行轉換*/
                            $noteTransDate = $row['mNt'];
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
                                'ID'               => $row['mId'],
                                '會號'             => $row['mNb'],
                                '會號附註'         => $row['mNbNt'],
                                '姓名'             => $row['mNm'],
                                '姓名附註'         => $row['mNmNt'],
                                '別名'             => $row['mAn'],
                                '筆名'             => $row['mPm'],
                                '性別'             => $row['mGd'],
                                '原始生日'         => $row['mBd'],
                                '生日'             => $birthday,
                                '生日旗標'         => $row['mBdFg'],
                                '原始入會日期'     => $row['mJd'],
                                '入會日期'         => $joinday,
                                '入會日期旗標'     => $row['mJdFg'],
                                '籍貫1'            => $row['mAh1'],
                                '籍貫1附註'        => $row['mAh1Nt'],
                                '籍貫1旗標'        => $row['mAh1Fg'],
                                '籍貫2'            => $row['mAh2'],
                                '籍貫2附註'        => $row['mAh2Nt'],
                                '籍貫2旗標'        => $row['mAh2Fg'],
                                '原始郵編1'        => $row['mZc1'],
                                '郵編1'            => $zipCode1,
                                '地址1'            => $row['mAd1'],
                                '地址1附註'        => $row['mAd1Nt'],
                                '地址1旗標'        => $row['mAd1Fg'],
                                '原始郵編2'        => $row['mZc2'],
                                '郵編2'            => $zipCode2,
                                '地址2'            => $row['mAd2'],
                                '地址2附註'        => $row['mAd2Nt'],
                                '地址2旗標'        => $row['mAd2Fg'],
                                '家中電話1'        => $row['mHn1'],
                                '家中電話1旗標'    => $row['mHn1Fg'],
                                '家中電話2'        => $row['mHn2'],
                                '家中電話2旗標'    => $row['mHn2Fg'],
                                '公司電話1'        => $row['mOn1'],
                                '公司電話1附註'    => $row['mOn1Nt'],
                                '公司電話1旗標'    => $row['mOn1Fg'],
                                '公司電話2'        => $row['mOn2'],
                                '公司電話2附註'    => $row['mOn2Nt'],
                                '公司電話2旗標'    => $row['mOn2Fg'],
                                '傳真'             => $row['mFn'],
                                '手機1'            => $row['mPn1'],
                                '手機2'            => $row['mPn2'],
                                'Email1'           => $row['mEa1'],
                                'Email1附註'       => $row['mEa1Nt'],
                                'Email2'           => $row['mEa2'],
                                'Email2附註'       => $row['mEa2Nt'],
                                'Email3'           => $row['mEa3'],
                                'Email3附註'       => $row['mEa3Nt'],
                                '原始備註'         => $row['mNt'],
                                '備註'             => $noteTransDate,
                                '原始會籍到期日期' => $row['mPd'],
                                '會籍到期日期'     => $edDate,
                                '退會旗標'         => $row['mOf'],
                                '榮譽會員旗標'     => $row['mHm'],
                                '已故旗標'         => $row['mDm'],
                                '工作人員旗標'     => $row['mSp'],
                                '更新時間'         => $row['mMt']
                            );
                        }

                        /*陣列長度*/
                        $length = count($data);

                        /*將取出的陣列資料輸出成表格*/
                        for ($i = 0; $i < $length; $i++) {
                            /*將會員資料各欄位串接成 modal_display() 的引數*/
                            $scriptIndex = $data[$i]['ID'] . ", '" . $data[$i]['會號'] . "', '" . $data[$i]['會號附註'] . "', '" . $data[$i]['姓名'] . "', '" . $data[$i]['姓名附註'] . "', '" . $data[$i]['別名'] . "', '" . $data[$i]['筆名'] . "', '" . $data[$i]['性別'] . "', '" . $data[$i]['原始生日'] . "', '" . $data[$i]['生日旗標'] . "', '" . $data[$i]['籍貫1'] . "', '" . $data[$i]['籍貫1附註'] . "', '" . $data[$i]['籍貫1旗標'] . "', '" . $data[$i]['籍貫2'] . "', '" . $data[$i]['籍貫2附註'] . "', '" . $data[$i]['籍貫2旗標'] . "', '" . $data[$i]['原始郵編1'] . "', '" . $data[$i]['地址1'] . "', '" . $data[$i]['地址1附註'] . "', '" . $data[$i]['地址1旗標'] . "', '" . $data[$i]['原始郵編2'] . "', '" . $data[$i]['地址2'] . "', '" . $data[$i]['地址2附註'] . "', '" . $data[$i]['地址2旗標'] . "', '" . $data[$i]['家中電話1'] . "', '" . $data[$i]['家中電話1旗標'] . "', '" . $data[$i]['家中電話2'] . "', '" . $data[$i]['家中電話2旗標'] . "', '" . $data[$i]['公司電話1'] . "', '" . $data[$i]['公司電話1附註'] . "', '" . $data[$i]['公司電話1旗標'] . "', '" . $data[$i]['公司電話2'] . "', '" . $data[$i]['公司電話2附註'] . "', '" . $data[$i]['公司電話2旗標'] . "', '" . $data[$i]['傳真'] . "', '" . $data[$i]['手機1'] . "', '" . $data[$i]['手機2'] . "', '" . $data[$i]['Email1'] . "', '" . $data[$i]['Email1附註'] . "', '" . $data[$i]['Email2'] . "', '" . $data[$i]['Email2附註'] . "', '" . $data[$i]['Email3'] . "', '" . $data[$i]['Email3附註'] . "', '" . $data[$i]['原始備註'] . "', '" . $data[$i]['原始入會日期'] . "', '" . $data[$i]['入會日期旗標'] . "', '" . $data[$i]['原始會籍到期日期'] . "', '" . $data[$i]['退會旗標'] . "', '" . $data[$i]['榮譽會員旗標'] . "', '" . $data[$i]['已故旗標'] . "', '" . $data[$i]['工作人員旗標'] . "'";

                            echo "
                            <tr id=\"member";
                                echo $data[$i]['ID'] . "\"";                        /*每列賦予 ID*/
                                /*榮譽或已故會員當列不同底色*/
                                if ($data[$i]['已故旗標'] == 1) {
                                    echo " class=\"deadMember\"";
                                } else if ($data[$i]['榮譽會員旗標'] == 1) {
                                    echo " class=\"honorMember\"";
                                }
                            echo ">
                                <td class=\"ControlCl\">
                                    <span class=\"TooltipBox\">
                                        <img src=\"edit.png\" alt=\"修改\" class=\"ImageButton\" onclick=\"modal_display('修改', $scriptIndex)\" onmouseover=\"modal_status('修改'); getWindowTop()\">
                                        <span class=\"TooltipText\">修改</span>
                                    </span>
                                    <span class=\"TooltipBox\">
                                        <img src=\"delete.png\" alt=\"刪除\" class=\"ImageButton\" onclick=\"modal_display('刪除', $scriptIndex)\" onmouseover=\"modal_status('刪除'); getWindowTop()\">
                                        <span class=\"TooltipText\">刪除</span>
                                    </span>
                                </td>
                                <td class=\"NumberCl\"><span onclick=\"window.open('circulation.php?Search={$data[$i]['會號']}&Option=mnum&FromTime=1984-12-23&TillTime=$toDay&unrt=1')\">" . $data[$i]['會號'];
                                if ($data[$i]['會號附註'] != "") {
                                    echo "<br><span class=\"annotation numberAnno\">(" . $data[$i]['會號附註'] . ")</span>";
                                }
                                echo "</span></td>";
                                if ($data[$i]['姓名附註'] != "") {
                                    $nameHTML = "<span class=\"nameWithNotes\">" . $data[$i]['姓名'] . "</span>";
                                    $nameNoteHTML = "<span class=\"TooltipText\">" . $data[$i]['姓名附註'] . "</span>";
                                } else {
                                    $nameHTML = $data[$i]['姓名'];
                                    $nameNoteHTML = "";
                                }
                                $nameColor = ($data[$i]['退會旗標'] == 0) ? "blueName" : "greenName";
                                echo "<td class=\"NameCl $nameColor TooltipBox\"><span onclick=\"window.open('circulation.php?Search={$data[$i]['姓名']}&Option=mname&FromTime=1984-12-23&TillTime=$toDay&unrt=1')\">" . $nameHTML;
                                if ($data[$i]['別名'] != "") {
                                    echo "<br><span class=\"annotation nameAnno\">(" . str_replace("、", "<br>", $data[$i]['別名']) . ")</span>";
                                }
                                    echo $nameNoteHTML;
                                echo "<span></td>
                                <td class=\"PseudonymCl\">" . str_replace("、", "<br>", $data[$i]['筆名']) . "</td>
                                <td class=\"GenderCl\">" . $data[$i]['性別'] . "</td>
                                <td class=\"BirthdayCl\">";
                                if ($data[$i]['生日旗標'] == "Feb30") {
                                    echo "<span class=\"indoubt\">" . $data[$i]['生日'] . "</span>";
                                } else {
                                    echo $data[$i]['生日'];
                                }
                                echo "</td>
                                <td class=\"AncestralHomeCl\">";
                                if ($data[$i]['籍貫1旗標'] == "indoubt") {
                                    echo "<span class=\"indoubt\">" . $data[$i]['籍貫1'] . "</span>";
                                } else {
                                    echo $data[$i]['籍貫1'];
                                }
                                if ($data[$i]['籍貫1附註'] != "") {
                                    echo "<br><span class=\"annotation placeAnno\">(" . $data[$i]['籍貫1附註'] . ")</span>";
                                }
                                if ($data[$i]['籍貫2'] != "") {
                                    if ($data[$i]['籍貫2旗標'] == "indoubt") {
                                        echo "<br><span class=\"indoubt\">" . $data[$i]['籍貫2'] . "</span>";
                                    } else {
                                        echo "<br>" . $data[$i]['籍貫2'];
                                    }
                                    if ($data[$i]['籍貫2附註'] != "") {
                                        echo "<br><span class=\"annotation placeAnno\">(" . $data[$i]['籍貫2附註'] . ")</span>";
                                    }
                                }
                                echo "</td>
                                <td class=\"AddressCl\">";
                                if ($data[$i]['地址1旗標'] == "moved") {
                                    echo "<span class=\"movedAddress\">" . $data[$i]['郵編1'] . $data[$i]['地址1'] . "</span>";
                                } else {
                                    if ($data[$i]['地址1旗標'] != "") {
                                        echo "<span class=\"indoubt\">" . $data[$i]['郵編1'] . $data[$i]['地址1'] . "</span>";
                                    } else {
                                        echo $data[$i]['郵編1'] . $data[$i]['地址1'];
                                    }
                                }
                                if ($data[$i]['地址1附註'] != "") {
                                    echo "<span class=\"annotation addrAnno\"> (" . $data[$i]['地址1附註'] . ")</span>";
                                }
                                if ($data[$i]['地址2'] != "") {
                                    if ($data[$i]['地址2旗標'] == "moved") {
                                        echo "<br><span class=\"movedAddress\">" . $data[$i]['郵編2'] . $data[$i]['地址2'] . "</span>";
                                    } else {
                                        if ($data[$i]['地址2旗標'] != "") {
                                            echo "<br><span class=\"indoubt\">" . $data[$i]['郵編2'] . $data[$i]['地址2'] . "</span>";
                                        } else {
                                            echo "<br>" . $data[$i]['郵編2'] . $data[$i]['地址2'];
                                        }
                                    }
                                    if ($data[$i]['地址2附註'] != "") {
                                        echo "<span class=\"annotation addrAnno\"> (" . $data[$i]['地址2附註'] . ")</span>";
                                    }
                                }
                                echo "</td>
                                <td class=\"HomeNumberCl\">";
                                if ($data[$i]['家中電話1旗標'] != "") {
                                    echo "<span class=\"indoubt\">" . str_replace("#", "<br>#", $data[$i]['家中電話1']) . "</span>";
                                } else {
                                    echo str_replace("#", "<br>#", $data[$i]['家中電話1']);
                                }
                                if ($data[$i]['家中電話2'] != "") {
                                    if ($data[$i]['家中電話2旗標'] != "") {
                                        echo "<br><span class=\"indoubt\">" . str_replace("#", "<br>#", $data[$i]['家中電話2']) . "</span>";
                                    } else {
                                        echo "<br>" . str_replace("#", "<br>#", $data[$i]['家中電話2']);
                                    }
                                }
                                echo "</td>
                                <td class=\"OfficeNumberCl\">";
                                if ($data[$i]['公司電話1旗標'] != "") {
                                    echo "<span class=\"indoubt\">" . str_replace("#", "<br>#", $data[$i]['公司電話1']) . "</span>";
                                } else {
                                    echo str_replace("#", "<br>#", $data[$i]['公司電話1']);
                                }
                                if ($data[$i]['公司電話1附註'] != "") {
                                    echo "<span class=\"annotation addrAnno\"> (" . $data[$i]['公司電話1附註'] . ")</span>";
                                }
                                if ($data[$i]['公司電話2'] != "") {
                                    if ($data[$i]['公司電話2旗標'] != "") {
                                        echo "<br><span class=\"indoubt\">" . str_replace("#", "<br>#", $data[$i]['公司電話2']) . "</span>";
                                    } else {
                                        echo "<br>" . str_replace("#", "<br>#", $data[$i]['公司電話2']);
                                    }
                                    if ($data[$i]['公司電話2附註'] != "") {
                                        echo "<span class=\"annotation addrAnno\"> (" . $data[$i]['公司電話2附註'] . ")</span>";
                                    }
                                }
                                echo "</td>
                                <td class=\"FaxNumberCl\">" . str_replace("#", "<br>#", $data[$i]['傳真']) . "</td>
                                <td class=\"PhoneNumberCl\">" . $data[$i]['手機1'];
                                if ($data[$i]['手機2'] != "") {
                                    echo "<br>" . $data[$i]['手機2'];
                                }
                                echo "</td>
                                <td class=\"EmailCl\">" . $data[$i]['Email1'];
                                if ($data[$i]['Email1附註'] != "") {
                                    echo "<span class=\"annotation emailAnno\"> (" . $data[$i]['Email1附註'] . ")</span>";
                                }
                                if ($data[$i]['Email2'] != "") {
                                    echo "<br>" . $data[$i]['Email2'];
                                    if ($data[$i]['Email2附註'] != "") {
                                        echo "<span class=\"annotation emailAnno\"> (" . $data[$i]['Email2附註'] . "</span>";
                                    }
                                }
                                if ($data[$i]['Email3'] != "") {
                                    echo "<br>" . $data[$i]['Email3'];
                                    if ($data[$i]['Email3附註'] != "") {
                                        echo "<span class=\"annotation emailAnno\"> (" . $data[$i]['Email3附註'] . "</span>";
                                    }
                                }
                                echo "</td>
                                <td class=\"JoindayCl\">" . $data[$i]['入會日期'] . "</td>
                                <td class=\"EdCl\">" . $data[$i]['會籍到期日期'] . "</td>
                                <td class=\"NoteCl\">" . $data[$i]['備註'] . "</td>
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
                if (isset($_GET['omi'])) {
                    $searchFinalURL = "&omi=" . $_GET['omi'];
                    if (isset($_GET['dmi'])) $searchFinalURL .= "&dmi=" . $_GET['dmi'];
                } else if (isset($_GET['dmi'])) {
                    $searchFinalURL = "&dmi=" . $_GET['dmi'];
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
                            echo "<a href=\"member.php?$searchStr&rows=$boxRow&page=$i$searchFinalURL\">$i</a>";
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
    <div id="MemberEditBackground" class="ModalBackground">
        <div id="MemberEditContent" class="ModalContent">

            <!--彈窗標題-->
            <h2 id="MemberEditTitle" class="EditTitle"></h2>

            <!--彈窗右上角的關閉按鈕（×）-->
            <span id="MemberModalClose" class="ModalClose" onclick="modal_close()">&times;</span>

            <!--刪除彈窗訊息-->
            <p id="DeleteMessage">確定要刪除這筆會員資料嗎？</p>

            <!--資料表單-->
            <form action="" method="POST" id="MemberModalForm" autocomplete="off" onsubmit="reveal()">
                <input type="text" name="mId" id="mId" class="MemberDataID" readonly>

                <table id="MemberModalFrame1" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%">會號</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 50.5%"><input type="text" name="mNumber" id="mNumber" class="mustfill" placeholder="會號" required></td>
                        <td class="noteLabel" style="width: 6.5%">附註</td>
                        <td class="colon noteLabelColon" style="width: 2.5%">：</td>
                        <td style="width: 31%" title="需要特別註明的會號相關訊息">
                            <input type="text" name="mNumberNote" id="mNumberNote" placeholder="會號附註">
                        </td>
                    </tr>
                </table>
                <table id="MemberModalFrame2" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%">姓名</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 50.5%"><input type="text" name="mName" id="mName" class="mustfill" placeholder="姓名" required></td>
                        <td class="noteLabel" style="width: 6.5%">附註</td>
                        <td class="colon noteLabelColon" style="width: 2.5%">：</td>
                        <td style="width: 31%" title="需要特別註明的姓名相關訊息">
                            <input type="text" name="mNameNote" id="mNameNote" placeholder="姓名附註">
                        </td>
                    </tr>
                </table>
                <table id="MemberModalFrame3" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%">別名</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 39.5%" title="可輸入化名、藝名、出家人的法名……等。如有多個別名，請用頓號「、」分隔">
                            <input type="text" name="mAlias" id="mAlias" placeholder="如：化名、藝名、法名等">
                        </td>
                        <td class="fieldLabel" style="width: 7.7%; padding-left: 0.45rem">筆名</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 40.3%" title="如有多個筆名，請用頓號「、」分隔">
                            <input type="text" name="mPseudonym" id="mPseudonym" placeholder="筆名">
                        </td>
                    </tr>
                </table>
                <table id="MemberModalFrame4" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%">性別</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td class="radioTd" style="width: 19.5%; font-size: 0">
                            <label><input type="radio" name="mGender" id="mGenderM" value="男" required>男</label>
                            <label><input type="radio" name="mGender" id="mGenderF" value="女">女</label>
                        </td>
                        <td class="fieldLabel" style="width: 7.7%; padding-left: 0.35rem">生日</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 30.3%"><input type="date" name="mBirthday" id="mBirthday" class="mustfill"></td>
                        <td class="noteLabel" style="width: 11.5%">異常旗標</td>
                        <td class="colon noteLabelColon" style="width: 2.5%">：</td>
                        <td style="width: 16%">
                            <select name="mBirthdayFlag" id="mBirthdayFlag">
                                <?php
                                    for ($i = 0; $i < count($dateFlag); $i++) {
                                        echo "<option value=\"" . $dateFlag[$i]['val'] . "\">" . $dateFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="MemberModalFrame5" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 6.5%" rowspan="2">籍貫</td>
                        <td class="colon" style="width: 3%" rowspan="2">：</td>
                        <td style="width: 32%"><input type="text" name="mAncestralHome1" id="mAncestralHome1" placeholder="現在地名"></td>
                        <td class="bracketAroundInput" style="width: 2%">(</td>
                        <td style="width: 24.5%"><input type="text" name="mAncestralHomeNote1" id="mAncestralHomeNote1" placeholder="舊地名"></td>
                        <td class="bracketAroundInput" style="width: 2%">)</td>
                        <td class="noteLabel" style="width: 11.5%">異常旗標</td>
                        <td class="colon noteLabelColon" style="width: 2.5%">：</td>
                        <td style="width: 16%">
                            <select name="mAncestralHomeFlag1" id="mAncestralHomeFlag1">
                                <?php
                                    for ($i = 0; $i < count($AHFlag); $i++) {
                                        echo "<option value=\"" . $AHFlag[$i]['val'] . "\">" . $AHFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 32%"><input type="text" name="mAncestralHome2" id="mAncestralHome2" placeholder="現在地名"></td>
                        <td class="bracketAroundInput" style="width: 2%">(</td>
                        <td style="width: 24.5%"><input type="text" name="mAncestralHomeNote2" id="mAncestralHomeNote2" placeholder="舊地名"></td>
                        <td class="bracketAroundInput" style="width: 2%">)</td>
                        <td class="noteLabel" style="width: 11.5%">異常旗標</td>
                        <td class="colon noteLabelColon" style="width: 2.5%">：</td>
                        <td style="width: 16%">
                            <select name="mAncestralHomeFlag2" id="mAncestralHomeFlag2">
                                <?php
                                    for ($i = 0; $i < count($AHFlag); $i++) {
                                        echo "<option value=\"" . $AHFlag[$i]['val'] . "\">" . $AHFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="MemberModalFrame6" class="ModalFrame">
                    <col style="width: 13%">
                    <col style="width: 3%">
                    <col style="width: 14%">
                    <col style="width: 40%">
                    <col style="width: 11.5%">
                    <col style="width: 2.5%">
                    <col style="width: 16%">
                    <tr>
                        <td class="fieldLabel" rowspan="2">地址</td>
                        <td class="colon" rowspan="2">：</td>
                        <td><input type="text" name="mZipCode1" id="mZipCode1" class="mustfill" placeholder="郵遞區號"></td>
                        <td colspan="4"><input type="text" name="mAddress1" id="mAddress1" class="mustfill" placeholder="地址"></td>
                    </tr>
                    <tr style="height: 1.6em">
                        <td colspan="2">
                            <select name="mAddressNote1select" id="mAddressNote1select" onchange="address_other_notes('mAddressNote1')">
                                <?php
                                    for ($i = 0; $i < count($AddrNote); $i++) {
                                        echo "<option value=\"" . $AddrNote[$i]['val'] . "\">" . $AddrNote[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select><input type="text" name="mAddressNote1" id="mAddressNote1" placeholder="請輸入相關的地址訊息">
                        </td>
                        <td class="noteLabel">異常旗標</td>
                        <td class="colon noteLabelColon">：</td>
                        <td>
                            <select name="mAddressFlag1" id="mAddressFlag1">
                                <?php
                                    for ($i = 0; $i < count($AddrFlag); $i++) {
                                        echo "<option value=\"" . $AddrFlag[$i]['val'] . "\">" . $AddrFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr><td colspan="7"><hr class="seg"></td></tr>
                    <tr>
                        <td class="fieldLabel" rowspan="2">其他地址</td>
                        <td class="colon" rowspan="2">：</td>
                        <td><input type="text" name="mZipCode2" id="mZipCode2" placeholder="郵遞區號"></td>
                        <td colspan="4"><input type="text" name="mAddress2" id="mAddress2" placeholder="地址"></td>
                    </tr>
                    <tr style="height: 1.6em">
                        <td colspan="2">
                            <select name="mAddressNote2select" id="mAddressNote2select" onchange="address_other_notes('mAddressNote2')">
                                <?php
                                    for ($i = 0; $i < count($AddrNote); $i++) {
                                        echo "<option value=\"" . $AddrNote[$i]['val'] . "\">" . $AddrNote[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select><input type="text" name="mAddressNote2" id="mAddressNote2" placeholder="請輸入相關的地址訊息">
                        </td>
                        <td class="noteLabel">異常旗標</td>
                        <td class="colon noteLabelColon">：</td>
                        <td>
                            <select name="mAddressFlag2" id="mAddressFlag2">
                                <?php
                                    for ($i = 0; $i < count($AddrFlag); $i++) {
                                        echo "<option value=\"" . $AddrFlag[$i]['val'] . "\">" . $AddrFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="MemberModalFrame7" class="ModalFrame">
                    <col style="width: 13%">
                    <col style="width: 3%">
                    <col style="width: 54%">
                    <col style="width: 11.5%">
                    <col style="width: 2.5%">
                    <col style="width: 16%">
                    <tr>
                        <td class="fieldLabel" rowspan="2">電話(H)</td>
                        <td class="colon" rowspan="2">：</td>
                        <td><input type="text" name="mHomeNumber1" id="mHomeNumber1" class="mustfill" placeholder="家裡電話#1"></td>
                        <td class="noteLabel">異常旗標</td>
                        <td class="colon noteLabelColon">：</td>
                        <td>
                            <select name="mHomeNumberFlag1" id="mHomeNumberFlag1">
                                <?php
                                    for ($i = 0; $i < count($PNFlag); $i++) {
                                        echo "<option value=\"" . $PNFlag[$i]['val'] . "\">" . $PNFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="text" name="mHomeNumber2" id="mHomeNumber2" placeholder="家裡電話#2"></td>
                        <td class="noteLabel">異常旗標</td>
                        <td class="colon noteLabelColon">：</td>
                        <td>
                            <select name="mHomeNumberFlag2" id="mHomeNumberFlag2">
                                <?php
                                    for ($i = 0; $i < count($PNFlag); $i++) {
                                        echo "<option value=\"" . $PNFlag[$i]['val'] . "\">" . $PNFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="MemberModalFrame8" class="ModalFrame">
                    <col style="width: 13%">
                    <col style="width: 3%">
                    <col style="width: 30%">
                    <col style="width: 2%">
                    <col style="width: 20%">
                    <col style="width: 2%">
                    <col style="width: 11.5%">
                    <col style="width: 2.5%">
                    <col style="width: 16%">
                    <tr>
                        <td class="fieldLabel" rowspan="2">電話(O)</td>
                        <td class="colon" rowspan="2">：</td>
                        <td><input type="text" name="mOfficeNumber1" id="mOfficeNumber1" class="mustfill" placeholder="公司電話#1"></td>
                        <td class="bracketAroundInput">(</td>
                        <td><input type="text" name="mOfficeNumberNote1" id="mOfficeNumberNote1" placeholder="附註"></td>
                        <td class="bracketAroundInput">)</td>
                        <td class="noteLabel">異常旗標</td>
                        <td class="colon noteLabelColon">：</td>
                        <td>
                            <select name="mOfficeNumberFlag1" id="mOfficeNumberFlag1">
                                <?php
                                    for ($i = 0; $i < count($PNFlag); $i++) {
                                        echo "<option value=\"" . $PNFlag[$i]['val'] . "\">" . $PNFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><input type="text" name="mOfficeNumber2" id="mOfficeNumber2" placeholder="公司電話#2"></td>
                        <td class="bracketAroundInput">(</td>
                        <td><input type="text" name="mOfficeNumberNote2" id="mOfficeNumberNote2" placeholder="附註"></td>
                        <td class="bracketAroundInput">)</td>
                        <td class="noteLabel">異常旗標</td>
                        <td class="colon noteLabelColon">：</td>
                        <td>
                            <select name="mOfficeNumberFlag2" id="mOfficeNumberFlag2">
                                <?php
                                    for ($i = 0; $i < count($PNFlag); $i++) {
                                        echo "<option value=\"" . $PNFlag[$i]['val'] . "\">" . $PNFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="MemberModalFrame9" class="ModalFrame">
                    <col style="width: 13%">
                    <col style="width: 3%">
                    <col style="width: 41.875%">
                    <col style="width: 0.25%">
                    <col style="width: 41.875%">
                    <tr>
                        <td class="fieldLabel">傳真</td>
                        <td class="colon">：</td>
                        <td colspan="3"><input type="text" name="mFaxNumber" id="mFaxNumber" class="mustfill" placeholder="傳真號碼"></td>
                    </tr>
                    <tr><td colspan="5"><hr class="seg"></td></tr>
                    <tr>
                        <td class="fieldLabel">手機</td>
                        <td class="colon">：</td>
                        <td><input type="text" name="mPhoneNumber1" id="mPhoneNumber1" class="mustfill" placeholder="手機號碼#1"></td>
                        <td></td>
                        <td><input type="text" name="mPhoneNumber2" id="mPhoneNumber2" placeholder="手機號碼#2"></td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="MemberModalFrame10" class="ModalFrame">
                    <col style="width: 13%">
                    <col style="width: 3%">
                    <col style="width: 59%">
                    <col style="width: 6.5%">
                    <col style="width: 2.5%">
                    <col style="width: 16%">
                    <tr>
                        <td class="fieldLabelCenter" rowspan="3">Email</td>
                        <td rowspan="3" class="colon">：</td>
                        <td><input type="text" name="mEmailAddress1" id="mEmailAddress1" class="mustfill" placeholder="Email地址#1"></td>
                        <td class="noteLabel">附註</td>
                        <td class="colon noteLabelColon">：</td>
                        <td><input type="text" name="mEmailAddressNote1" id="mEmailAddressNote1"></td>
                    </tr>
                    <tr>
                        <td><input type="text" name="mEmailAddress2" id="mEmailAddress2" placeholder="Email地址#2"></td>
                        <td class="noteLabel">附註</td>
                        <td class="colon noteLabelColon">：</td>
                        <td><input type="text" name="mEmailAddressNote2" id="mEmailAddressNote2"></td>
                    </tr>
                    <tr>
                        <td><input type="text" name="mEmailAddress3" id="mEmailAddress3" placeholder="Email地址#3"></td>
                        <td class="noteLabel">附註</td>
                        <td class="colon noteLabelColon">：</td>
                        <td><input type="text" name="mEmailAddressNote3" id="mEmailAddressNote3"></td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="MemberModalFrame11" class="ModalFrame">
                    <tr>
                        <td class="fieldLabel" style="width: 13%">備註</td>
                        <td class="colon" style="width: 3%">：</td>
                        <td style="width: 84%"><textarea name="mNote" id="mNote" wrap="hard"></textarea></td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="MemberModalFrame12" class="ModalFrame">
                    <col style="width: 19.5%">
                    <col style="width: 3%">
                    <col style="width: 47.5%">
                    <col style="width: 11.5%">
                    <col style="width: 2.5%">
                    <col style="width: 16%">
                    <tr>
                        <td class="fieldLabel">入會日期</td>
                        <td class="colon">：</td>
                        <td><input type="date" name="mJoinday" id="mJoinday" class="mustfill"></td>
                        <td class="noteLabel">異常旗標</td>
                        <td class="colon noteLabelColon">：</td>
                        <td>
                            <select name="mJoindayFlag" id="mJoindayFlag">
                                <?php
                                    for ($i = 0; $i < count($dateFlag); $i++) {
                                        echo "<option value=\"" . $dateFlag[$i]['val'] . "\">" . $dateFlag[$i]['item'] . "</option>";
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldLabel"><span>會籍到期日期</span></td>
                        <td class="colon">：</td>
                        <td colspan="4"><input type="date" name="mExpirationDate" id="mExpirationDate" class="mustfill"></td>
                    </tr>
                </table>
                <hr class="seg">
                <table id="MemberModalFrame13" class="ModalFrame">
                    <tr>
                        <td style="text-align: center">
                            <label style="margin-right: 1em; margin-left: -2.1em"><input type="checkbox" name="mOutFlag" id="mOutFlag" value="1">退會或不續會</label>
                            <label style="margin-right: 1em"><input type="checkbox" name="mHonorMemberFlag" id="mHonorMemberFlag" value="1">榮譽會員</label>
                            <label style="margin-right: 1em"><input type="checkbox" name="mDeadMemberFlag" id="mDeadMemberFlag" value="1">已故</label>
                            <label><input type="checkbox" name="mSpecFlag" id="mSpecFlag" value="1">工作人員</label>
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

    <script src="member.js"></script>
</body>
</html>
<?php
    mysqli_close($db_link);
?>