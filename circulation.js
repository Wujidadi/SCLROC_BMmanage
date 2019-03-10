/*將資料庫「ID」最大值（已在 HTML 中取出）宣告為變數*/
var idmax = $("#IDMax")[0].innerHTML;                                           //jQuery 沒有 innerHTML 屬性，要使用它必須以 [0] 的方式來獲取元素

/*將曆法旗標宣告為變數*/
var mgfg = $("#calendar")[0].innerHTML;

/*彈窗可移動*/
$(document).ready(function() {
    $("#CirculationEditContent, #CirculationDealContent").draggable({cancel: "td, h2, span, p, input"});
});

/*為彈窗內部各輸入框及欄位賦值*/
function modal_display(modalstatus, cid, cbnum, cbname, cmnum, cmname, cbt, crt) {

    $("#CirculationEditBackground").show();

    switch (modalstatus) {
        case '修改':
            $("#CirculationEditContent").css("backgroundColor", "white");
            $("#CirculationModalForm input[type!='submit'][type!='button']:not(.CirculationDataID)").css("backgroundColor", "#ffdf");
            $("#CirculationModalForm select").css("backgroundColor", "#ffdf");
            $("#CirculationModalForm textarea").css("backgroundColor", "#ffdf");
            $("#DeleteMessage").hide();
            $("#CirculationEditTitle").text("修改借還書紀錄");
            /*所有物件設為可編輯*/
            $("#CirculationModalForm input[type!='button'][type!='submit']:not(.CirculationDataID)").attr("readonly", false);
            $("#CirculationModalForm input[type='checkbox'], #MemberModalForm input[type='radio'], #MemberModalForm select, #MemberModalForm textarea").attr("disabled", false);
            break;

        case '刪除':
            $("#CirculationEditContent").css("backgroundColor", "#feef");
            $("#CirculationModalForm input[type!='submit'][type!='button']:not(.CirculationDataID)").css("backgroundColor", "#fff8f8ff");
            $("#CirculationModalForm select").css("backgroundColor", "#fff8f8ff");
            $("#CirculationModalForm textarea").css("backgroundColor", "#fff8f8ff");
            $("#CirculationEditTitle").text("刪除借還書紀錄");
            $("#CirculationMessage").show();
            /*所有物件設為不可編輯*/
            $("#CirculationModalForm input[type!='button'][type!='submit']:not(.CirculationDataID)").attr("readonly", true);
            $("#CirculationModalForm input[type='checkbox'], #MemberModalForm input[type='radio'], #MemberModalForm select, #MemberModalForm textarea").attr("disabled", true);
            break;
    }

    $("#cId").val(cid);
    //$("#ScrollView").val("circulation" + cid);                                   //若使用元素 id 定位畫面捲動至的位置，需要此行
    $("#cBookNumber").val(cbnum);
    $("#cBookName").val(cbname);
    $("#cMemberNumber").val(cmnum);
    $("#cMemberName").val(cmname);
    $("#cBorrowTime").val(cbt);
    $("#cReturnTime").val(crt);
}

/*提交表單時必須把 readonly 或 disabled 設為 true 的欄位屬性改回 false，否則無法傳值*/
function reveal() {
    $("#CirculationModalForm input[type!='button'][type!='submit']:not(.MemberDataID)").attr("readonly", false);
    $("#CirculationModalForm input[type='checkbox'], #CirculationModalForm input[type='radio'], #CirculationModalForm select, #CirculationModalForm textarea").attr("disabled", false);
}

/*個人借還書紀錄（新增借還書紀錄）彈窗*/
function add_display() {
    $("#CirculationDealBackground").show();
    $("#cbMn").val("");
    $("#cbMm").val("");
    $("#brdisp").remove();
    $("#remain").remove();
    $("#aPassFlag").val('新增');
    $("#cbMn").focus();
    $("#expire, #soonexpire").remove();
}

/*關閉彈窗*/
function modal_close() {
    if (!$("#CirculationEditBackground").css("display", "none")) {
        $("#CirculationEditBackground").css("display", "none");
    }
    if (!$("#CirculationErrorBackground").css("display", "none")) {
        $("#CirculationErrorBackground").css("display", "none");
    }
    if (!$("#CirculationDealBackground").css("display", "none")) {
        $("#CirculationDealBackground").css("display", "none");
    }
    $("#CirculationEditContent, #CirculationDealContent").css({"left": "0", "top": '50vh'});
}

