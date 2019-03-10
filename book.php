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

    /*資料庫查詢設定－查詢所有書籍*/
    $total_query   = 'SELECT * FROM `book`';                                    //設定搜尋條件為所有書籍資料
    $total_result  = mysqli_query($db_link, $total_query);                      //查詢所有書籍
    $total_number  = mysqli_num_rows($total_result);                            //計算書籍總數

    /*資料庫查詢設定－查詢未除帳書籍*/
    $extant_query  = 'SELECT * FROM `book` WHERE `bDf` != 1 ORDER BY `bNb`';    //僅列出「未除帳」的書籍，故篩選刪除旗標不等於 1（True）者，並依書號排序
    $extant_result = mysqli_query($db_link, $extant_query);                     //查詢所有未除帳書籍
    $extant_number = mysqli_num_rows($extant_result);                           //計算未除帳書籍總數

    /*設定資料分頁*/
    include "ini.php";                                                          //引入寫在外部檔案內的預設每頁筆數
    $page_number = 1;                                                           //預設頁數
    if (isset($_GET['rows'])) {                                                 //若使用者已輸入每頁筆數
        if ($_GET['rows'] != '') {                                              //且此筆數不為空值
            $page_row = $_GET['rows'];                                          //依使用者所輸入的每頁筆數設置每頁顯示筆數
        } else if ($_GET['rows'] == '') {                                       //但若使用者輸入的筆數為空值
            $_GET['rows'] = $page_row_book;                                     //以預設每頁筆數取代該空值
            $page_row = $_GET['rows'];                                          //同時以此預設每頁筆數為準來顯示本頁資料
        }
    } else {
        $page_row = $page_row_book;                                             //若使用者根本沒有輸入每頁筆數，直接套用外部檔案的預設每頁筆數
    }
    if (isset($_GET['page'])) {
        $page_number = $_GET['page'];                                           //依使用者所選頁數值更新頁數
    } else if (isset($_POST['PageNumber'])) {
        $page_number = $_POST['PageNumber'];                                    //依彈窗表單送出時所在頁面更新頁數
    }
    $start_row     = $page_row * ($page_number - 1);                            //設定每頁從第 N 筆開始顯示，N = (頁數 - 1) * 每頁筆數
    $limit_query   = $extant_query . ' LIMIT ' . $start_row . ', ' . $page_row; //查詢語法加上限制筆數，由第 N 筆起顯示所設定的筆數
    $limit_result  = mysqli_query($db_link, $limit_query);                      //依限制條件查詢
    $extant_pages  = ceil($extant_number / $page_row);                          //計算未除帳書籍的總頁數 = (未除帳書籍總數 ÷ 每頁筆數) 並無條件進位

    /*取得當前資料庫中「ID」的最大值*/
    $id_max_query = 'SELECT MAX(`bId`) FROM `book`';
    $id_max_result = mysqli_query($db_link, $id_max_query);
    $id_max = @mysqli_fetch_row($id_max_result);

    /*取得各書區書號的最大值，並設立（新增時的）建議書號*/
    $a_max_query  = "SELECT MAX(SUBSTRING(`bNb`, 2, 4)) FROM `book` WHERE SUBSTRING(`bNb`, 1, 1) = 'A'";
    $b_max_query  = "SELECT MAX(SUBSTRING(`bNb`, 2, 4)) FROM `book` WHERE SUBSTRING(`bNb`, 1, 1) = 'B'";
    $c_max_query  = "SELECT MAX(SUBSTRING(`bNb`, 2, 4)) FROM `book` WHERE SUBSTRING(`bNb`, 1, 1) = 'C'";
    $a_max_result = mysqli_query($db_link, $a_max_query);
    $b_max_result = mysqli_query($db_link, $b_max_query);
    $c_max_result = mysqli_query($db_link, $c_max_query);
    $a_max = @mysqli_fetch_row($a_max_result);
    $b_max = @mysqli_fetch_row($b_max_result);
    $c_max = @mysqli_fetch_row($c_max_result);
    //echo $a_max[0] . "<br>" . $b_max[0] . "<br>" . $c_max[0];
    $a_sug_num = "A" . str_pad(($a_max[0] + 1), 4, "0", STR_PAD_LEFT);
    $b_sug_num = "B" . str_pad(($b_max[0] + 1), 4, "0", STR_PAD_LEFT);
    $c_sug_num = "C" . str_pad(($c_max[0] + 1), 4, "0", STR_PAD_LEFT);
    //echo $a_sug_num . "<br>" . $b_sug_num . "<br>" . $c_sug_num;

    if (isset($_POST['PassFlag'])) {
        /*將彈窗表單傳回的各項值，重新命名為名稱稍短、且可直接在雙引號中引用的變數，順便解決了有些值為 NULL 的問題*/
        $PbId = $_POST['bId'];
        $PbNumber = ($_POST['bNumber'] == '') ? 'NULL' : "'" . $_POST['bNumber'] . "'";
        $PbName = ($_POST['bName'] == '') ? 'NULL' : "'" . $_POST['bName'] . "'";
        $PbAuthor = ($_POST['bAuthor'] == '') ? 'NULL' : "'" . $_POST['bAuthor'] . "'";
        $PbIllustrator = ($_POST['bIllustrator'] == '') ? 'NULL' : "'" . $_POST['bIllustrator'] . "'";
        $PbTranslator = ($_POST['bTranslator'] == '') ? 'NULL' : "'" . $_POST['bTranslator'] . "'";
        $PbPublisher = ($_POST['bPublisher'] == '') ? 'NULL' : "'" . $_POST['bPublisher'] . "'";
        $PbContainerNumber = ($_POST['bContainerNumber'] == '') ? 'NULL' : "'" . $_POST['bContainerNumber'] . "'";
        $PbNote = ($_POST['bNote'] == '') ? 'NULL' : "'" . $_POST['bNote'] . "'";

        /*傳值旗標為「修改」時更新資料庫*/
        if ($_POST['PassFlag'] == '修改') {
            /*更新書籍資料表*/
            $edit_query  = "UPDATE `book` SET ";
            $edit_query .= "`bNb` = $PbNumber, ";
            $edit_query .= "`bNm` = $PbName, ";
            $edit_query .= "`bAt` = $PbAuthor, ";
            $edit_query .= "`bIl` = $PbIllustrator, ";
            $edit_query .= "`bTr` = $PbTranslator, ";
            $edit_query .= "`bPb` = $PbPublisher, ";
            $edit_query .= "`bCn` = $PbContainerNumber, ";
            $edit_query .= "`bNt` = $PbNote ";
            $edit_query .= "WHERE `bId` = $PbId";
            $edit_result = mysqli_query($db_link, $edit_query);
            /*更新借還書紀錄資料表*/
            $change_select_query  = "UPDATE `circulation` SET `cBm` = $PbName WHERE `cBn` = $PbNumber";
            $change_select_result = mysqli_query($db_link, $change_select_query);
        }
        /*傳值旗標為「插入」時新增資料到資料庫*/
        else if ($_POST['PassFlag'] == '插入') {
            $insert_query  = "INSERT INTO `book` (`bId`, `bNb`, `bNm`, `bAt`, `bIl`, `bTr`, `bPb`, `bCn`, `bNt`, `bDf`) VALUES ";
            $insert_query .= "(NULL, $PbNumber, $PbName, $PbAuthor, $PbIllustrator, $PbTranslator, $PbPublisher, $PbContainerNumber, $PbNote, '0')";
            $insert_result = mysqli_query($db_link, $insert_query);
        }
        /*傳值旗標為「除帳」時將該筆資料的刪除旗標設為 0*/
        else if ($_POST['PassFlag'] == '除帳') {
            $delete_query  = "UPDATE `book` SET `bDf` = '1', `bDt` = '" . date("Y-m-d H:i:s") . "' WHERE `bId` = $PbId";
            $delete_result = mysqli_query($db_link, $delete_query);
        }
        /*若更新資料失敗（如輸入已存在的書號）則顯示錯誤訊息，更新資料成功則重新整理頁面*/
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
        for ($i = 0; $i < count($bookSearchItem); $i++) {
            if ($_GET['Option'] == $bookSearchItem[$i]['item']) {
                $searchWords = $bookSearchItem[$i]['sql'];
                break;
            }
        }

        /*防範隱碼攻擊*/
        $search_keyword = filter_var($_GET['Search'], FILTER_SANITIZE_MAGIC_QUOTES);

        /*結合搜尋條件的查詢語法*/
        $searchPrefix  = "SELECT * FROM `book` WHERE ";
        $searchTarget  = " LIKE '%" . $search_keyword . "%' AND `bDf` != 1";
        $search_query  = $searchPrefix . $searchWords . $searchTarget;

        /*測試語法是否正確*/
        //echo $search_query . "<br>" . $_GET['Option'] . "<br>" . $searchWords;

        $search_result = mysqli_query($db_link, $search_query);
        $search_number = mysqli_num_rows($search_result);
        $search_pages  = ceil($search_number / $page_row);                  //計算符合搜尋條件書籍的總頁數 = (結果數 ÷ 每頁筆數) 並無條件進位

        /*搜尋條件加上筆數及頁數限制*/
        $search_limit_query  = $search_query . ' ORDER BY `bNb` LIMIT ' . $start_row . ', ' . $page_row;
        $search_limit_result = mysqli_query($db_link, $search_limit_query);
        $search_limit_number = mysqli_num_rows($search_limit_result);
    }

    $toDay = date("Y-m-d");
