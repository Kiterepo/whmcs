<?php
# 异步返回页面
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
use \Illuminate\Database\Capsule\Manager as Capsule;

if(!class_exists('PayTaro')) {
    include("./class.php");
}

logModuleCall('payTaro', 'notify', '', http_build_query($_POST));
// log_result(http_build_query($_REQUEST));
$GATEWAY 					= getGatewayVariables('PayTaro');
$url						= $GATEWAY['systemurl'];
$companyname 				= $GATEWAY['companyname'];
$currency					= $GATEWAY['currency'];
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
$appId						= $GATEWAY['appId'];
$appSecret					= $GATEWAY['appSecret'];
$payTaro                    = new PayTaro($appId, $appSecret);
$strToSign = $payTaro->prepareSign($_POST);
$verify_result = $payTaro->verify($strToSign, $_POST['sign']);
if(!$verify_result) { 
	logTransaction($GATEWAY["name"], $_POST, "Unsuccessful");
	die('FAIL');
} else {
    $invoiceId = $_POST['out_trade_no'];
    $transid = $_POST['trade_no'];
    $paymentAmount = $_POST['total_amount'] / 100;
    $feeAmount = 0;

    //货币转换开始
    //获取支付货币种类
    $currencytype 	= \Illuminate\Database\Capsule\Manager::table('tblcurrencies')->where('id', $gatewayParams['convertto'])->first();

    //获取账单 用户ID
    $userinfo 	= \Illuminate\Database\Capsule\Manager::table('tblinvoices')->where('id', $invoiceId)->first();
    if ($userinfo->status === 'Paid') {
    	die('SUCCESS');
    }

    //得到用户 货币种类
    $currency = getCurrency($userinfo->userid);

    // 转换货币
    $paymentAmount = convertCurrency($paymentAmount, $currencytype->id, $currency['id']);
    // 货币转换结束
    checkCbTransID($transid);
    addInvoicePayment($invoiceId, $transid, $paymentAmount, $feeAmount, 'PayTaro');
    logTransaction($GATEWAY["name"], $_POST, "Successful-A");
    die('SUCCESS');
}
die('FAIL');
?>