/*ESC 鍵可關閉彈窗*/
function modal_esc(e) {
    key = (window.event) ? e.keyCode : e.which;                                 //相容 Firefox
    if (($('#CirculationEditBackground').css('display') != "none") && (key == 27)) {
        $("#CirculationEditBackground").css("display", "none");
    }
    if (($('#CirculationErrorBackground').css('display') != "none") && (key == 27)) {
        $("#CirculationErrorBackground").css("display", "none");
    }
    if (($('#CirculationDealBackground').css('display') != "none") && (key == 27)) {
        $("#CirculationDealBackground").css("display", "none");
    }
    $("#CirculationEditContent, #CirculationDealContent").css({"left": "0", "top": '50vh'});
}

/*畫面捲動至已賦予 id 的元素處*/
function scroll_view(coords) {
    var coord = "#" + coords;
    var top = $(coord).offset().top;                                            //取得元素頂部的 y 座標
    window.scrollTo(0, top - 75);                                               //令畫面捲動至比元素高 75px 處
}

/*獲取畫面的 Y 座標*/
function getWindowTop() {
    var winTop = window.scrollY;
    $("#ScrollView").val(winTop);
}

/*畫面捲動至指定的 Y 座標處*/
function scrollView(y) {
    window.scrollTo(0, y);
}

/*顯示錯誤訊息彈窗*/
function error_display() {
    var errinfo = $("#ErrorInfo")[0].innerHTML;                                 //jQuery 沒有 innerHTML 屬性，要使用它必須以 [0] 的方式來獲取元素
    if (errinfo != '') {
        $("#ErrorBackground").css("display", "block");
    }
}

/*限制輸入框只能輸入數字*/
function validateNumber(e, pnumber) {
    if (!/^\d+$/.test(pnumber)) {
        var newValue = /^\d+/.exec(e.value);
        if (newValue != null) {
            e.value = newValue;
        } else {
            e.value = "";
        }
    }
    return false;
}

/*AJAX：輸入書號同時變動書名*/
function show_book(str, tgt) {
    var xmlhttp;
    if (str.length == 0) {
        $("#" + tgt).val("");
        return;
    }
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = setTimeout(function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            $("#" + tgt).val(xmlhttp.responseText);
        }
    }, 400);
    xmlhttp.open("GET", "blist.php?num=" + str, true);
    xmlhttp.send();
}

/*AJAX：輸入會號同時變動會員姓名*/
function show_member(str, tgt) {
    $("#brdisp, #sneer, #expire, #soonexpire, #remain").remove();
    var xmlhttp;
    if (str.length == 0) {
        $("#" + tgt).val("");
        return;
    }
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            var result = xmlhttp.responseText;                                  //將 ajax 回傳值宣告為變數
            var json = eval("(" + result + ")");                                //用 eval() 函數解析 ajax 回傳值（上一行與本行最為重要）
            $("#" + tgt).val(json.nm);
            $("#memberStatus").text(json.fg);
            $("#memberSpecs").text(json.sp);
        }
    }
    xmlhttp.open("GET", "mlist.php?num=" + str, true);
    xmlhttp.send();
}

/*AJAX：點按「還書」按鈕即時將當前時間資訊寫入資料庫，完成登記並展示*/
function book_return(id) {
    var br_ajax = new XMLHttpRequest();
    br_ajax.onreadystatechange = function() {
        if (br_ajax.readyState == 4 && br_ajax.status == 200) {
            var result = br_ajax.responseText;                                  //將 ajax 回傳值宣告為變數
            var json = eval("(" + result + ")");                                //用 eval() 函數解析 ajax 回傳值（上一行與本行最為重要）
            if (mgfg == 1) {
                json.abbrFormat = (Number(json.abbrFormat.substr(0, 4)) - 1911) + "/" + json.abbrFormat.substr(5, 2) + "/" + json.abbrFormat.substr(8, 2);
            }
            $("#crt" + id).html(json.abbrFormat);
            $("#scrt" + id).html(json.fullFormat);
            $("#circulation" + id).removeClass("overdue soondue");
        }
    }
    br_ajax.open("GET", "br.php?id=" + id, true);
    br_ajax.send();
}

/*AJAX：在借還書紀錄新增彈窗中點按「還書」按鈕即時將當前時間資訊寫入資料庫，完成登記*/
function book_return_in_modal(id) {
    var br_ajax = new XMLHttpRequest();
    br_ajax.onreadystatechange = function() {
        if (br_ajax.readyState == 4 && br_ajax.status == 200) {
            var result = br_ajax.responseText;                                  //將 ajax 回傳值宣告為變數
            var json = eval("(" + result + ")");                                //用 eval() 函數解析 ajax 回傳值（上一行與本行最為重要）
            $("#brdrt" + id).html("已還");
        }
    }
    br_ajax.open("GET", "br.php?id=" + id, true);
    br_ajax.send();
}

