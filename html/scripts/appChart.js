var userChart = (function() {
	return {
		init: function() {
			var self = this;
			self.huan(huan[0],'users');
            self.huan(huan[1],'order');
			self.recentGet( window.line['income-day']);
            self.people(window.line['increase']);
			$('.js-income').on('click',function(){
                var e=$(this);
                var data=e.attr('data-data');
                self.recentGet( window.line[data]);
                e.addClass('active').siblings().removeClass('active');
			});
			$('.js-people').on('click',function(){
                var e=$(this);
                $('#people-h').html($(this).html());
                var data=e.attr('data-data');
                self.people( window.line[data]);
                e.addClass('active').siblings().removeClass('active');
			});
		},
		huan: function(data,id) {
			var user = echarts.init(document.getElementById(id));
            var values=[];
            for(x in data.head){
                values.push({value:data.data[x],name:data.head[x]});
            }
			var option = {
				color:['#89c7f1','#1abc9c','#f29489','#d48265','#91c7ae','#749f83'],
				tooltip: {
					formatter: "{a} <br/>{b} : {c} ({d}%)"
				},
				legend: {
					orient: 'vertical',
					x: 'right',
					data:data.head
				},
				series: [{
					name: '用户分布',
					type: 'pie',
					data:values,
					radius: ['50%', '70%'],
					avoidLabelOverlap: false,
					label: {
						normal: {
							show: true,
							formatter:"{d}%",
							textStyle: {
								fontSize: '15',
								fontWeight: 'bold'
							}
						}
					}
				}]
			};
			user.setOption(option);
		},
		recentGet: function( data) {
			var sale = echarts.init(document.getElementById('income'));
			var option = {
				title:{
					textStyle:{
						color:'#000'
					}
				},
				tooltip : {
					trigger: 'axis',
					formatter: "{b}<br/>{a}: {c} 元"
				},
				xAxis : [
					{
						type : 'category',
						boundaryGap : false,
						data : data.head,
						splitLine:{
			                lineStyle:{color:'rgba(128,128,128,0.1)'}
			            }
					}
				],
				yAxis : [
					{
						type : 'value',
						name:'单位/元',
						splitLine:{
			                lineStyle:{color:'rgba(128,128,128,0.1)'}
			            }
					}
				],
				series : [
					{
                        name: '收入',
						type:'line',
						itemStyle: {
							normal: {
								color: '#89c7f1'
							}
						},
						lineStyle:{normal:{color:'#89c7f1'}},
			            markPoint: {
			                data: [
			                    {type: 'max', name: '最大值'},
			                ],
			            },
						symbolSize:7,
						data:data.data
					}
				]
			};

			sale.setOption(option);
		},
		people: function( data) {
			var sale = echarts.init(document.getElementById('people'));
			var option = {
				tooltip : {
					trigger: 'axis'
				},
				xAxis : [
					{
						type : 'category',
						boundaryGap : false,
						data : line.date,
						splitLine:{
			                lineStyle:{color:'rgba(128,128,128,0.1)'}
			            }
					}
				],
				yAxis : [
					{
						type : 'value',
						name:'单位/人',
						splitLine:{
			                lineStyle:{color:'rgba(128,128,128,0.1)'}
			            }
					}
				],
				series : [
					{
                        name: '人数',
						type:'line',
						lineStyle:{normal:{color:'#1abc9c'}},
						itemStyle: {
							normal: {
								color: '#1abc9c'
							}
						},
			            markPoint: {
			                data: [
			                    {type: 'max', name: '最大值'},
			                ]
			            },
						symbolSize:7,
						data:data
					}
				]
			};

			sale.setOption(option);
		}
	}
}());

$(document).ready(function() {
	userChart.init();
});
