<?php
    /*頁面及連線設定*/
    header("Content-Type: text/html; charset=utf-8");                           //宣告頁面字元集與編碼
    require("conn.php");                                                        //引入連線引用檔

    session_start();                                                            //啟動 session

    /*查詢所有未還書紀錄*/
    $c_query  = "SELECT * FROM `circulation` WHERE `cRt` IS NULL";
    $c_result = mysqli_query($db_link, $c_query);
    $c_number = mysqli_num_rows($c_result);

    /*查詢所有已過期紀錄（借閱日期距今超過 28 天）*/
    $o_query  = "SELECT * FROM `circulation` WHERE TIMESTAMPDIFF(DAY, `cBt`, NOW()) > 28 AND `cRt` IS NULL";
    $o_result = mysqli_query($db_link, $o_query);
    $o_number = mysqli_num_rows($o_result);

    /*依會員別查詢已過期紀錄數目*/
    $m_query  = "SELECT `cMn`, `cMm`, COUNT(`cMn`) AS `cUr` FROM `circulation` WHERE TIMESTAMPDIFF(DAY, `cBt`, NOW()) > 28 AND `cRt` IS NULL GROUP BY `cMn` ORDER BY CAST(`cMn` AS UNSIGNED)";
    $m_result = mysqli_query($db_link, $m_query);
    $m_number = mysqli_num_rows($m_result);

    /*查詢快要過期的紀錄（借閱日期距今 22～28 天）*/
    $s_query  = "SELECT * FROM `circulation` WHERE (TIMESTAMPDIFF(DAY, `cBt`, NOW()) > 21 AND TIMESTAMPDIFF(DAY, `cBt`, NOW()) <= 28) AND `cRt` IS NULL";
    $s_result = mysqli_query($db_link, $s_query);
    $s_number = mysqli_num_rows($s_result);

    /*依會員別查詢快要過期的紀錄*/
    $n_query  = "SELECT `cMn`, `cMm`, COUNT(`cMn`) AS `cUr` FROM `circulation` WHERE (TIMESTAMPDIFF(DAY, `cBt`, NOW()) > 21 AND TIMESTAMPDIFF(DAY, `cBt`, NOW()) <= 28) AND `cRt` IS NULL GROUP BY `cMn` ORDER BY CAST(`cMn` AS UNSIGNED)";
    $n_result = mysqli_query($db_link, $n_query);
    $n_number = mysqli_num_rows($n_result);

    include "MemberOutFlag.php";                                                //引入用於每日檢查及設定會員資格的外部檔案

    /*查詢會籍已過期但未超過寬限期的會員*/
    $h_query  = "SELECT * FROM `mlist` WHERE `msOf` = '2'";
    $h_result = mysqli_query($db_link, $h_query);
    $h_number = mysqli_num_rows($h_result);

    $toDay = date("Y-m-d");
