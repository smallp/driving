$(document).ready(function(){
	param={
		T:$('#template').html(),
		target:'data',
    	dealData:function(data){
    		var status={'3':'待处理','4':'已处理'};
//    		var kind={'1':'科目二','2':'科目三','4':'陪练陪驾'};
    		for(x in data){
    			data[x].partner=data[x].partner?data[x].partner:'';
    			data[x].place=data[x].place?data[x].place:'';
    			data[x].oprator=data[x].oprator?data[x].oprator:'';
        		if (data[x].status!=4)
        			data[x].option='<button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#delete" data-id="'+data[x].logId+'"><i class="cacel-btn"></i>处理</button>';
        		else data[x].option='';
        		data[x].status=status[data[x].status];
        		data[x].orderTime=window.data.orderTime(data[x].orderTime);
        		data[x].dealTime=(data[x].dealTime==0)?'':window.data.getTime(data[x].dealTime);
    		}
    		return data;
    	}
	}
	PAGER.init(param);
	
});