/*AJAX：在借還書紀錄新增彈窗中輸入會號，下方隨即展示其借書紀錄，及與其還可再借數目相等的空白輸入欄位*/
function circultion_display_num(n) {
    var brd = new XMLHttpRequest();
    brd.onreadystatechange = function() {
        if (brd.readyState == 4 && brd.status == 200) {
            $("#brdisp, #sneer, #expire, #soonexpire").remove();                //此行必須寫在 readyState 和 status 狀態的判斷式中
            var result = brd.responseText;                                      //將 ajax 回傳值宣告為變數
            var json = eval("(" + result + ")");                                //用 eval() 函數解析 ajax 回傳值（上一行與本行最為重要）
            var jlen = json.length;
            var mbfg = $("#memberStatus").text();
            var mspf = $("#memberSpecs").text();
            var k;
            if (mbfg == '1') {
                $("#CirculationDealFrame").after("\
                    <p id=\"expire\">抱歉！您目前不是會員，不能借書～</p>\
                ");
            } else if (mbfg == '2') {
                $("#CirculationDealFrame").after("\
                    <p id=\"soonexpire\">您尚未繳交今年度的會費，記得在年底之前繳交喔！</p>\
                ");
            }
            if (mspf == 1) {
                k = 21;
            } else {
                k = 7;
            }
            if (result != '' && mbfg != '1') {
                $("#CirculationDealFrame").after("\
                    <table id=\"brdisp\">\
                        <tr>\
                            <th>書號</th>\
                            <th>書名</th>\
                            <th>借閱日期</th>\
                            <th></th>\
                        </tr>"
                );
                for (var i = 0; i < jlen; i++) {
                    $("#brdisp").append("\
                        <tr>\
                            <td class=\"brdbn\">" + json[i].bNum  + "</td>\
                            <td class=\"brdbm\">" + json[i].bName + "</td>\
                            <td class=\"brdbt\">" + json[i].bTime.substr(0, 10) + "</td>\
                            <td class=\"brdrt\" id=\"brdrt" + json[i].ID + "\"><button type=\"button\" onclick=\"book_return_in_modal('" + json[i].ID + "')\">還書</button></td>\
                        </tr>"
                    );
                }
                if (jlen < k) {                                                 //每人最多可借本數減去已借本數，剩下的才顯示為可輸入的文字框，
                    for (var j = 0; j < (k - jlen) ; j++) {
                        var time = new Date();
                        $("#brdisp").append("\
                            <tr>\
                                <input type=\"hidden\" name=\"brid-" + j + "\" value=\"" + (Number(idmax) + 1 + j) + "\">\
                                <td class=\"brdbn\"><input type=\"text\" name=\"brbn-" + j + "\" onkeyup=\"show_book(value, 'brbm-" + j + "')\" pattern=\"^[ABCabc]\\d{4}\" tabindex=\"" + (j + 2) + "\"></td>\
                                <td class=\"brdbm\"><input type=\"text\" name=\"brbm-" + j + "\" id=\"brbm-" + j + "\"></td>\
                                <td class=\"brdbt\"><input type=\"text\" name=\"brbt-" + j + "\" value=\"" + timeFormat(time) + "\"></td>\
                                <td class=\"brdrt\"></td>\
                            </tr>"
                        );
                    }
                    $("#brdisp").append("\
                        <input type=\"hidden\" id=\"remain\" name=\"remain\" value=\"" + j + "\">"
                    );
                }
                if (jlen > k) {
                    $("#brdisp").after("\
                        <p id=\"sneer\">您已經借了超過 " + k +" 本書！<br>我不知道您是怎麼做到的，<br>不過看起來在您還書之前，<br>您是不能再借更多書了。</p>\
                    ");
                }
            }
        }
    }
    brd.open("GET", "bmr.php?n=" + n, true);
    brd.send();
}

/*利用腳本設定傳值旗標（修改或插入）*/
function modal_status(s) {
    $("#PassFlag").val(s);
}

/*轉換 JavaScript 時間為標準格式*/
function timeFormat(t) {
    var ty = t.getFullYear();
    var tm = t.getMonth() + 1;
        if (tm < 10) tm = "0" + tm;
    var td = t.getDate();
        if (td < 10) td = "0" + td;
    var th = t.getHours();
        if (th < 10) th = "0" + th;
    var ti = t.getMinutes();
        if (ti < 10) ti = "0" + ti;
    var ts = t.getSeconds();
        if (ts < 10) ts = "0" + ts;
    var tf = ty + "-" + tm + "-" + td + " " + th + ":" + ti + ":" + ts;
    return tf;
}

/*引入底部版權版本資訊檔案*/
$("#includePage").load("footer.html");