?>

<!DOCTYPE html>
<html>
<head>
    <!--宣告 html 頁面編碼-->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <!--宣告為 RWD 響應式網頁-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--宣告頁面標題，依查詢所得書籍數量動態變化-->
    <title>圖書資料管理 (現有<?php echo $extant_number; ?>本書)</title>

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
<body id="bookBody" onload="<?php if (isset($_POST['PassFlag'])) echo "scrollView('" . $_POST['ScrollView'] . "'); "; ?>error_display()" onkeydown="modal_esc(event)">
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

    <?php
        /*隱藏區域，存放目前資料庫中「ID」及 A、B、C 三個書區書號的最大值*/
        echo "<p id=\"IDMax\">" . $id_max[0] . "</p>";
        echo "<p id=\"aMax\" class=\"baMax\">" . $a_sug_num . "</p>";
        echo "<p id=\"bMax\" class=\"baMax\">" . $b_sug_num . "</p>";
        echo "<p id=\"cMax\" class=\"baMax\">" . $c_sug_num . "</p>";
    ?>

    <h1 id="TitleBook">圖書資料管理</h1>

    <?php if ($adminFlag == "true") { ?>
    <!--新增借還書紀錄資料按鈕-->
    <img id="addBookButton" src="add_book_button.png" onmouseout="this.src='add_book_button.png'" onmouseover="this.src='add_book_button_invert.png'; modal_status('插入')" onclick="add_display()">
    <?php } ?>

    <!--選擇筆數及頁數表單-->
    <form action="" method="GET" id="BookSearcher">
        <table id="bookSearchBox">
            <tr>
                <td class="bFirstCol">
                    <!--搜尋功能-->
                    <input type="text" id="bSearch" name="Search" value="<?php if (isset($_GET["Search"])) echo $_GET["Search"]; ?>">
                </td>
                <td class="bSecondCol">
                    <select id="bOption" name="Option">
                        <?php
                            /*在下拉式選單中依序列出搜尋條件*/
                            for ($i = 0; $i < count($bookSearchItem); $i++) {
                                echo "<option value=\"" . $bookSearchItem[$i]['item'] . "\"";
                                if ((isset($_GET["Option"])) && ($bookSearchItem[$i]['item'] == $_GET["Option"])) echo "selected";
                                echo ">" . $bookSearchItem[$i]['item'] . "</option>";
                            }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="bSearchResult">
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
                        // if ($display_num == 0) echo 1;
                        // echo "\"> 頁";

                        /*當使用者有更動每頁顯示筆數時，才更動外部檔案中設定的預設每頁筆數*/
                        if ((isset($_GET['rows'])) && ($_GET['rows'] != '') && ($_GET['rows'] != $page_row_book)) {
                            $init = file("ini.php") or die("Unable to open file!");         //將外部設定檔的內容取出為陣列
                            $init[2] = "    \$page_row_book = " . $_GET['rows'] . ";\n";    //改寫預設頁數的初始值
                            $inittext = implode('', $init);                                 //將取出的陣列重新組合在一起
                            file_put_contents("ini.php", $inittext);                        //寫入修改過的檔案內容
                        }
                    ?>
                </td>
                <td>
                    <input type="submit" id="bSubmit" value="搜尋">
                </td>
            </tr>
        </table>
    </form>

    <?php
        // /*更改搜尋條件時，若原先的所在頁數超過新搜尋結果的總頁數，顯示警告訊息並提示*/
        // if ($page_number > $result_pages) {
        //     echo "<div class=\"overPage\">當前所在頁數超過搜索總頁數！<br>請再按一次「搜尋」就可以跳到搜索結果第 1 頁囉～</div>";
        // };

        /*未設定搜尋條件時，顯示所有未除帳（即現存）的書籍資料，否則顯示符合搜尋條件的書籍資料*/
        if ((!isset($_GET['Search']) || ($_GET['Search'] == '')) || (!isset($_GET['Option']) || ($_GET['Option'] == ''))) {
            $display_result = $limit_result;
        } else {
            $display_result = $search_limit_result;
        }

        /*搜尋結果 > 0 時才顯示表格*/
        if (mysqli_num_rows($display_result) > 0) {
            /*以表格顯示書籍資料*/
            echo "<table id=\"BookData\">
                <!--各欄-->
                <col id=\"ControlColumn\">
                <col id=\"NumberColumn\">
                <col id=\"NameColumn\">
                <col id=\"AuthorColumn\">
                <col id=\"IllustratorColumn\">
                <col id=\"TranslatorColumn\">
                <col id=\"PublisherColumn\">
                <col id=\"ContainerNumberColumn\">
                <col id=\"NoteColumn\">

                <!--表頭-->
                <thead>
                    <tr id=\"Head\">
                        <th id=\"ControlHead\"></th>
                        <th id=\"NumberHead\">書號</th>
                        <th id=\"NameHead\">書名</th>
                        <th id=\"AuthorHead\">作者</th>
                        <th id=\"IllustratorHead\">繪者</th>
                        <th id=\"TranslatorHead\">譯者</th>
                        <th id=\"PublisherHead\">出版者</th>
                        <th id=\"ContainerNumberHead\">櫃號</th>
                        <th id=\"NoteHead\">備註</th>
                    </tr>
                </thead>

                <tbody>";
                    /*將書籍資料依限制條件分頁後輸出為陣列*/
                    while ($row = mysqli_fetch_assoc($display_result)) {
                        $Area = substr($row['bNb'], 0, 1);                      //取出書號第一位英文字母為書區
                        $Serial = substr($row['bNb'], 1);                       //取出書號的數字部分為純編號
                        $data[] = array(                                        //書籍資料各欄位轉為陣列
                            'ID'     => $row['bId'],
                            '書號'   => $row['bNb'],
                            '書區'   => $Area,
                            '編號'   => $Serial,
                            '書名'   => $row['bNm'],
                            '作者'   => $row['bAt'],
                            '繪者'   => $row['bIl'],
                            '譯者'   => $row['bTr'],
                            '出版者' => $row['bPb'],
                            '櫃號'   => $row['bCn'],
                            '備註'   => $row['bNt']
                        );
                    }

                    /*陣列長度*/
                    $length = count($data);

                    /*將取出的陣列資料輸出成表格*/
                    for ($i = 0; $i < $length; $i++) {
                        /*將書籍資料各欄位串接成 edit/insert/delete_display() 的引數*/
                        $scriptIndex = $data[$i]['ID'] . ", '" . $data[$i]['書區'] . "', '" . $data[$i]['編號'] . "', '" . $data[$i]['書名'] . "', '" . $data[$i]['作者'] . "', '" . $data[$i]['繪者'] . "', '" . $data[$i]['譯者'] . "', '" . $data[$i]['出版者'] . "', '" . $data[$i]['櫃號'] . "', '" . $data[$i]['備註'] . "'";
                        $blankScriptIndex = $data[$i]['ID'] . ", '" . $data[$i]['書區'] . "', '" . $data[$i]['編號'] . "'";

                        echo "
                        <tr id=\"book" . $data[$i]['ID'] /*每列賦予 ID*/ . "\">
                            <td class=\"ControlCell\">";
                            if ($adminFlag == "true") {
                                echo "<span class=\"TooltipBox\">
                                    <img src=\"edit.png\" alt=\"修改\" class=\"ImageButton\" onclick=\"edit_display($scriptIndex)\" onmouseover=\"modal_status('修改'); getWindowTop()\">
                                    <span class=\"TooltipText\">修改</span>
                                </span>
                                <span class=\"TooltipBox\">";
                                    /*第一列至倒數第二列「向下新增」按鈕的處理方式：若下一筆資料書號為本筆 + 1 才可向下新增，否則不可*/
                                    if ($i < $length - 1) {
                                        if (($data[$i]['書區'] == $data[$i + 1]['書區']) && ($data[$i]['編號'] == $data[$i + 1]['編號'] - 1)) {
                                            echo "    <img src=\"insert_x.png\" alt=\"插入\" class=\"ImageButtonDisabled\" onclick=\"\">
                                                  <span class=\"TooltipText\">連號不可插入</span>";
                                        } else {
                                            echo "    <img src=\"insert.png\" alt=\"插入\" class=\"ImageButton\" onclick=\"insert_display($blankScriptIndex)\" onmouseover=\"modal_status('插入'); getWindowTop()\">
                                                  <span class=\"TooltipText\">向下插入一筆</span>";
                                        }
                                    }
                                    /*最後一列「向下新增」按鈕的處理方式：重新查詢資料庫中有無書號 + 1 的資料，有則可新增，否則不可*/
                                    else {
                                        $FillSerial = sprintf("%04d", $data[$i]['編號'] + 1);
                                        $next_datum_query  = 'SELECT * FROM `book` WHERE `bNb` LIKE \'' . $data[$i]['書區'] . $FillSerial . '\'';
                                        $next_datum_result = mysqli_query($db_link, $next_datum_query);
                                        $next_datum_number = mysqli_num_rows($next_datum_result);
                                        if ($next_datum_number) {
                                            echo "    <img src=\"insert_x.png\" alt=\"插入\" class=\"ImageButtonDisabled\" onclick=\"\">
                                                  <span class=\"TooltipText\">連號不可插入</span>";
                                        } else {
                                            echo "    <img src=\"insert.png\" alt=\"插入\" class=\"ImageButton\" onclick=\"insert_display($scriptIndex)\" onmouseover=\"modal_status('插入'); getWindowTop()\">
                                                  <span class=\"TooltipText\">向下插入一筆</span>";
                                        }
                                    }
                                    echo "
                                </span>
                                <span class=\"TooltipBox\">
                                    <img src=\"delete.png\" alt=\"刪除\" class=\"ImageButton\" onclick=\"delete_display($scriptIndex)\" onmouseover=\"modal_status('除帳'); getWindowTop()\">
                                    <span class=\"TooltipText\">刪除 (除帳)</span>
                                </span>";
                            }
                            echo "</td>
                            <td class=\"NumberCell\"><span onclick=\"window.open('circulation.php?Search={$data[$i]['書號']}&Option=bnum&FromTime=1984-12-23&TillTime=$toDay&unrt=1')\">" . $data[$i]['書號'] . "</span></td>
                            <td class=\"NameCell\"><span onclick=\"window.open('circulation.php?Search={$data[$i]['書名']}&Option=bname&FromTime=1984-12-23&TillTime=$toDay&unrt=1')\">" . $data[$i]['書名'] . "</span></td>
                            <td class=\"AuthorCell\">"          . $data[$i]['作者']   . "</td>
                            <td class=\"IllustratorCell\">"     . $data[$i]['繪者']   . "</td>
                            <td class=\"TranslatorCell\">"      . $data[$i]['譯者']   . "</td>
                            <td class=\"PublisherCell\">"       . $data[$i]['出版者'] . "</td>
                            <td class=\"ContainerNumberCell\">" . $data[$i]['櫃號']   . "</td>
                            <td class=\"NoteCell\">"            . $data[$i]['備註']   . "</td>
                        </tr>";
                    }
                    echo "
                    </tbody>
                </table>
                <div class=\"pageBoxPack\">";

            /*使用者有選定每頁顯示筆數或頁數時，下方頁數選擇鈕的參數等於其值，否則等於預設值*/
            if (isset($_GET['rows'])) $boxRow = $_GET['rows']; else $boxRow = $page_row;

            /*使用者有輸入搜尋條件時，下方頁數選擇鈕的參數加入該條件，否則忽略*/
            if (isset($_GET['Search'])) $searchStr = "&Search=" . $search_keyword . "&Option=" . $_GET['Option']; else $searchStr = "";

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
                        echo "<a href=\"book.php?rows=$boxRow&page=$i$searchStr\">$i</a>";
                    } else {
                        echo $i;
                    }
                    echo "</div>";
                }
            }
            echo "</div>";
        }
    ?>

    <!--修改、新增及刪除資料所用的彈窗表單-->
    <div id="BookEditBackground" class="ModalBackground">
        <div id="BookEditContent" class="ModalContent">

            <!--彈窗標題-->
            <h2 id="BookEditTitle" class="EditTitle"></h2>

            <!--彈窗右上角的關閉按鈕（×）-->
            <span class="ModalClose" onclick="modal_close()">&times;</span>

            <!--刪除彈窗訊息-->
            <p id="DeleteMessage">確定要把這本書除帳嗎？</p>

            <!--資料表單-->
            <form action="" method="POST" id="BookModalForm" class="ModalForm">
                <input type="text" name="bId" id="bId" class="BookDataID" readonly>

                <table id="BookModalFrame" class="ModalFrame">
                    <col style="width: 13%">
                    <col style="width: 5%">
                    <col style="width: 82%">
                    <tr>
                        <td class="fieldLabel">書號</td>
                        <td>：</td>
                        <td><input type="text" name="bNumber" id="bNumber" required></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">書名</td>
                        <td>：</td>
                        <td><input type="text" name="bName" id="bName" required></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">作者</td>
                        <td>：</td>
                        <td><input type="text" name="bAuthor" id="bAuthor"></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">繪者</td>
                        <td>：</td>
                        <td><input type="text" name="bIllustrator" id="bIllustrator"></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">譯者</td>
                        <td>：</td>
                        <td><input type="text" name="bTranslator" id="bTranslator"></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">出版者</td>
                        <td>：</td>
                        <td><input type="text" name="bPublisher" id="bPublisher"></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">櫃號</td>
                        <td>：</td>
                        <td><input type="text" name="bContainerNumber" id="bContainerNumber"></td>
                    </tr>
                    <tr>
                        <td class="fieldLabel">備註</td>
                        <td>：</td>
                        <td><input type="text" name="bNote" id="bNote"></td>
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

                <!--利用腳本設定傳值旗標（修改或插入）-->
                <script>
                    function modal_status(s) {
                        $("#PassFlag").val(s);
                    }
                </script>
            </form>
        </div>
    </div>

    <!--錯誤訊息彈窗-->
    <div id="BookErrorBackground" class="ModalErrorBackground">
        <div id="BookErrorBox" class="ModalErrorBox">
            <!--彈窗右上角的關閉按鈕（×）-->
            <span class="ModalClose" onclick="modal_close()">&times;</span>

            <!--錯誤訊息-->
            <p>輸入了重複的書號！</p>
        </div>
    </div>

    <!--底部版權版本資訊-->
    <div id="includePage"></div>

    <script src="book.js"></script>
</body>
</html>
<?php
    mysqli_close($db_link);
?>