?>
<!DOCTYPE html>
<html>
<head>
    <!--宣告 html 頁面編碼-->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <!--宣告為 RWD 響應式網頁-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--宣告頁面標題-->
    <title>華兒文圖書及會員資訊管理系統</title>

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
</head>
<body>
    <?php
        /*檢查是否有登出要求或登入 cookie 是否到期，是則執行登出工作並重整，否則進行下一步檢查登入的動作*/
        if ((isset($_GET['log']) && $_GET['log'] == 'out') || (!isset($_COOKIE['login_status']) && isset($_SESSION['loginMember']))) {
            unset($_SESSION['loginMember']);
            unset($_SESSION['loginName']);
            unset($_SESSION['memberLevel']);
            setcookie("login_status", "Loging in!", time() - 7200);
            header('location: homepage.php');
        } else {
            /*檢查有無登入失敗訊息，無則檢查是否登入*/
            if (!isset($_SESSION['errorMsg']) || $_SESSION['errorMsg'] != 'true') {
                /*檢查是否登入*/
                if (isset($_SESSION['loginMember']) && $_SESSION['loginMember'] != "") {
                    echo "<div id=\"loginText\">" . $_SESSION['loginName'] . "，您好！
                        <a href=\"homepage.php?log=out\" id=\"logout\">登出</a>
                    </div>";
                    setcookie("login_status", "Loging in!", time() + 7200);     //延長 cookie 時間至此次操作後的 2 小時
                } else {
                    echo "<div id=\"loginText\"><span id=\"login\" onclick=\"show_loginMenu()\">登入<br></span>
                        <form method=\"POST\" id=\"loginForm\">
                            帳號：<input type=\"text\" id=\"usernm\" name=\"usernm\"><br>
                            密碼：<input type=\"password\" id=\"passwd\" name=\"passwd\">
                            <div style=\"text-align: center\">
                                <input type=\"submit\" class=\"loginButton\" value=\"登入\">
                                <input type=\"reset\" class=\"loginButton\" value=\"清空\">
                            </div>
                        </form>
                    </div>";
                }
            }
            /*若已有登入失敗訊息，顯示錯誤訊息 3 秒後可重新登入*/
            else {
                echo "<div id=\"loginFailText\">登入失敗！3 秒後重新登入</div>";
                unset($_SESSION['loginMember']);
                unset($_SESSION['errorMsg']);
                header('refresh: 3');
            }
        }

        /*執行登入工作*/
        if (isset($_POST['usernm']) && isset($_POST['passwd'])) {
            /*繫結會員資料庫*/
            $login_query  = "SELECT * FROM `user` WHERE `uUm` = '{$_POST['usernm']}'";
            $login_result = mysqli_query($db_link, $login_query);
            $login_number = mysqli_num_rows($login_result);

            /*若比對不到會員資料，表示帳號輸入錯誤，賦予錯誤訊息後重新整理*/
            if ($login_number == 0) {
                $_SESSION['errorMsg'] = 'true';
                header('refresh: 0');
            }
            /*比對會員資料與帳號相符，繼續登入工作*/
            else {
                /*取出帳號密碼的值*/
                $data = mysqli_fetch_assoc($login_result);                          //username 相同的資料應只有 1 筆，故不必再用 for loop 取出所有值
                $username = $data['uUm'];
                $name     = $data['uNm'];
                $password = $data['uPw'];
                $level    = $data['uLv'];

                /*比對密碼，相符則登入成功，使頁面重新整理，呈現登入狀態*/
                if (password_verify($_POST['passwd'], $password)) {                 //密碼使用 password_hash() 及 password_verify() 加解密，參考 pm/ch07/php_strfun21.php
                    unset($_SESSION['errorMsg']);
                    $_SESSION['loginMember'] = $username;                           //設定登入帳號
                    $_SESSION['loginName']   = $name;                               //設定登入暱稱
                    $_SESSION['memberLevel'] = $level;                              //設定登入權限等級
                    setcookie("login_status", "Loging in!", time() + 7200);         //設定登入 cookie 時間為 2 個小時
                }
                /*密碼不符則賦予登入失敗訊息*/
                else {
                    $_SESSION['errorMsg'] = 'true';
                }
                header('refresh: 0');
            }
        }
    ?>

    <div id="HomeTitleSoftEdge">
        <div id="HomeTitle">
            <h1 id="TitleHome" data-text="中華民國兒童文學學會">中華民國兒童文學學會</h1>
            <h2 id="SubtitleHome" data-text="圖書及會員資訊管理系統">圖書及會員資訊管理系統</h2>
        </div>
    </div>
    <table id="FuntionSwitch">
        <tr>
            <td rowspan="2"><div id="GoToBook" class="switchButton" onclick="window.open('book.php')" data-text="圖書資料管理">圖書資料管理</div></td>
            <td><div id="GoToMember" class="switchButton" onclick="window.open('member.php')" data-text="個人會員資料管理">個人會員資料管理</div></td>
            <td rowspan="2"><div id="GoToCirculation" class="switchButton" onclick="window.open('circulation.php')" data-text="圖書借閱及歸還管理">圖書借閱及歸還管理</div></td>
        </tr>
        <tr>
            <td><div id="GoToSociety" class="switchButton" onclick="window.open('society.php')" data-text="團體會員資料管理">團體會員資料管理</div></td>
        </tr>
    </table>

    <p id="welcome"><span id="greeting"></span><br>今天是 <span id="today"></span>，<br>現在是<span id="present"><span id="timePeriod"></span> <span id="presentTime"></span></span>。</p>

    <div id="informationSwitch">
        <span id="circulationInformation" class="informBtn" onclick="inform_switch('c')">借還書</span>
        <span id="memberInformation" class="informBtn" onclick="inform_switch('m')">會員</span>
    </div>

    <div id="circulationSituation">
        <?php
            if ($o_number > 0) {
                echo "<p class=\"unreturned\">目前有 <span class=\"unreturnedNum\">$c_number</span> 本書借出未還，<br>其中 <span id=\"overdueNum\">$o_number</span> 本已經過期！<br>以下是已過期<span class=\"daystip\">（超過 28 天）</span>的紀錄：</p>
                <table id=\"homepageUrtd\">";
                    while ($urtd = mysqli_fetch_assoc($m_result)) {
                        echo "<tr class=\"clicktr\" onclick=\"window.open('circulation.php?Search={$urtd['cMn']}&Option=mnum&FromTime=1984-12-23&TillTime=$toDay&unrt=1')\"><td class=\"urtdmn\">" . $urtd['cMn'] . "</td><td class=\"urtdmm\">" . $urtd['cMm'] . "</td><td class=\"urtdur\">" . $urtd['cUr'] . " 本</td></tr>";
                    }
                echo "</table>";
            } else {
                echo "<p class=\"unreturned\">目前有 <span class=\"unreturnedNum\">$c_number</span> 本書借出未還，<br><span id=\"noUrtd\">而且沒有任何一本過期！<br>真是太好了～</span></p>";
            }
            if ($n_number > 0) {
                echo "<p class=\"unreturned\">另外還有 <span class=\"unreturnedNum\">$s_number</span> 本書即將到期<br><span class=\"daystip\">（借出超過 3 個禮拜）</span><br>紀錄如下：</p>
                <table id=\"soondueUrtd\">";
                    while ($surtd = mysqli_fetch_assoc($n_result)) {
                        echo "<tr class=\"clicktr\" onclick=\"window.open('circulation.php?Search={$surtd['cMn']}&Option=mnum&FromTime=1984-12-23&TillTime=2018-06-20&unrt=1')\"><td class=\"urtdmn\">" . $surtd['cMn'] . "</td><td class=\"urtdmm\">" . $surtd['cMm'] . "</td><td class=\"urtdur\">" . $surtd['cUr'] . " 本</td></tr>";
                    }
                echo "</table>";
            } else {
                if ($o_number > 0) {
                    echo "<p class=\"unreturned\">目前借出的書沒有快要到期<span class=\"daystip\">（借出超過 3 個禮拜）</span>的！";
                } else {
                    echo "<p class=\"unreturned\">現在也沒有任何一本書即將到期<span class=\"daystip\">（借出超過 3 個禮拜）</span>喔！";
                }
            }
        ?>
    </div>
    <div id="memberSituation">
        <?php
            if ($h_number > 0) {
                echo "<p class=\"unrenewed\">目前有 <span class=\"unrenewedNum\">$h_number</span> 位會員會籍已到期、<br>尚未繳費但未滿 1 年寬限期，<br>名單如下：</p>";
                echo "<table id=\"homepageMbod\">";
                while ($mbod = mysqli_fetch_assoc($h_result)) {
                    echo "<tr class=\"clicktr\" onclick=\"window.open('member.php?Search={$mbod['msNb']}&Option=num')\"><td class=\"mbodn\">" . $mbod['msNb'] . "</td><td class=\"mbodm\">" . $mbod['msNm'] . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class=\"unrenewed\">當前所有會員都已繳交了今年度的會費！</p>";
            }
        ?>
    </div>

    <!--底部版權版本資訊-->
    <div id="includePage"></div>

    <script src="homepage.js"></script>
</body>
</html>