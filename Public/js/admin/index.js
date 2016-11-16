var getUrl = function() {
        var url = window.location.href;
        var rpos = url.lastIndexOf('index.php')+'index.php'.length;
        url = url.substring(0, rpos);
        return url;
    }
$.validator.addMethod("pwdstrength",function(value, element, param){
    modes = checkStrong(value);
    return this.optional(element) || modes >= param;
}, $.validator.format("数字，小写字母，大写字母，特殊字符中至少含{0}项"));
var checkStrong = function(sValue) {
    var modes = 0;
    //正则表达式验证符合要求的
    if (sValue.length < 1) return modes;
    if (/\d/.test(sValue)) modes++; //数字
    if (/[a-z]/.test(sValue)) modes++; //小写
    if (/[A-Z]/.test(sValue)) modes++; //大写
    if (/\W/.test(sValue)) modes++; //特殊字符
     return modes;
}
jQuery.extend(jQuery.validator.messages, {
  required: "必选字段",
  remote: "请修正该字段",
  email: "请输入正确格式的电子邮件",
  url: "请输入合法的网址",
  date: "请输入合法的日期",
  dateISO: "请输入合法的日期 (ISO).",
  number: "请输入合法的数字",
  digits: "只能输入整数",
  creditcard: "请输入合法的信用卡号",
  equalTo: "请再次输入相同的值",
  accept: "请输入拥有合法后缀名的字符串",
  maxlength: jQuery.validator.format("请输入一个 长度最多是 {0} 的字符串"),
  minlength: jQuery.validator.format("请输入一个 长度最少是 {0} 的字符串"),
  rangelength: jQuery.validator.format("请输入 一个长度介于 {0} 和 {1} 之间的字符串"),
  range: jQuery.validator.format("请输入一个介于 {0} 和 {1} 之间的值"),
  max: jQuery.validator.format("请输入一个最大为{0} 的值"),
  min: jQuery.validator.format("请输入一个最小为{0} 的值")
});

var panelGroup = $('#accordion');

var load = function(id) {
    var target = $("#"+id);
    target.addClass('active in');
    target.css('display', 'block');
    var inner = target.html();
    var inner = target.html();
    if (inner.trim() ==='') {
        $.ajax({
            url : getUrl()+"/Admin/Data/index?part="+id,
            success: function (output) {
              target.html(output);
            },
            error: function(XMLHttpRequest, textStatus) {
             if(XMLHttpRequest.status === 403) {
              location.reload();
             }
            },
        });
    }
}

$("a[data-toggle='tab']", panelGroup).click(function (){

    var activeLi = $("li[class='active']", panelGroup);

    activeLi.removeClass('active');

    activeLi.children().attr('aria-expanded', "false");
    $(this).attr('aria-expanded', "true");
    $(this).parent().addClass('active');
    var actived = $(".active.in[role='tabpanel']");
    actived.removeClass('active in');
    actived.css('display', 'none');
    var id  = $(this).attr('aria-controls');
    load(id);
});

$(document).ready(function(){
    load('AdminInfo');
});

