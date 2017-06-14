$.extend({web:function(url,data,success,method){
	url=url==''?location.href:url;
	method=typeof method=='undefined'?'post':method;
	$.ajax({
        url: url,
        method: method,
        data: data,
        dataType: 'json',
        success:success
    });
}});
$( document ).ajaxError(function(event, res) {
	try{
		res=JSON.parse(res.responseText);
		alert(res.info);
	}catch(e){
		alert('服务器错误！');
	}
});
var data = (function(){
    return {
        getTime:function( time ){
        	var str=new Date(time * 1000).toLocaleString();
    		return str;
        },
        getPic:function( pics ){
            var str = "";
            for( var i = 0,len = pics.length;i<len;i++ ){
                str += "<li><img src=\""+pics[i]+"\" alt=\"图片\"></li>";
            }
            return str;
        },
        fillForm:function( obj,data ){
            for (x in data){
            	if (x in obj)
            		obj[x].value=data[x];
            }
        },
        orderTime:function( time ){
            endTime = time+40;
            return this._orderTime(time) + '-' + this._orderTime(endTime);
        },
        _orderTime:function(time){
            var h = Math.floor(time / 60);
            var m = time % 60;
            if (m==0) m='00';
            return `${h}:${m}`;
        }
    }
})();