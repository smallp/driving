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
			});
			$('.js-people').on('click',function(){
                var e=$(this);
                $('#people-h').html($(this).html());
                var data=e.attr('data-data');
                self.people( window.line[data]);
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
					trigger: 'item',
					formatter: "{a} <br/>{b} : {c} ({d}%)"
				},
				legend: {
					orient: 'vertical',
					x: 'right',
					data:data.head
				},
				title: {
					text: '用户分布',
					textStyle: {
						color: '#235894'
					}
				},
				series: [{
					name: '用户分布',
					type: 'pie',
					data:values,
					radius: ['50%', '70%'],
					avoidLabelOverlap: false,
					label: {
						normal: {
							show: false,
							position: 'center'
						},
						emphasis: {
							show: true,
							textStyle: {
								fontSize: '30',
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
				tooltip : {
					trigger: 'axis'
				},
				xAxis : [
					{
						type : 'category',
						boundaryGap : false,
						data : data.head
					}
				],
				yAxis : [
					{
						type : 'value'
					}
				],
				series : [
					{
                        name: '收入',
						type:'line',
//						label: {
//							normal: {
//								show: true,
//								position: 'top'
//							}
//						},
						lineStyle:{normal:{color:'#89c7f1'}},
			            markPoint: {
			                data: [
			                    {type: 'max', name: '最大值'},
			                ],
							itemStyle: {
								normal: {
									color: '#89c7f1'
								}
							},
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
						data : line.date
					}
				],
				yAxis : [
					{
						type : 'value'
					}
				],
				series : [
					{
                        name: '人数',
						type:'line',
						lineStyle:{normal:{color:'#1abc9c'}},
			            markPoint: {
			                data: [
			                    {type: 'max', name: '最大值'},
			                ],
							itemStyle: {
								normal: {
									color: '#1abc9c'
								}
							},
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
