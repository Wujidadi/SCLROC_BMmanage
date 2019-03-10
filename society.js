/*將資料庫「ID」最大值（已在 HTML 中取出）宣告為變數*/
var idmax = $("#IDMax")[0].innerHTML;                                           //jQuery 沒有 innerHTML 屬性，要使用它必須以 [0] 的方式來獲取元素

/*彈窗可移動*/
$(document).ready(function() {
    $("#SocietyEditContent").draggable({cancel: "td, h2, span, p, input"});
});

/*為彈窗內部各輸入框及欄位賦值*/
function modal_display(modalstatus, sid, snum, soldnum, snumnote, sname, snamefs, suc, szc, saddr, son1, son2, sfax, spicname, spictitle, spicnote, srpnname, srpntitle, srpnnote, srpnext, srpnphone, srpnemail, sls1name, sls1title, sls1note, sls1ext, sls1phone, sls1email, sls2name, sls2title, sls2note, sls2ext, sls2phone, sls2email, sls3name, sls3title, sls3note, sls3ext, sls3phone, sls3email, sjd, sjdflag, slpd, snote, soutflag) {

    $("#SocietyEditBackground").show();

    /*令彈窗回捲到頂端*/
    $("#SocietyEditContent").scrollTop(0);                                       //切記物件若隱藏，此行無效，所以務必要放在使物件可見的代碼之後

    switch (modalstatus) {
        case '修改':
        case '新增':
            $("#SocietyEditContent").css("backgroundColor", "white");
            $("#SocietyModalForm input[type!='submit'][type!='button']:not(.SocietyDataID)").css("backgroundColor", "#ffdf");
            $("#SocietyModalForm select").css("backgroundColor", "#ffdf");
            $("#SocietyModalForm textarea").css("backgroundColor", "#ffdf");
            $("#SocietyModalForm .mustfill").css("backgroundColor", "papayawhip");
            $("#DeleteMessage").hide();
            if (modalstatus == '修改') {
                $("#SocietyEditTitle").text("修改團體會員資料");
            } else if (modalstatus == '新增') {
                $("#SocietyEditTitle").text("新增團體會員資料");
            }
            /*所有物件設為可編輯*/
            $("#SocietyModalForm input[type!='button'][type!='submit']:not(.SocietyDataID)").attr("readonly", false);
            $("#SocietyModalForm input[type='checkbox'], #SocietyModalForm input[type='radio'], #SocietyModalForm select, #SocietyModalForm textarea").attr("disabled", false);
            break;

        case '刪除':
            $("#SocietyEditContent").css("backgroundColor", "#feef");
            $("#SocietyModalForm input[type!='submit'][type!='button']:not(.SocietyDataID)").css("backgroundColor", "#fff8f8ff");
            $("#SocietyModalForm select").css("backgroundColor", "#fff8f8ff");
            $("#SocietyModalForm textarea").css("backgroundColor", "#fff8f8ff");
            $("#SocietyEditTitle").text("刪除團體會員資料");
            $("#DeleteMessage").show();
            /*所有物件設為不可編輯*/
            $("#SocietyModalForm input[type!='button'][type!='submit']:not(.SocietyDataID)").attr("readonly", true);
            $("#SocietyModalForm input[type='checkbox'], #SocietyModalForm input[type='radio'], #SocietyModalForm select, #SocietyModalForm textarea").attr("disabled", true);
            break;
    }

    $("#sId").val(sid);
    //$("#ScrollView").val("society" + societyidsocietyid);                            //若使用元素 id 定位畫面捲動至的位置，需要此行
    $("#sNumber").val(snum);
    $("#sOldNumber").val(soldnum);
    $("#sNumberNote").val(snumnote);
    $("#sName").val(sname);
    $("#sForShort").val(snamefs);
    $("#sUnifiedCode").val(suc);
    $("#sZipCode").val(szc);
    $("#sAddress").val(saddr);
    $("#sOfficeNumber1").val(son1);
    $("#sOfficeNumber2").val(son2);
    $("#sFaxNumber").val(sfax);
    $("#sPiCName").val(spicname);
    $("#sPiCTitle").val(spictitle);
    $("#sPiCNote").val(spicnote);
    $("#sRpnName").val(srpnname);
    $("#sRpnTitle").val(srpntitle);
    $("#sRpnNote").val(srpnnote);
    $("#sRpnExt").val(srpnext);
    $("#sRpnPhone").val(srpnphone);
    $("#sRpnEmail").val(srpnemail);
    $("#sLs1Name").val(sls1name);
    $("#sLs1Title").val(sls1title);
    $("#sLs1Note").val(sls1note);
    $("#sLs1Ext").val(sls1ext);
    $("#sLs1Phone").val(sls1phone);
    $("#sLs1Email").val(sls1email);
    $("#sLs2Name").val(sls2name);
    $("#sLs2Title").val(sls2title);
    $("#sLs2Note").val(sls2note);
    $("#sLs2Ext").val(sls2ext);
    $("#sLs2Phone").val(sls2phone);
    $("#sLs2Email").val(sls2email);
    $("#sLs3Name").val(sls3name);
    $("#sLs3Title").val(sls3title);
    $("#sLs3Note").val(sls3note);
    $("#sLs3Ext").val(sls3ext);
    $("#sLs3Phone").val(sls3phone);
    $("#sLs3Email").val(sls3email);
    $("#sJoinday").val(sjd);
    switch (sjdflag) {
        case 'noYear':
            $("#sJoinday").attr("title", "原始資料的入會日期沒有年份，所以這裡的年份是不正確的");
            break;
        case 'onlyYear':
            $("#sJoinday").attr("title", "原始資料的入會日期沒有「月」和「日」，所以這裡的月日是不正確的");
            break;
        case 'noDay':
            $("#sJoinday").attr("title", "原始資料的入會日期沒有「日」，所以這裡的日期部分是不正確的");
            break;
        default:
            $("#sJoinday").attr("title", "");
            break;
    }
    $("#sJoindayFlag").val(sjdflag);
    $("#sLastPayDay").val(slpd);
    $("#sNote").val(snote.replace(/<br>/gi, "\r\n"));                           //取代 snote（備註）字串中的 <br> 標籤為換行字元，必須使用 g、i 等正規表示法，否則只會取代第一次出現的 <br>
    if (soutflag == 1) {
        $("#sOutFlag").prop({"checked": true});
    } else {
        $("#sOutFlag").prop({"checked": false});
    }
}

