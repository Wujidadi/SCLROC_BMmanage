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

function year(t) {
    var y = t.getFullYear();
    var my = y - 1911;
    //return [y, my];
    return {"y": y, "mg": my};
}

function month(t) {
    var m = t.getMonth() + 1;
    return m;
}

function day(t) {
    var d = t.getDate();
    return d;
}

function week(t) {
    var w = t.getDay();
    var weeklist = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"]
    return weeklist[w];
}

function hour(t) {
    var h = t.getHours();
    if (h > 12) h -= 12;
    else if (h == 0) h = 12;
    return h;
}

function min(t) {
    var i = t.getMinutes();
    if (i < 10) i = "0" + i;
    return i;
}

function sec(t) {
    var s = t.getSeconds();
    if (s < 10) s = "0" + s;
    return s;
}

function greeting(t) {
    var h = t.getHours();
    if (h <= 4) {
        var welcome = "晚安！";
        if (h < 1) {
            var times = "半夜";
        } else {
            var times = "凌晨";
        }
    } else if (h < 11) {
        var welcome = "早安！";
        if (h < 6) {
            var times = "凌晨";
        } else {
            var times = "早上";
        }
    } else if (h < 13) {
        var welcome = "午安！";
        var times = "中午";
    } else if (h < 18) {
        var welcome = "下午好！";
        if (h < 17) {
            var times = "下午";
        } else {
            var times = "傍晚";
        }
    } else {
        var welcome = "晚安！";
        if (h < 19) {
            var times = "傍晚";
        } else {
            var times = "晚上";
        }
    }
    //return [welcome, times];
    return {"welcome": welcome, "times": times};
}

$(document).ready(setInterval(function() {
    var t = new Date();
    //t.setDate(t.getDate());
    //t.setHours(t.getHours() + 14);
    //t.setMinutes(t.getMinutes() - 23);
    $("#greeting").text(greeting(t)["welcome"]);
    $("#today").text(year(t)["y"] + " 年（民國 " + year(t)["mg"] + " 年）" + month(t) + " 月 " + day(t) + " 日" + week(t));
    $("#timePeriod").text(greeting(t)["times"]);
    $("#presentTime").text(hour(t) + " 點 " + min(t) + " 分 " + sec(t) + " 秒");
}), 1000);

var n = 3;

function countdown_relogin() {
    n--;
    $("#loginFailText").text("登入失敗！" + n + " 秒後重新登入");
}

$(document).ready(function() {
    if (n > 0) {
        setInterval("countdown_relogin()", 1000);
    }
});

function show_loginMenu() {
    $("#loginForm").toggle();
}

$(document).click(function(event) {
    if (!$("#loginText").is(event.target) && $("#loginText").has(event.target).length === 0) {
        $("#loginForm").hide();
    }
});

function inform_switch(s) {
    switch (s) {
        case 'c':
            $('#circulationInformation').css("background", "linear-gradient(hotpink, pink)");
            $('#memberInformation').css("background", "linear-gradient(palegreen, white)");
            $('#circulationSituation').show();
            $('#memberSituation').hide();
            break;
        case 'm':
            $('#circulationInformation').css("background", "linear-gradient(palegreen, white)");
            $('#memberInformation').css("background", "linear-gradient(hotpink, pink)");
            $('#circulationSituation').hide();
            $('#memberSituation').show();
            break;
    }
}

/*引入底部版權版本資訊檔案*/
$("#includePage").load("footer.html");