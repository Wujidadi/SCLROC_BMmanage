/*獲取當前日期*/
var today = new Date();

function year(t) {
    var y = t.getFullYear();
    return y;
}

function month(t) {
    var m = t.getMonth() + 1;
    if (m < 10) m = "0" + m;
    return m;
}

function day(t) {
    var d = t.getDate();
    if (d < 10) d = "0" + d;
    return d;
}

/*將資料庫「ID」最大值（已在 HTML 中取出）宣告為變數*/
var idmax = $("#IDMax")[0].innerHTML;                                           //jQuery 沒有 innerHTML 屬性，要使用它必須以 [0] 的方式來獲取元素

/*彈窗可移動*/
$(document).ready(function() {
    $("#MemberEditContent").draggable({cancel: "td, h2, span, p, input"});
});

/*為彈窗內部各輸入框及欄位賦值*/
function modal_display(modalstatus, memberid, membernumber, membernumbernote, membername, membernamenote, malias, mpseudonym, mgender, mbirthday, mbirthdayflag, mancestralhome1, mancestralhome1note, mancestralhome1flag, mancestralhome2, mancestralhome2note, mancestralhome2flag, mzipcode1, maddress1, maddress1note, maddress1flag, mzipcode2, maddress2, maddress2note, maddress2flag, mhomenumber1, mhomenumber1flag, mhomenumber2, mhomenumber2flag, mofficenumber1, mofficenumber1note, mofficenumber1flag, mofficenumber2, mofficenumber2note, mofficenumber2flag, mfaxnumber, mphonenumber1, mphonenumber2, memail1, memail1note, memail2, memail2note, memail3, memail3note, mnote, mjoinday, mjoindayflag, mExpirationDate, moutflag, mhonorflag, mdeadflag, mspecflag) {

    $("#MemberEditBackground").show();

    /*令彈窗回捲到頂端*/
    $("#MemberEditContent").scrollTop(0);                                       //切記物件若隱藏，此行無效，所以務必要放在使物件可見的代碼之後

    switch (modalstatus) {
        case '修改':
        case '新增':
            $("#MemberEditContent").css("backgroundColor", "white");
            $("#MemberModalForm input[type!='submit'][type!='button']:not(.MemberDataID)").css("backgroundColor", "#ffdf");
            $("#MemberModalForm select").css("backgroundColor", "#ffdf");
            $("#MemberModalForm textarea").css("backgroundColor", "#ffdf");
            $("#MemberModalForm .mustfill").css("backgroundColor", "papayawhip");
            $("#DeleteMessage").hide();
            if (modalstatus == '修改') {
                $("#MemberEditTitle").text("修改會員資料");
                $("#mBirthday").prop({"required": false});
                $("#mJoinday").val(mjoinday);
                $("#mJoinday").prop({"required": false});
                $("#mExpirationDate").val(mExpirationDate);
            } else if (modalstatus == '新增') {
                $("#MemberEditTitle").text("新增會員資料");
                $("#mBirthday").prop({"required": true});
                $("#mJoinday").val(year(today) + "-" + month(today) + "-" + day(today));
                $("#mJoinday").prop({"required": true});
                $("#mExpirationDate").val(year(today) + "-12-31");
            }
            /*所有物件設為可編輯*/
            $("#MemberModalForm input[type!='button'][type!='submit']:not(.MemberDataID)").attr("readonly", false);
            $("#MemberModalForm input[type='checkbox'], #MemberModalForm input[type='radio'], #MemberModalForm select, #MemberModalForm textarea").attr("disabled", false);
            break;

        case '刪除':
            $("#MemberEditContent").css("backgroundColor", "#feef");
            $("#MemberModalForm input[type!='submit'][type!='button']:not(.MemberDataID)").css("backgroundColor", "#fff8f8ff");
            $("#MemberModalForm select").css("backgroundColor", "#fff8f8ff");
            $("#MemberModalForm textarea").css("backgroundColor", "#fff8f8ff");
            $("#MemberEditTitle").text("刪除會員資料");
            $("#DeleteMessage").show();
            $("#mBirthday").prop({"required": false});
            $("#mJoinday").val(mjoinday);
            $("#mJoinday").prop({"required": false});
            $("#mExpirationDate").val(mExpirationDate);
            /*所有物件設為不可編輯*/
            $("#MemberModalForm input[type!='button'][type!='submit']:not(.MemberDataID)").attr("readonly", true);
            $("#MemberModalForm input[type='checkbox'], #MemberModalForm input[type='radio'], #MemberModalForm select, #MemberModalForm textarea").attr("disabled", true);
            break;
    }

    $("#mId").val(memberid);
    //$("#ScrollView").val("member" + memberid);                            //若使用元素 id 定位畫面捲動至的位置，需要此行
    $("#mNumber").val(membernumber);
    $("#mNumberNote").val(membernumbernote);
    $("#mName").val(membername);
    $("#mNameNote").val(membernamenote);
    $("#mAlias").val(malias);
    $("#mPseudonym").val(mpseudonym);
    if (mgender == '男') {
        $("#mGenderM").prop({"checked": true});
    } else if (mgender == '女') {
        $("#mGenderF").prop({"checked": true});
    } else {
        $("#mGenderM").prop({"checked": false});
        $("#mGenderF").prop({"checked": false});
    }
    $("#mBirthday").val(mbirthday);
    switch (mbirthdayflag) {
        case 'noYear':
            $("#mBirthday").attr("title", "原始資料的生日沒有年份，所以這裡的年份是不正確的");
            break;
        case 'onlyYear':
            $("#mBirthday").attr("title", "原始資料的生日沒有「月」和「日」，所以這裡的月日是不正確的");
            break;
        case 'noDay':
            $("#mBirthday").attr("title", "原始資料的生日沒有「日」，所以這裡的日期部分是不正確的");
            break;
        case 'Feb30':
            $("#mBirthday").attr("title", "原始資料的生日打成 2 月 30 日，為能正確輸入資料庫已改動過，所以這裡的日期是不正確的");
            break;
        default:
            $("#mBirthday").attr("title", "");
            break;
    }
    $("#mBirthdayFlag").val(mbirthdayflag);
    $("#mAncestralHome1").val(mancestralhome1);
    $("#mAncestralHomeNote1").val(mancestralhome1note);
    $("#mAncestralHomeFlag1").val(mancestralhome1flag);
    $("#mAncestralHome2").val(mancestralhome2);
    $("#mAncestralHomeNote2").val(mancestralhome2note);
    $("#mAncestralHomeFlag2").val(mancestralhome2flag);
    $("#mZipCode1").val(mzipcode1);
    $("#mAddress1").val(maddress1);
    $("#mAddressNote1").val(maddress1note);
    if ((maddress1note != "") && (maddress1note != "聯絡地址") && (maddress1note != "戶籍地址") && (maddress1note != "通訊地址")) {
        $("#mAddressNote1select").val("其他");
        $("#mAddressNote1select").css("width", "30%");
        $("#mAddressNote1").show();
        $("#mAddressNote1").css("width", "70%");
    } else {
        $("#mAddressNote1select").val(maddress1note);
        $("#mAddressNote1").hide();
        $("#mAddressNote1select").css("width", "100%");
    }
    $("#mAddressFlag1").val(maddress1flag);
    $("#mZipCode2").val(mzipcode2);
    $("#mAddress2").val(maddress2);
    $("#mAddressNote2").val(maddress2note);
    if ((maddress2note != "") && (maddress2note != "聯絡地址") && (maddress2note != "戶籍地址") && (maddress2note != "通訊地址")) {
        $("#mAddressNote2select").val("其他");
        $("#mAddressNote2select").css("width", "30%");
        $("#mAddressNote2").show();
        $("#mAddressNote2").css("width", "70%");
    } else {
        $("#mAddressNote2select").val(maddress2note);
        $("#mAddressNote2").hide();
        $("#mAddressNote2select").css("width", "100%");
    }
    $("#mAddressFlag2").val(maddress2flag);
    $("#mHomeNumber1").val(mhomenumber1);
    $("#mHomeNumberFlag1").val(mhomenumber1flag);
    $("#mHomeNumber2").val(mhomenumber2);
    $("#mHomeNumberFlag2").val(mhomenumber2flag);
    $("#mOfficeNumber1").val(mofficenumber1);
    $("#mOfficeNumberNote1").val(mofficenumber1note);
    $("#mOfficeNumberFlag1").val(mofficenumber1flag);
    $("#mOfficeNumber2").val(mofficenumber2);
    $("#mOfficeNumberNote2").val(mofficenumber2note);
    $("#mOfficeNumberFlag2").val(mofficenumber2flag);
    $("#mFaxNumber").val(mfaxnumber);
    $("#mPhoneNumber1").val(mphonenumber1);
    $("#mPhoneNumber2").val(mphonenumber2);
    $("#mEmailAddress1").val(memail1);
    $("#mEmailAddressNote1").val(memail1note);
    $("#mEmailAddress2").val(memail2);
    $("#mEmailAddressNote2").val(memail2note);
    $("#mEmailAddress3").val(memail3);
    $("#mEmailAddressNote3").val(memail3note);
    $("#mNote").val(mnote.replace(/<br>/gi, "\r\n"));                           //取代 mnote（備註）字串中的 <br> 標籤為換行字元，必須使用 g、i 等正規表示法，否則只會取代第一次出現的 <br>
    switch (mjoindayflag) {
        case 'noYear':
            $("#mJoinday").attr("title", "原始資料的入會日期沒有年份，所以這裡的年份是不正確的");
            break;
        case 'onlyYear':
            $("#mJoinday").attr("title", "原始資料的入會日期沒有「月」和「日」，所以這裡的月日是不正確的");
            break;
        case 'noDay':
            $("#mJoinday").attr("title", "原始資料的入會日期沒有「日」，所以這裡的日期部分是不正確的");
            break;
        case 'Feb30':
            $("#mJoinday").attr("title", "原始資料的入會日期打成 2 月 30 日，為能正確輸入資料庫已改動過，所以這裡的日期是不正確的");
            break;
        default:
            $("#mJoinday").attr("title", "");
            break;
    }
    $("#mJoindayFlag").val(mjoindayflag);
    if (moutflag == 1) {
        $("#mOutFlag").prop({"checked": true});
    } else {
        $("#mOutFlag").prop({"checked": false});
    }
    if (mhonorflag == 1) {
        $("#mHonorMemberFlag").prop({"checked": true});
    } else {
        $("#mHonorMemberFlag").prop({"checked": false});
    }
    if (mdeadflag == 1) {
        $("#mDeadMemberFlag").prop({"checked": true});
    } else {
        $("#mDeadMemberFlag").prop({"checked": false});
    }
    if (mspecflag == 1) {
        $("#mSpecFlag").prop({"checked": true});
    } else {
        $("#mSpecFlag").prop({"checked": false});
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
    $("#MemberModalForm input[type!='button'][type!='submit']:not(.MemberDataID)").attr("readonly", false);
    $("#MemberModalForm input[type='checkbox'], #MemberModalForm input[type='radio'], #MemberModalForm select, #MemberModalForm textarea").attr("disabled", false);
}

/*關閉彈窗*/
function modal_close() {
    if (!$("#MemberEditBackground").css("display", "none")) {
        $("#MemberEditBackground").css("display", "none");
    }
    if (!$("#MemberErrorBackground").css("display", "none")) {
        $("#MemberErrorBackground").css("display", "none");
    }
    $("#MemberEditContent").css({"left": "0", "top": '50vh'});
}

/*ESC 鍵可關閉彈窗*/
function modal_esc(e) {
    key = (window.event) ? e.keyCode : e.which;                                 //相容 Firefox
    if (($('#MemberEditBackground').css('display') != "none") && (key == 27)) {
        $("#MemberEditBackground").css("display", "none");
    }
    if (($('#MemberErrorBackground').css('display') != "none") && (key == 27)) {
        $("#MemberErrorBackground").css("display", "none");
    }
    $("#MemberEditContent").css({"left": "0", "top": '50vh'});
}

/*搜尋選單選中「榮譽會員」、「非榮譽會員」或「已故會員」時變更文字框與選單框的寬度*/
function hide_text() {
    if (($("#mOption").val() == "hnm") || ($("#mOption").val() == "nhm") || ($("#mOption").val() == "ddm")) {
        $("#mOption").css("width", "100%");
        $("#mSearch").hide();
    } else {
        $("#mOption").css("width", "33%");
        $("#mSearch").show();
    }
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

/*引入底部版權版本資訊檔案*/
$("#includePage").load("footer.html");