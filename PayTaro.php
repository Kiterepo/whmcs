<?php

function payTaro_config() {
    $configarray = [
        "FriendlyName" 		=> ["Type" => "System", "Value"=>"聚合支付（PayTaro）"],
        "appId" 			=> ["FriendlyName" => "AppID", "Type" => "text", "Size" => "32", ],
        "appSecret" 		=> ["FriendlyName" => "AppSecret", "Type" => "text", "Size" => "32", ]
    ];
    return $configarray;
}

function payTaro_link($params) {

    $systemurl = $params['systemurl'];
    if (!stristr($_SERVER['PHP_SELF'], 'viewinvoice')) {
        return;
    }
    if(!class_exists('PayTaro')) {
        include("PayTaro/class.php");
    }
    $payTaro = new PayTaro($params['appId'], $params['appSecret']);
    $payData = [
        'app_id' => $params['appId'],
        'out_trade_no' => $params['invoiceid'],
        'total_amount' => $params['amount'] * 100,
        'notify_url' => $systemurl."/modules/gateways/PayTaro/notify.php",
        'return_url' => $systemurl."/viewinvoice.php?id=".$params['invoiceid'],
    ];
    $payData['sign'] = $payTaro->sign($payTaro->prepareSign($payData));

    $response = $payTaro->post($payData);
    logModuleCall('payTaro', 'pay', '', json_encode($response));
    $webpaylink = $response['data']['pay_url'];

    $code = '<a href="'.$webpaylink.'" target="_blank" id="alipayDiv" class="btn btn-info btn-block">前往收银台</a>';
    return $code.'<script>
		//设置每隔 5000 毫秒执行一次 load() 方法
		setInterval(function(){load()}, 5000);
		function load(){
			var xmlhttp;
			if (window.XMLHttpRequest){
				// code for IE7+, Firefox, Chrome, Opera, Safari
				xmlhttp=new XMLHttpRequest();
			}else{
				// code for IE6, IE5
				xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlhttp.onreadystatechange=function(){
				if (xmlhttp.readyState==4 && xmlhttp.status==200){
					trade_state=xmlhttp.responseText;
					if(trade_state=="SUCCESS"){
						document.getElementById("alipayimg").style.display="none";
						document.getElementById("alipayDiv").innerHTML="支付成功";
						window.location.reload()
					}
				}
			}
			//invoice_status.php 文件返回订单状态，通过订单状态确定支付状态
			xmlhttp.open("get","'.$systemurl.'/modules/gateways/PayTaro/query.php?invoiceid='.$params['invoiceid'].'",true);
			//下面这句话必须有
			//把标签/值对添加到要发送的头文件。
			//xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			//xmlhttp.send("out_trade_no=002111");
			xmlhttp.send();
		}
	</script>';
}
?>
