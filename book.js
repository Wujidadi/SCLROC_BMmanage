/*將資料庫「ID」及各書區書號最大值（已在 HTML 中取出）宣告為變數*/
var idmax = $("#IDMax")[0].innerHTML;                                   //jQuery 沒有 innerHTML 屬性，要使用它必須以 [0] 的方式來獲取元素
var amax = $("#aMax")[0].innerHTML;
var bmax = $("#bMax")[0].innerHTML;
var cmax = $("#cMax")[0].innerHTML;

/*彈窗可移動*/
$(document).ready(function() {
    $("#BookEditContent").draggable({cancel: "td, h2, span, p, input"});
});

/*「修改」彈窗*/
function edit_display(bookid, bookarea, booknumber, bookname, author, illustrator, translator, publisher, container, note) {
    $("#BookEditBackground").css("display", "block");
    $("#BookEditContent").css({"backgroundColor": "white", "transform": "translateY(-75%)"});
    $("#BookModalForm input[type!='submit'][type!='button']:not(.BookDataID)").css("backgroundColor", "#ffdf");
    $("#BookEditTitle").text("修改書籍資料");
    $("#DeleteMessage").css({"display": "none", "color": "inherit"});
    $("#bId").val(bookid);
    //$("#ScrollView").val("book" + bookid);                            //若使用元素 id 定位畫面捲動至的位置，需要此行
    $("#bNumber").val(bookarea + booknumber).attr("readonly", false);
    $("#bName").val(bookname).attr("readonly", false);
    $("#bAuthor").val(author).attr("readonly", false);
    $("#bIllustrator").val(illustrator).attr("readonly", false);
    $("#bTranslator").val(translator).attr("readonly", false);
    $("#bPublisher").val(publisher).attr("readonly", false);
    $("#bContainerNumber").val(container).attr("readonly", false);
    $("#bNote").val(note).attr("readonly", false);
}

/*「插入」彈窗*/
function insert_display(bookid, bookarea, booknumber) {
    $("#BookEditBackground").css("display", "block");
    $("#BookEditContent").css({"backgroundColor": "white", "transform": "translateY(-75%)"});
    $("#BookModalForm input[type!='submit'][type!='button']:not(.BookDataID)").css("backgroundColor", "#ffdf");
    $("#BookEditTitle").text("插入書籍資料");
    $("#DeleteMessage").css({"display": "none", "color": "inherit"});
    $("#bId").val(Number(idmax) + 1);
    //$("#ScrollView").val("book" + bookid);                            //若使用元素 id 定位畫面捲動至的位置，需要此行
    /*書號的純數字部分不足 4 位時補零*/
    booknumber = Number(booknumber) + 1;
    if (booknumber >= 1000) {
        booknumber = booknumber;
    } else if (booknumber >= 100) {
        booknumber = '0' + booknumber;
    } else if (booknumber >= 10) {
        booknumber = '00' + booknumber;
    } else {
        booknumber = '000' + booknumber;
    }
    $("#bNumber").val(bookarea + booknumber).attr("readonly", false);
    $("#bName").val("").attr("readonly", false);
    $("#bAuthor").val("").attr("readonly", false);
    $("#bIllustrator").val("").attr("readonly", false);
    $("#bTranslator").val("").attr("readonly", false);
    $("#bPublisher").val("").attr("readonly", false);
    $("#bContainerNumber").val("").attr("readonly", false);
    $("#bNote").val("").attr("readonly", false);
}

/*「新增」彈窗*/
function add_display() {
    $("#BookEditBackground").css("display", "block");
    $("#BookEditContent").css({"backgroundColor": "white", "transform": "translateY(-75%)"});
    $("#BookModalForm input[type!='submit'][type!='button']:not(.BookDataID)").css("backgroundColor", "#ffdf");
    $("#BookEditTitle").text("新增書籍資料");
    $("#DeleteMessage").css({"display": "none", "color": "inherit"});
    $("#bId").val(Number(idmax) + 1);
    //$("#ScrollView").val("book" + bookid);                            //若使用元素 id 定位畫面捲動至的位置，需要此行
    $("#bNumber").val("").attr({"readonly": false, "placeholder": "建議書號：" + amax + ", " + bmax + ", " + cmax});
    $("#bName").val("").attr("readonly", false);
    $("#bAuthor").val("").attr("readonly", false);
    $("#bIllustrator").val("").attr("readonly", false);
    $("#bTranslator").val("").attr("readonly", false);
    $("#bPublisher").val("").attr("readonly", false);
    $("#bContainerNumber").val("").attr("readonly", false);
    $("#bNote").val("").attr("readonly", false);
}

/*「刪除」彈窗*/
function delete_display(bookid, bookarea, booknumber, bookname, author, illustrator, translator, publisher, container, note) {
    $("#BookEditBackground").css("display", "block");
    $("#BookEditContent").css({"backgroundColor": "#feef", "transform": "translateY(-65%)"});
    $("#BookModalForm input[type!='submit'][type!='button']:not(.BookDataID)").css("backgroundColor", "#fff8f8ff");
    $("#BookEditTitle").text("書籍除帳");
    $("#DeleteMessage").css({"display": "block", "color": "red"});
    $("#bId").val(bookid);
    $("#bNumber").val(bookarea + booknumber).attr("readonly", true);
    $("#bName").val(bookname).attr("readonly", true);
    $("#bAuthor").val(author).attr("readonly", true);
    $("#bIllustrator").val(illustrator).attr("readonly", true);
    $("#bTranslator").val(translator).attr("readonly", true);
    $("#bPublisher").val(publisher).attr("readonly", true);
    $("#bContainerNumber").val(container).attr("readonly", true);
    $("#bNote").val(note).attr("readonly", true);
}

/*關閉彈窗*/
function modal_close() {
    if (!$("#BookEditBackground").css("display", "none")) {
        $("#BookEditBackground").css("display", "none");
    }
    if (!$("#BookErrorBackground").css("display", "none")) {
        $("#BookErrorBackground").css("display", "none");
    }
    $("#BookEditContent, #BookErrorBox").css({"left": "0", "top": '50vh'});
}

/*ESC 鍵可關閉彈窗*/
function modal_esc(e) {
    key = (window.event) ? e.keyCode : e.which;                         //相容 Firefox
    if (($('#BookEditBackground').css('display') != "none") && (key == 27)) {
        $("#BookEditBackground").css("display", "none");
    }
    if (($('#BookErrorBackground').css('display') != "none") && (key == 27)) {
        $("#BookErrorBackground").css("display", "none");
    }
    $("#BookEditContent, #BookErrorBox").css({"left": "0", "top": '50vh'});
}

/*畫面捲動至已賦予 id 的元素處*/
function scroll_view(coords) {
    var coord = "#" + coords;
    var top = $(coord).offset().top;                                    //取得元素頂部的 y 座標
    window.scrollTo(0, top - 75);                                       //令畫面捲動至比元素高 75px 處
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
    var errinfo = $("#ErrorInfo")[0].innerHTML;                         //jQuery 沒有 innerHTML 屬性，要使用它必須以 [0] 的方式來獲取元素
    if (errinfo == 1062) {
        $("#BookErrorBackground").css("display", "block");
    }
}

/*引入版權版本資訊檔案*/
$("#includePage").load("footer.html");