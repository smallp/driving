var isShow=true;
$('.sidebar .toggle-bar a').click(function(e){
	e.preventDefault();
	if(isShow){
		$('.sidebar').css('width','86px');
		$('.sidebar .nano .brand a').html('<img src="/images/logo1.png"/>');
		$('.main-content-wrapper').css('margin-left','86px');
		$('.nano-content .toggle-bar a img').attr('src','/images/hide-bar.png');
		$('.nano-content>li:not(first-child)>a>span').addClass('hide');
		$('.nano-content .sub-menu>a i.fa-angle-right').addClass('hide');
		$('.nano-content .sub-menu>a i.fa').css('padding-left','2px');
		$('.nano-content li:nth-child(2)>a i.fa').css('padding-left','2px');
		$('.sub-menu>ul').addClass('float_nav');
		$('.nano-content li:nth-child(2)>a>i.fa').css('font-size','30px');
		$('.nano-content .sub-menu>a i.fa').css('font-size','30px');
		isShow=false;
		window.sessionStorage.setItem('value',isShow);
		
	}else{
		$('.sidebar').css('width','240px');
		$('.sidebar .nano .brand a').html('<img src="/images/logo-txt.png"/>');
		$('.main-content-wrapper').css('margin-left','240px');
		$('.nano-content .toggle-bar a img').attr('src','/images/show-bar.png');
		$('.nano-content li:not(first-child) a span').removeClass('hide');
		$('.nano-content .sub-menu>a i.fa-angle-right').removeClass('hide');
		$('.nano-content .sub-menu>a i.fa').css('padding-left','0px');
		$('.nano-content li:nth-child(2) a i.fa').css('padding-left','0px');
		$('.sub-menu>ul').removeClass('float_nav');
		$('.nano-content li:nth-child(2)>a>i.fa').css('font-size','12px');
		$('.nano-content .sub-menu>a i.fa').css('font-size','12px');
		isShow=true;
		window.sessionStorage.setItem('value',isShow);
	}
	
	console.log(window.sessionStorage.getItem('value'));
});
$('#naver').delegate('.sub-menu','mouseover',function(){
	if(!isShow){
		$(this).find('ul').slideDown();
		$(this).siblings('.sub-menu').find('ul').css('display','none');
	}
});
$(function(){
	var value=window.sessionStorage.getItem('value');
	if(value=="true"||value==null){
		console.log(value);
		$('.sidebar').css('width','240px');
		$('.sidebar .nano .brand a').html('<img src="/images/logo-txt.png"/>');
		$('.main-content-wrapper').css('margin-left','240px');
		$('.nano-content .toggle-bar a img').attr('src','/images/show-bar.png');
		$('.nano-content li:not(first-child) a span').removeClass('hide');
		$('.nano-content .sub-menu>a i.fa-angle-right').removeClass('hide');
		$('.nano-content .sub-menu>a i.fa').css('padding-left','0px');
		$('.nano-content li:nth-child(2) a i.fa').css('padding-left','0px');
		$('.sub-menu>ul').removeClass('float_nav');
		$('.nano-content li:nth-child(2)>a>i.fa').css('font-size','12px');
		$('.nano-content .sub-menu>a i.fa').css('font-size','12px');
	}else{
		console.log(value);
		$('.sidebar').css('width','86px');
		$('.sidebar .nano .brand a').html('<img src="/images/logo1.png"/>');
		$('.main-content-wrapper').css('margin-left','86px');
		$('.nano-content .toggle-bar a img').attr('src','/images/hide-bar.png');
		$('.nano-content>li:not(first-child)>a>span').addClass('hide');
		$('.nano-content .sub-menu>a i.fa-angle-right').addClass('hide');
		$('.nano-content .sub-menu>a i.fa').css('padding-left','2px');
		$('.nano-content li:nth-child(2)>a i.fa').css('padding-left','2px');
		$('.sub-menu>ul').addClass('float_nav');
		$('.nano-content li:nth-child(2)>a>i.fa').css('font-size','30px');
		$('.nano-content .sub-menu>a i.fa').css('font-size','30px');
	}
})
