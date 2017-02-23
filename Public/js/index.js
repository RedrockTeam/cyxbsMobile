//放大镜
var $Screen = $(".screen");
var more = $(".more1");
var In = $(".more1 .in");

$Screen.mousemove(function (e) {
	var x = e.pageX - $Screen.offset().left;
	var y = e.pageY - $Screen.offset().top;
	y > 467.5 ? y = 467.5 : y = y ;
	y < 132.5 ? y = 132.5 : y = y ;
	x > 367.5 ? x = 367.5 : x = x ;
	x < 132.5 ? x = 132.5 : x = x ;
	more.css("left",x+425.5);
	more.css("top",y-132.5);
	In.css("left",165.5-x);
	In.css("top",-96.5-y);
});

//懒加载
var $window = $(window);
var $find = $('#find');
var topFind = $find.offset().top;
var $info = $('#info');
var topInfo = $info.offset().top;
var $moreFunc = $('#more-func');
var topMore = $moreFunc.offset().top;
var $more1 = $(".more1");
var $more2 = $(".more2");
var $Screen = $(".screen");
// var $scope = $(".scope");

$(document).on('scroll', function(e){
	var _top = $window.scrollTop() + 0.5 * $window.height();

	if(_top >= topFind + 0.1 * $find.height()){
		Find();
	}

	if(_top >= topInfo + 0.1 * $info.height()){
		info();
	}

	if(_top >= topInfo + 0.2 * $info.height()){
		moreFunc();
	}
});

function info (){
	$("#info .out").addClass("animation");
}

function Find (){
	$("#find .out").addClass("animation");
	setTimeout(function(){
		$more2.css("display","block");
		$more1.css("display","block");
		$Screen.css("display","block");
	},3000);
}

function moreFunc () {
	$("#more-func .out").addClass("animation");
}

//返回顶部
$("#up").on('click', function(e){
	e.stopPropagation();
	e.preventDefault();
	$('html, body').animate({scrollTop:0}, 'slow');
});
$window.mousemove(function (e) {
	var y = e.pageY;
	if(y>1000){
		$("#up").css("display","block");
		// $up.animate({opacity:1},500);
	}else{
		// $up.animate({opacity:0},500);
		// setTimeout(function(){
		// 	$up.css("display","none");
		// },500);
		$("#up").css("display","none");
	}
});