/*地址欄附註訊息選中「其他」時，自動顯現訊息輸入框，否則隱藏之*/
function address_other_notes(a) {
    var opt = $("#" + a + "select").val();
    if (opt == '其他') {
        $("#" + a + "select").css("width", "30%");
        $("#" + a).show();
        $("#" + a).css("width", "70%");
    } else {
        $("#" + a).hide();
        $("#" + a + "select").css("width", "100%");
    }
}

/*提交表單時必須把 readonly 或 disabled 設為 true 的欄位屬性改回 false，否則無法傳值*/
function reveal() {
    $("#SocietyModalForm input[type!='button'][type!='submit']:not(.SocietyDataID)").attr("readonly", false);
    $("#SocietyModalForm input[type='checkbox'], #SocietyModalForm input[type='radio'], #SocietyModalForm select, #SocietyModalForm textarea").attr("disabled", false);
}

/*關閉彈窗*/
function modal_close() {
    if (!$("#SocietyEditBackground").css("display", "none")) {
        $("#SocietyEditBackground").css("display", "none");
    }
    if (!$("#SocietyErrorBackground").css("display", "none")) {
        $("#SocietyErrorBackground").css("display", "none");
    }
    $("#SocietyEditContent").css({"left": "0", "top": '50vh'});
}

/*ESC 鍵可關閉彈窗*/
function modal_esc(e) {
    key = (window.event) ? e.keyCode : e.which;                                 //相容 Firefox
    if (($('#SocietyEditBackground').css('display') != "none") && (key == 27)) {
        $("#SocietyEditBackground").css("display", "none");
    }
    if (($('#SocietyErrorBackground').css('display') != "none") && (key == 27)) {
        $("#SocietyErrorBackground").css("display", "none");
    }
    $("#SocietyEditContent").css({"left": "0", "top": '50vh'});
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

/*顯示/隱藏筆名欄*/
function pn_hide() {
    $("#PseudonymCol").toggle();
    $("#PseudonymHd").toggle();
    $(".PseudonymCl").toggle();
    if ($("#PseudonymHd").is(":hidden")) {
        $("#PNHide").html("<img src=\"arrow_expand_right.png\"><span class=\"TooltipText\">顯示筆名</span>");
    } else {
        $("#PNHide").html("<img src=\"arrow_expand_left.png\"><span class=\"TooltipText\">隱藏筆名</span>");
    }
}

/*顯示/隱藏籍貫欄*/
function ah_hide() {
    $("#AncestralHomeCol").toggle();
    $("#AncestralHomeHd").toggle();
    $(".AncestralHomeCl").toggle();
    if ($("#AncestralHomeHd").is(":hidden")) {
        $("#AHHide").html("<img src=\"arrow_expand_right.png\"><span class=\"TooltipText\">顯示籍貫</span>");
    } else {
        $("#AHHide").html("<img src=\"arrow_expand_left.png\"><span class=\"TooltipText\">隱藏籍貫</span>");
    }
}

// $("#SocietyModalFrame1").click(function() {
//     var x = $("#sId")[0].clientWidth;
//     $("#pkg").text(x);
// });

/*引入底部版權版本資訊檔案*/
$("#includePage").load("footer.html");