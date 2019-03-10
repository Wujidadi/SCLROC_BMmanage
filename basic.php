<?php
    /*頁面及連線設定*/
    header("Content-Type: text/html; charset=utf-8");                           //宣告頁面字元集與編碼
    require("conn.php");                                                        //引入連線引用檔
?>
<html>
<head>
    <!--宣告 html 頁面編碼-->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <!--宣告為 RWD 響應式網頁-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--宣告頁面標題-->
    <title></title>

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
    <!--底部版權版本資訊-->
    <div id="includePage"></div>

    <script>
        /*引入底部版權版本資訊檔案*/
        $("#includePage").load("footer.html");
    </script>
</body>
</html>