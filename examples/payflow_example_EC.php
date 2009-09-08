<?php
/*

This Payflow Sample is based on the information found at:
http://paypaldeveloper.com/pdn/board/message?board.id=payflow&thread.id=1008.

This sample can be used for both Payflow Pro and Websites Payments Pro UK merchants.

IMPORANT NOTE:

You MUST have a Payflow Pro or Websites Payments Pro UK account to use this sample.  This sample is
NOT compatible with a Websites Payments Pro account.

1. Obtain either a Websites Payments Pro UK (Payflow) or Payflow Pro account.
2. Replace $currency with 'GBP' if using Websites Payments Pro UK.
3. Modify the login credentials to reflect your account information.
4. Set the $fraud variable if you have the Fraud Protection Services on your account.
4. Upload this file to your server/website

If you decided to make any changes, please refer to Payflow Pro/Websites Payments Pro UK Developer's Guide.

Copyrighted to Choon Yen Kong 8 March 2007 @ http://www.ecommerce-web-store.com

Modified by Todd Sieber @ PayPal 21 August 2007:

        * Added various functions to illustrate simple business logic that you might use.

Modified by ToddS @ PayPal 05 November 2007:

        * Changed code to reflect changes effective 15/10/2007 to the HTTPS Interface as per the posting at
          http://www.paypaldeveloper.com/pdn/board/message?board.id=payflow&thread.id=1615
        * Changed field "ADDRESS" to "STREET".

Modified by ToddS @ PayPal 30 November 2007:

        * Fixed line 540 (now 544), changed from ($fraud = 'YES') to ($fraud == 'YES').

Modified by ToddS @ PayPal 07 January 2008:

        * Changed CLIENTIP to CUSTIP.

Modified by Mike Challis (www.carmosaic.com) 23 February 2008

        * added features:
          Use an array to build the query for better visual representation in the php code.
          Added bracketed numbers to all query string names
          The bracketed numbers are length tags which allow you to
          use the special characters of "&" and "=" in the value sent.

Changed $paypal_query array as suggested by Michael McAndrew (michaelmcandrew@gmail.com) 26 April 2008

Modified by ToddS @ PayPal 28 October 2008:
			
				* Added 'useraction=commit' parameter to allow the display of the ORDERDESC and AMT on the 
				  PayPal Review Your Information page.
				* Added REQBILLINGADDRESS to SetEC call.  This will return the billing address of the PayPal account being used.
				  Your account may need to be modified to allow this data to be returned.  Contact support if needed.

Use this script at your own risk!  If you have an improved version, do share it on the forum!
*/


// Set these values prior to attempting to run this sample.
// Change the $currency to GBP for UK transactions.  Default is USD.
$currency = 'USD';

/*
COMMON ISSUES:

        * Result Code 1, using caused by one of the following:
            ** Invalid login information, see result code 26 below.
            ** IP Restrictions on the account. Verify there are no ip restrictions in Manager under Service Settings.
            
        * Result Code 26: Verify USER, VENDOR, PARTNER and PASSWORD. Remember, USER and VENDOR are both the merchant login ID unless
          a Payflow Pro USER was created.  All fields are case-sensitive.
          See this post for more information: http://www.paypaldeveloper.com/pdn/board/message?board.id=payflow&message.id=1388
          
        * No Response Received.  Usually caused by using the older host URLs.
        
        * Receiving Communication Exceptions or No Response.  Since this service is based on HTTPS it is possible
          that due to network issues either on PayPal's side or yours that you can not process a transaction.
          If this is the case, what is suggested is that you put some type of loop in your code to try up to X
          times before "giving up".  This example will try to get a response up to 3 times before it fails and by
          using the Request ID as described below, you can do these attempts without the chance of causing duplicate
          charges on the credit card.

*/

// Login credentials.
// These are your Payflow Pro/Websites Payments Pro UK credentials that you use to log into PayPal Manager at https://manager.paypal.com.
// These are NOT PayPal Sandbox or Websites Payments Pro credentials.  See "Result Code 26" above.
// Replace all ***** with your account information.
$user = 'CSTSINC';
$vendor = 'CSTSINC';
$partner = 'VeriSign';
$password = 'AKAVISH1';

// Are you using the Payflow Fraud Protection Service?
// Default is YES, change to NO or blank if not.
$fraud = 'NO';

// Change to Live if you want to post to the live servers.
$env = 'Test';

// Begin ...

if ($_REQUEST) {
    if (isset($_REQUEST['x'])) {
        $action = $_REQUEST['x'];
    }
    if (isset($_REQUEST['order_num'])) {
        $order_num = $_REQUEST['order_num'];
    }
    if (isset($_REQUEST['amount'])) {
        $amount = $_REQUEST['amount'];
    }
} else {
    $action = "";
}

$desc = 'Payflow HTTPS PHP Test';
$fname = "";
$lname = "";
$addr1 = "";
$addr2 = "";
$addr3 = "";
$addr4 = "";
$country = "";
$email = "";
$paypal = 'USER='.$user.'&VENDOR='.$vendor.'&PARTNER='.$partner.'&PWD='.$password;
$cust_ip = $_SERVER['REMOTE_ADDR'];
$paypal_query = "";

if($env=='Live') {
    $submiturl = 'https://payflowpro.paypal.com';
    // The 'useraction=commit' parameter is used to display the ORDERDESC and AMT on the PayPal Review Your Information page.
    $PayPalURL = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token=';
} else {
    $submiturl = 'https://pilot-payflowpro.paypal.com';
    // This URL is to be used if you have not setup your account to use the Sandbox.
    // See the posting at https://www.paypaldeveloper.com/pdn/board/message?board.id=payflow&thread.id=660.
    // $PayPalURL = 'https://test-expresscheckout.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';
    //
    // The 'useraction=commit' parameter is used to display the ORDERDESC and AMT on the PayPal Review Your Information page.
    $PayPalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token=';
}

// Here is an PHP example that shows how you would do both Direct Payment (Sale) and an Express Checkout (PayPal) transaction.
// This is for you review and only touches on the basic transations available to you.  Refer to the Payflow Pro Developer's Guide
// or the Websites Payments Pro Payflow Edition Developer's Guide (UK merchants).
//
// Review this sample and the comments contained within it along with the guides above.
//
// Explanation of the Request ID header.
// The request Id is a unique id that you send with your transaction data.  This Id if not changed
// will help prevent duplicate transactions.  The idea is to set this Id outside the loop or if on a page,
// prior to the final confirmation page.
//
// Once the transaction is sent and if you don't receive a response you can resend the transaction and the
// server will respond with the response data of the original submission. If the transaction was already processed
// you will receive back the original response with DUPLICATE=1 appended to the end.
//
// This allows you to resend transaction requests should there be a network or user issue without re-charging
// a customers credit card.

// header
echo '
<html>
<head>
<title>My Store</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<div align="right"><a href="http://localhost/payflow_example_ec.php">Start Again</a></div>';

// get down to business
switch ($action) {

case 'SetExpressCheckout':
    $amount = $_POST['price1']*$_POST['qty1']+$_POST['price2']*$_POST['qty2']+$_POST['price3']*$_POST['qty3'];
    $cancel_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
    $return_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?x=GetExpressCheckoutDetails&order_num='.$order_num.'&amount='.$amount;

    // Mike Challis (www.carmosaic.com) added feature:
    // use an array to build the query for better visual representation in the php code.
    // and for the bracketed numbers

    $paypal_query_array = array(

        'USER'       => $user,
        'VENDOR'     => $vendor,
        'PARTNER'    => $partner,
        'PWD'        => $password,
        'TENDER'     => 'P',  // P - Express Checkout using PayPal account
        'TRXTYPE'    => 'S',  // S - Sale
        'ACTION'     => 'S',  // S - Sale
        'AMT'        => $amount,
        'CURRENCY'   => $currency,
        'CANCELURL'  => $cancel_url,
        'RETURNURL'  => $return_url,
        'INVNUM'     => $order_num,
        'ORDERDESC'  => $desc,
        // Display the billing address on the GetEC call.  Account must be setup with PayPal to allow this feature.
        'REQBILLINGADDRESS' => '1', // 0 = Do not display, 1 = Display
        );

    // Mike Challis (www.carmosaic.com) added feature: bracketed numbers.
    // Bracketed numbers are length tags which allow you
    // to use the special characters of "&" and "=" in the value sent.

    foreach ($paypal_query_array as $key => $value) {
				$paypal_query[]= $key.'['.strlen($value).']='.$value;
		}
		$paypal_query=implode('&', $paypal_query);
    
    // The $order_num field is storing our unique id that we'll use in the request id header.  By storing the id
    // in this manner, we are able to allowing reposting of the form without creating a duplicate transaction.
    $unique_id = $order_num;

    // call function to return name-value pair
    $nvpArray = fetch_data($unique_id, $submiturl, $paypal_query);

    if($nvpArray['RESPMSG']=='Approved') {
        // After you receive a successful response from PayPal, you should add the value of the Token from SetExpressCheckoutResponse as a
        // name/value pair to the following URL, and redirect your customer’s browser to it:
        // Live: https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=value_from_SetExpressCheckoutResponse
        // Pilot: https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=value_from_SetExpressCheckoutResponse
        // Express Checkout has a variation on this redirect URL that allows you to bypass calling the second API (GetExpressCheckoutDetails)
        // and to change the text of the final button displayed on PayPal. See the Developer's Guide for more details.
        // Recommendation for Browser Redirection:
        // For redirecting the customer’s browser to the PayPal URL, PayPal recommends that you use the HTTPS response 302 “Object Moved” with
        // your URL as the value of the Location header in the HTTPS response. Ensure that you use an SSL-enabled server to prevent browser
        // warnings about a mix of secure and insecure graphics.
        $payPalURL = $PayPalURL.urldecode($nvpArray["TOKEN"]);
        echo '
        <html>
        <head>
        <META HTTP-EQUIV="Refresh"CONTENT="0;URL='.$payPalURL.'">
        </head>
        <body>
        <a href="'.$payPalURL.'">Click here</a> if you are not redirected to PayPal within 5 seconds.
        </body>
        </html>';
    } else {
        // error_handle($nvpArray);
        // Check for results and display approval or decline.
    		response_handler($nvpArray, $fraud);
        exit;
    }
break;

case 'GetExpressCheckoutDetails':
    // After the customer has selected shipping and billing information on the PayPal website and clicks Pay, which is the customer’s approval
    // of the use of PayPal. PayPal then redirects the customer’s browser to your website using the ReturnURL specified by you in
    // the SetExpressCheckoutRequest. If the customer clicks the Cancel button, PayPal returns him to the CancelURL specified in the
    // SetExpressCheckoutRequest.

    $data = $paypal.'&TENDER=P&TRXTYPE=S&ACTION=G&TOKEN='.$_REQUEST['token'];
    // prepare unique id for Action=G.  Each part of Express Checkout must
    // have a unique request ID.
    $unique_id = generateGUID();
    // call function to return name-value pair
    $nvpArray = fetch_data($unique_id, $submiturl, $data);
    //echo $nvpArray;
    if($nvpArray['RESPMSG']=='Approved') {
        // Since the transaction was approved, display the information returned from PayPal.  At this time, you'd determine
        // what to display to your customer and what data to store in your db.
        echo '<h4>Processing order ... GetEC Response</h4>
        <form name="DoExpressCheckout" action="" method="POST">
        <table border="1" width="400">
        <tr><td>Token:</td><td>'.$_REQUEST['token'].'</td></tr>
        <tr><td>Order Total:</td><td>'.number_format($amount,2).' '.$currency.'</td></tr>
        <tr><td>Order Number:</td><td>'.$nvpArray['INVNUM'].'</td></tr>
        <tr><td colspan="2">Buyer Details:</td></tr>
        <tr><td>First Name:</td><td>'.$nvpArray['FIRSTNAME'].'</td></tr>
        <tr><td>Last Name:</td><td>'.$nvpArray['LASTNAME'].'</td></tr>
        <tr><td>Email Address:</td><td>'.$nvpArray['EMAIL'].'</td></tr>
        <tr><td>Payer Status:</td><td>'.$nvpArray['PAYERSTATUS'].'</td></tr>';
        if (isset($nvpArray['STREET'])) {
        	echo '
        	<tr><td colspan="2">Billing Address:</td></tr>
        	<tr><td>Address Line 1:</td><td>'.$nvpArray['STREET'].'</td></tr>
        	<tr><td>City:</td><td>'.$nvpArray['CITY'].'</td></tr>
        	<tr><td>State:</td><td>'.$nvpArray['STATE'].'</td></tr>
        	<tr><td>Postal Code:</td><td>'.$nvpArray['ZIP'].'</td></tr>
        	<tr><td>Country:</td><td>'.$nvpArray['COUNTRYCODE'].'</td></tr>';
        }
      	echo '
        <tr><td colspan="2">Shipping Address:</td></tr>
        <tr><td>Address Line 1:</td><td>'.$nvpArray['SHIPTOSTREET'].'</td></tr>';
        if (isset($nvpArray['SHIPTOSTREET2'])) {
            echo '<tr><td>Address Line 2:</td><td>'.$nvpArray['SHIPTOSTREET2'].'</td></tr>';
        }
        echo '<tr><td>City:</td><td>'.$nvpArray['SHIPTOCITY'].'</td></tr>
        <tr><td>State:</td><td>'.$nvpArray['SHIPTOSTATE'].'</td></tr>
        <tr><td>Postal code:</td><td>'.$nvpArray['SHIPTOZIP'].'</td></tr>
        <tr><td>Country:</td><td>'.$nvpArray['SHIPTOCOUNTRY'].'</td></tr><tr>
				<tr><td>Address Status:</td><td>'.$nvpArray['ADDRESSSTATUS'].'</td></tr>
        <td colspan="2"><input type="hidden" name="x" value="DoExpressCheckout"><input type="submit" value="Pay" /></td></tr>
        </table>
        <input type="hidden" name="token" value="'.$_REQUEST['token'].'">
        <input type="hidden" name="payerid" value="'.$_REQUEST['PayerID'].'">
        <input type="hidden" name="order_num" value="'.$nvpArray['INVNUM'].'">
        <input type="hidden" name="unique" value="'.generateGUID().'">
        </form>';
    } else {
        //Whoops, something went wrong.  Display generic error.
        // error_handle($nvpArray);
        // Check for results and display approval or decline.
    		response_handler($nvpArray, $fraud);
        exit;
    }
break;

case 'DoExpressCheckout':
    // After you receive a successful GetExpressCheckoutDetailsResponse, you would display a order review page (ie shipping information) or a
    // page on which the customer can select a shipping method, enter shipping instructions, or specify any other information necessary to
    // complete the purchase.
    //
    // When the customer clicks the “Place Order” button, send DoExpressCheckoutPaymentRequest to initiate the payment. After a successful
    // response is sent from PayPal, direct the customer to your order completion page to inform him that you received his order.
    $token = urlencode($_REQUEST['token']);
    $payer_id = urlencode($_REQUEST['payerid']);
    $serverName = urlencode($_SERVER['SERVER_NAME']);

    // Mike Challis (www.carmosaic.com) added feature:
    // use an array to build the query for better visual representation in the php code.
    // and for the bracketed numbers

    $paypal_query_array = array(

        'USER'       => $user,
        'VENDOR'     => $vendor,
        'PARTNER'    => $partner,
        'PWD'        => $password,
        'TENDER'     => 'P',  // P - Express Checkout using PayPal account
        'TRXTYPE'    => 'S',  // S - Sale
        'ACTION'     => 'D',  //
        'TOKEN'      => $token,
        'PAYERID'    => $payer_id,
        'AMT'        => $amount,
        'CURRENCY'   => $currency,
        'IPADDRESS'  => $serverName,
        'INVNUM'     => $order_num,
        'ORDERDESC'  => $desc,
        );

    // Mike Challis (www.carmosaic.com) added feature: bracketed numbers.
    // Bracketed numbers are length tags which allow you
    // to use the special characters of "&" and "=" in the value sent.
    // http://paypaldeveloper.com/pdn/board/message?board.id=payflow&thread.id=1008

    foreach ($paypal_query_array as $key => $value) {
				$paypal_query[]= $key.'['.strlen($value).']='.$value;
		}
		$paypal_query=implode('&', $paypal_query);

    $unique_id = $_REQUEST['unique'];        // get it from GetExpressCheckoutDetails form so that no duplication

    // call function to return name-value pair
    $nvpArray = fetch_data($unique_id, $submiturl, $paypal_query);

    // Check for results and display approval or decline.
    response_handler($nvpArray, $fraud);
break;

case 'DoDirectPayment':
    // Payment details
    $card_num = str_replace(' ','',$_POST['card_num']);
    $card = $_POST['card'];
    $cvv2 = $_POST['cvv2'];        // 123
    $expiry = $_POST['mm'].substr($_POST['yy'],2,2); // We only use a 2-digit year.  Need this due to bug in PHP on the date function.
    $amount = number_format($_POST['amount'],2);  // format to valid amount, ie removal of commas and 2-decimals.
    $currency = $_POST['currency'];
    $card_start = $_POST['cardstart'];
    $card_issue = $_POST['cardissue'];
    // Billing Details
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $addr1 = $_POST['street'];
    $addr2 = $_POST['city'];
    $addr3 = $_POST['state'];
    $addr4 = $_POST['zip'];
    $country = $_POST['country'];        // 3-digits ISO code
    // Other information
    $custom = 'Testing Only';

    // Transaction results (especially values for declines and error conditions) returned by each PayPal-supported
    // processor vary in detail level and in format. The Payflow Verbosity parameter enables you to control the kind
    // and level of information you want returned.
    // By default, Verbosity is set to LOW. A LOW setting causes PayPal to normalize the transaction result values.
    // Normalizing the values limits them to a standardized set of values and simplifies the process of integrating
    // the Payflow SDK.
    // By setting Verbosity to MEDIUM, you can view the processor’s raw response values. This setting is more “verbose”
    // than the LOW setting in that it returns more detailed, processor-specific information.
    // Review the chapter in the Developer's Guides regarding VERBOSITY and the INQUIRY function for more details.
    // Set the transaction verbosity to MEDIUM.

    // Mike Challis (www.carmosaic.com) added feature:
    // use an array to build the query for better visual representation in the php code.
    // and for the bracketed numbers

    $paypal_query_array = array(

        'USER'       => $user,
        'VENDOR'     => $vendor,
        'PARTNER'    => $partner,
        'PWD'        => $password,
        'TENDER'     => 'C',  // C - Direct Payment using credit card
        'TRXTYPE'    => 'A',  // A - Authorization, S - Sale
        'ACCT'       => $card_num,
        'CVV2'       => $cvv2,
        'EXPDATE'    => $expiry,
        'ACCTTYPE'   => $card,
        'AMT'        => $amount,
        'CURRENCY'   => $currency,
        'FIRSTNAME'  => $fname,
        'LASTNAME'   => $lname,
        'STREET'     => $addr1,
        'CITY'       => $addr2,
        'STATE'      => $addr3,
        'ZIP'        => $addr4,
        'COUNTRY'    => $country,
        'EMAIL'      => $email,
        'CUSTIP'     => $cust_ip,
        'COMMENT1'   => $custom,
        'INVNUM'     => $order_num,
        'ORDERDESC'  => $desc,
        'VERBOSITY'  => 'MEDIUM',
       	'CARDSTART'  => $card_start,
       	'CARDISSUE'  => $card_issue,
				);
				
    // Mike Challis (www.carmosaic.com) added feature: bracketed numbers.
    // Bracketed numbers are length tags which allow you
    // to use the special characters of "&" and "=" in the value sent.

    foreach ($paypal_query_array as $key => $value) {
				$paypal_query[]= $key.'['.strlen($value).']='.$value;
		}
		$paypal_query=implode('&', $paypal_query);


    // The $order_num field is storing our unique id that we'll use in the request id header.  By storing the id
    // in this manner, we are able to allowing reposting of the form without creating a duplicate transaction.
    $unique_id = $order_num;

    // Call the function to send data to PayPal and return the data into an Array.
    $nvpArray = fetch_data($unique_id, $submiturl, $paypal_query);

    // Check for results and display approval or decline.
    response_handler($nvpArray, $fraud);

    // end
break;

case 'GetDirectPaymentDetails':
    $amount = $_POST['price1']*$_POST['qty1']+$_POST['price2']*$_POST['qty2']+$_POST['price3']*$_POST['qty3'];
    // checkout - enter card details and shipping
    echo '
    <form name="checkout" method="post" action="">
    <table width="100%" border="0" cellspacing="0" cellpadding="3">
    <tr><td width="25%">Total:</td><td width="75%"><input type="text" name="amount" value="'.number_format($amount,2).'"> '.$currency.'</td></tr>
    <tr><td>Order Number:</td><td><input type="text" name="order_num" value="'.$order_num.'" maxlength="12" readonly></td></tr>
    <tr><td>Credit Card:</td><td><select name="card">
    <option value="0">Visa</option><option selected="selected" value="1">MasterCard</option>
    <option value="8">American Express</option></select></td></tr>
    <tr><td>Card Number:</td><td><input type="text" name="card_num" value="5105105105105100" maxlength="100"></td></tr>
    <tr><td>Expiry Date:</td><td><select name="mm">
    <option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option>
    <option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option>
    <option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12" selected>12</option>
    </select><select name="yy">
    <option value="'.(date('Y')).'">'.(date('Y')).'</option><option value="'.(date('Y')+1).'">'.(date('Y')+1).'</option>
    <option value="'.(date('Y')+2).'" selected>'.(date('Y')+2).'</option><option value="'.(date('Y')+3).'">'.(date('Y')+3).'</option>
    <option value="'.(date('Y')+4).'">'.(date('Y')+4).'</option><option value="'.(date('Y')+5).'">'.(date('Y')+5).'</option>
    </select></td></tr>
    <tr><td>Card Verification Number:</td><td><input type="text" name="cvv2" value="123" maxlength="35"></td></tr>
    <tr><td>Card Start:</td><td><input type="text" name="cardstart" value="" maxlength="4"></td></tr>
    <tr><td>Card Issue:</td><td><input type="text" name="cardissue" value="" maxlength="2"></td></tr>
    <tr><td></td><td>NOTE: For a Switch or Solo transaction to be approved,<br> either Card Start or Card Issue must be present.</td></tr>
    <tr><td>First Name:</td><td><input type="text" name="fname" value="'.$fname.'" maxlength="35"></td></tr>
    <tr><td>Last Name:</td><td><input type="text" name="lname" value="'.$lname.'" maxlength="35"></td></tr>
    <tr><td>Street:</td><td><input type="text" name="street" value="'.$addr1.'" maxlength="100"></td></tr>
    <tr><td>City/Town:</td><td><input type="text"name="city" value="'.$addr2.'" maxlength="35"></td></tr>
    <tr><td>State/County:</td><td><input type="text" name="state" value="'.$addr3.'" maxlength="35"></td></tr>
    <tr><td>Postcode:</td><td><input type="text" name="zip" value="'.$addr4.'" maxlength="18"></td></tr>
    <tr><td>Country:</td><td><input type="text" name="country" value="'.$country.'" maxlength="35"></td></tr>
    <tr><td>Notification E-mail:</td><td><input type="text" name="email" value="'.$email.'" maxlength="150"></td></tr>
    <tr><td><input type="hidden" name="currency" value="'.$currency.'"><input type="hidden" name="x" value="DoDirectPayment"></td>
    <td><input type="submit" value=" Pay "></td></tr>
    </table>
    </form>';
break;

default:
    session_unset();
    // Simulated shopping cart list of items.
    // Generating random order number.  This order number is also being used for the Request Id.
    // If you want to create even a more unique id to prevent duplicate transactions, see the GenerateGUID function
    // at the bottom of this example.
    $order_num = date('ymdH').rand(1000,9999);
    echo '
    <form name="form1" method="post" action="">
    <table width="450" border="0" cellspacing="1" cellpadding="3">
    <tr><td width="65%"><strong>Product Description</strong></td>
    <td width="20%"><strong>Unit Price</strong></td>
    <td width="15%"><strong>Quantity</strong></td></tr>
    <tr><td>Tooth Brush</td><td>'.$currency.' 2.50<input type="hidden" name="price1" value="2.50"></td>
    <td><input type="text" name="qty1" size="5" value="1"> </td></tr>
    <tr><td>Tooth Paste</td><td>'.$currency.' 3.95<input type="hidden" name="price2" value="3.95"></td>
    <td><input type="text" name="qty2" size="5" value="0"></td></tr>
    <tr><td>Dental Floss</td><td>'.$currency.' 1.75<input type="hidden" name="price3" value="1.75"></td>
    <td><input type="text" name="qty3" size="5" value="0"></td></tr>
    <tr valign="bottom"><td><br>Pay by: <br>
    <input type="radio" name="x" value="GetDirectPaymentDetails" checked="checked">Credit Card (Direct Payment)<br>
    <input type="radio" name="x" value="SetExpressCheckout">PayPal Express Checkout</td>
    <td colspan="2" align="right"><input type="hidden" name="order_num" value="'.$order_num.'">
    <input type="submit" name="Submit" value="Submit"></td></tr>
    </table>
    </form>';
break;
}

// footer
echo '
</body>
</html>';

// API functions and error handling
function fetch_data($unique_id, $submiturl, $data) {

    // get data ready for API
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    // Here's your custom headers; adjust appropriately for your setup:
    $headers[] = "Content-Type: text/namevalue"; //or text/xml if using XMLPay.
    $headers[] = "Content-Length : " . strlen ($data);  // Length of data to be passed 
    // Here I set the server timeout value to 45, but notice below in the cURL section, I set the timeout
    // for cURL to 90 seconds.  You want to make sure the server timeout is less, then the connection.
    $headers[] = "X-VPS-Timeout: 45";
    $headers[] = "X-VPS-Request-ID:" . $unique_id;

    // Optional Headers.  If used adjust as necessary.
    //$headers[] = "X-VPS-VIT-OS-Name: Linux";                    // Name of your OS
    //$headers[] = "X-VPS-VIT-OS-Version: RHEL 4";                // OS Version
    //$headers[] = "X-VPS-VIT-Client-Type: PHP/cURL";             // What you are using
    //$headers[] = "X-VPS-VIT-Client-Version: 0.01";              // For your info
    //$headers[] = "X-VPS-VIT-Client-Architecture: x86";          // For your info
    //$headers[] = "X-VPS-VIT-Integration-Product: PHPv4::cURL";  // For your info, would populate with application name
    //$headers[] = "X-VPS-VIT-Integration-Version: 0.01";         // Application version
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $submiturl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HEADER, 1);                // tells curl to include headers in response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        // return into a variable
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);              // times out after 90 secs
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);        // this line makes it work under https
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);        //adding POST data
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);       //verifies ssl certificate
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);       //forces closure of connection when done
    curl_setopt($ch, CURLOPT_POST, 1); 									//data sent as POST

    //echo $data;
    echo '<br><br>';
    // Try to submit the transaction up to 3 times with 5 second delay.  This can be used
    // in case of network issues.  The idea here is since you are posting via HTTPS there
    // could be general network issues, so try a few times before you tell customer there
    // is an issue.

    echo '<h4>Processing order ... please wait</h4>';
    $i=1;
    while ($i++ <= 3) {
        $result = curl_exec($ch);
        $headers = curl_getinfo($ch);
        //print_r($headers);
        //echo '<br>';
        //print_r($result);
        //echo '<br>';
        if ($headers['http_code'] != 200) {
            sleep(5);  // Let's wait 5 seconds to see if its a temporary network issue.
        }
        else if ($headers['http_code'] == 200) {
            // we got a good response, drop out of loop.
            break;
        }
    }
    // In this example I am looking for a 200 response from the server prior to continuing with
    // processing the order.  You can use this or other methods to validate a response from the
    // server and/or timeout issues due to network.
    if ($headers['http_code'] != 200) {
        echo '<h2>General Error!</h2>';
        echo '<h3>Unable to receive response from PayPal server.</h3><p>';
        echo '<h4>Verify host URL of '.$submiturl.' and check for firewall/proxy issues.</h4>';
        curl_close($ch);
        exit;
    }
    curl_close($ch);
    $result = strstr($result, "RESULT");
    //echo $result;
    // prepare responses into array
    $proArray = array();
    while(strlen($result)){
        // name
        $keypos= strpos($result,'=');
        $keyval = substr($result,0,$keypos);
        // value
        $valuepos = strpos($result,'&') ? strpos($result,'&'): strlen($result);
        $valval = substr($result,$keypos+1,$valuepos-$keypos-1);
        // decoding the respose
        $proArray[$keyval] = $valval;
        $result = substr($result,$valuepos+1,strlen($result));
    }
    return $proArray;
}

function response_handler($nvpArray, $fraud) {

    $result_code = $nvpArray['RESULT']; // get the result code to validate.
    $RespMsg = 'General Error.  Please contact Customer Support.';  // Generic error for all results not captured below.

    // Part of accepting credit cards or PayPal is to determine what your business rules are.  Basically, what risk are you
    // willing to take, especially with credit cards.  The code below gives you an idea of how to check the results returned
    // so you can determine how to handle the transaction.
    //
    // This is not an exhaustive list of failures or issues that could arise.  Review the list of Result Code's in the
    // Developer Guides and add logic as you deem necessary.
    // These responses are just an example of what you can do and how you handle the response received
    // from the bank/PayPal is dependent on your own business rules and needs.
    //
    // Evaluate Result Code returned from PayPal.
    // Since you are posting via HTTPS you would not see any negative result codes as documented in the developer's guide.
    // This is due to the fact that negative result codes are generated from the SDK, not the server.
    if ($result_code == 1 || $result_code == 26) {
        // This is just checking for invalid login credentials.  You normally would not display this type of message.
        // Result code 26 will be issued if you do not provide both the <vendor> and <user> fields.
        // Remember: <vendor> = your merchant (login id), <user> = <vendor> unless you created a seperate <user> for Payflow Pro.
        //
        // The other most common error with authentication is result code 1, user authentication failed.  This is usually
        // due to invalid account information or ip restriction on the account.  You can verify ip restriction by logging
        // into Manager.  See Service Settings >> Allowed IP Addresses.  Lastly it could be you forgot the path "/transaction"
        // on the URL.
        $RespMsg = "Account configuration issue.  Please verify your login credentials.<br>See comments contained in this sample
        			and read this <a href='http://www.paypaldeveloper.com/pdn/board/message?board.id=payflow&message.id=1388' 
        			target='_blank'>post</a> for more information.";
    }
    else if ($result_code == 0) {
        // Example of a message you might want to display with an approved transaction.
        $RespMsg = "Your transaction was approved. We will ship in 24 hours.";
        // Even though the transaction was approved, you still might want to check for AVS or CVV2(CSC) prior to
        // accepting the order.  Do realize that credit cards are approved (charged) regardless of the AVS/CVV2 results.
        // Should you decline (void) the transaction, the card will still have a temporary charge (approval) on it.
        //
        // Check AVS - Street/Zip
        // In the message below it shows what failed, ie street, zip or cvv2.  To prevent fraud, it is suggested
        // you only give a generic billing error message and not tell the card-holder what is actually wrong.  However,
        // that decision is yours.
        //
        // Also, it is totally up to you on if you accept only "Y" or allow "N" or "X".  You need to decide what
        // business logic and liability you want to accept with cards that either don't pass the check or where
        // the bank does not participate or return a result.  Remember, AVS is mostly used in the US but some foreign
        // banks do participate.

        // Remember, this just an example of what you might want to do.
        if (isset($nvpArray['AVSADDR'])) {
            if ($nvpArray['AVSADDR'] != "Y") {
                // Display message that transaction was not accepted.  At this time, you
                // could display message that information is incorrect and redirect user
                // to re-enter STREET and ZIP information.  However, there should be some sort of
                // 3 strikes your out check.
                $RespMsg = "Your billing (street) information does not match. Please re-enter.";
                // Here you might want to put in code to flag or void the transaction depending on your needs.
            }
        }
        if (isset($nvpArray['AVSZIP'])) {
            if ($nvpArray['AVSZIP'] != "Y") {
                // Display message that transaction was not accepted.  At this time, you
                // could display message that information is incorrect and redirect user
                // to re-enter STREET and ZIP information.  However, there should be some sort of
                // 3 strikes your out check.
                $RespMsg = "Your billing (zip) information does not match. Please re-enter.";
                // Here you might want to put in code to flag or void the transaction depending on your needs.
            }
        }
        if (isset($nvpArray['CVV2MATCH'])) {
            if ($nvpArray['CVV2MATCH'] != "Y") {
                // Display message that transaction was not accepted.  At this time, you
                // could display message that information is incorrect.  Normally, to prevent
                // fraud you would not want to tell a customer that the 3/4 digit number on
                // the credit card was invalid.
                $RespMsg = "Your billing (cvv2) information does not match. Please re-enter.";
                // Here you might want to put in code to flag or void the transaction depending on your needs.
            }
        }
    }
    else if ($result_code == 12) {
        // Hard decline from bank.
        $RespMsg = "Your transaction was declined.";
    }
    else if ($result_code == 13) {
        // Voice authorization required.
        $RespMsg = "Your Transaction is pending. Contact Customer Service to complete your order.";
    }
    else if ($result_code == 23 || $result_code == 24) {
        // Issue with credit card number or expiration date.
        $RespMsg = "Invalid credit card information. Please re-enter.";
    }
    // Using the Fraud Protection Service.
    // This portion of code would be is you are using the Fraud Protection Service, this is for US merchants only.
    if ($fraud == 'YES') {
        // 125, 126 and 127 are Fraud Responses.
        // Refer to the Payflow Pro Fraud Protection Services User's Guide or
        // Website Payments Pro Payflow Edition - Fraud Protection Services User's Guide.
        if ($result_code == 125) {
            // 125 = Fraud Filters set to Decline.
            $RespMsg = "Your Transaction has been declined. Contact Customer Service to place your order.";
        }
        else if ($result_code == 126) {
            // 126 = One of more filters were triggered.  Here you would check the fraud message returned if you
            // want to validate data.  For example, you might have 3 filters set, but you'll allow 2 out of the
            // 3 to consider this a valid transaction.  You would then send the request to the server to modify the
            // status of the transaction.  This outside the scope of this sample.  Refer to the Fraud Developer's Guide.
            $RespMsg = "Your Transaction is Under Review. We will notify you via e-mail if accepted.";
        }
        else if ($result_code == 127) {
            // 127 = Issue with fraud service.  Manually, approve?
            $RespMsg = "Your Transaction is Under Review. We will notify you via e-mail if accepted.";
        }
    }
    // This would simulate displaying the message to your customer.  Also, the results returned from
    // the server are displayed too.
    displayResponse($RespMsg, $nvpArray);
}

function displayResponse($RespMsg, $nvpArray) {

    echo '<p>Results returned from server: <br><br>';
    while (list($key, $val) = each($nvpArray)) {
        echo "\n" . $key . ": " . $val . "\n<br>";
    }
    echo '</p>';
    // Was this a duplicate transaction, ie the request ID was NOT changed.
    // Remember, a duplicate response will return the results of the orignal transaction which
    // could be misleading if you are debugging your software.
    // For Example, let's say you got a result code 4, Invalid Amount from the orignal request because
    // you were sending an amount like: 1,050.98.  Since the comma is invalid, you'd receive result code 4.
    // RESULT=4&PNREF=V18A0C24920E&RESPMSG=Invalid amount&PREFPSMSG=No Rules Triggered
    // Now, let's say you modified your code to fix this issue and ran another transaction but did not change
    // the request ID.  Notice the PNREF below is the same as above, but DUPLICATE=1 is now appended.
    // RESULT=4&PNREF=V18A0C24920E&RESPMSG=Invalid amount&DUPLICATE=1
    // This would tell you that you are receving the results from a previous transaction.  This goes for
    // all transactions even a Sale transaction.  In this example, let's say a customer ordered something and got
    // a valid response and now a different customer with different credit card information orders something, but again
    // the request ID is NOT changed, notice the results of these two sales.  In this case, you would have not received
    // funds for the second order.
    // First order: RESULT=0&PNREF=V79A0BC5E9CC&RESPMSG=Approved&AUTHCODE=166PNI&AVSADDR=X&AVSZIP=X&CVV2MATCH=Y&IAVS=X
    // Second order: RESULT=0&PNREF=V79A0BC5E9CC&RESPMSG=Approved&AUTHCODE=166PNI&AVSADDR=X&AVSZIP=X&CVV2MATCH=Y&IAVS=X&DUPLICATE=1
    // Again, notice the PNREF is from the first transaction, this goes for all the other fields as well.
    // It is suggested that your use this to your benefit to prevent duplicate transaction from the same customer, but you want
    // to check for DUPLICATE=1 to ensure it is not the same results as a previous one.
    if(isset ($nvpArray['DUPLICATE'])) {
            echo '<h2>Error!</h2><p>This is a duplicate of your previous order.</p>';
            echo '<p>Notice that DUPLICATE=1 is returned and the PNREF is the same ';
            echo 'as the previous one.  You can see this in Manager as the Transaction ';
            echo 'Type will be "N".';
    }
    if (isset($nvpArray['PPREF'])) {
        // Check if PayPal Express Checkout and if order is Pending.
        if (isset($nvpArray['PENDINGREASON'])) {
        	if ($nvpArray['PENDINGREASON']=='completed') {
            	echo '<h2>Transaction Completed!</h2>';
            	echo '<h3>'.$RespMsg.'</h3><p>';
            	echo '<h4>Note: To simulate a duplicate transaction, refresh this page in your browser.  ';
            	echo 'Notice that you will see DUPLICATE=1 returned.</h4>';
        	} elseif($nvpArray['PENDINGREASON']=='echeck') {
            	// PayPal transaction
            	echo '<h2>Transaction Completed!</h2>';
            	echo '<h3>The payment is pending because it was made by an eCheck that has not yet cleared.</h3';
        	} else {
     		// PENDINGREASON not 'completed' or 'echeck'.  See Integration guide for more responses.
     		echo '<h2>Transaction Completed!</h2>';
     		echo '<h3>The payment is pending due to: '.$nvpArray['PENDINGREASON'];
     		echo '<h4>Please login to your PayPal account for more details.</h4>';
     		}
     	}
    } else {
    	if ($nvpArray['RESULT'] == "0") {
    		echo '<h2>Transaction Completed!</h2>';
    	} else {
    		echo '<h2>Transaction Failure!</h2>';
    	}
    	echo '<h3>'.$RespMsg.'</h3><p>';
    	if ($nvpArray['RESULT'] != "26" && $nvpArray['RESULT'] != "1") {
    		echo '<h4>Note: To simulate a duplicate transaction, refresh this page in your browser.&nbsp';
    		echo 'Notice that you will see DUPLICATE=1 returned.</h4>';
    	}
    }
}

function generateCharacter () {
    $possible = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
    return $char;
}

function generateGUID () {
    $GUID = generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter()."-";
    $GUID = $GUID .generateCharacter().generateCharacter().generateCharacter().generateCharacter()."-";
    $GUID = $GUID .generateCharacter().generateCharacter().generateCharacter().generateCharacter()."-";
    $GUID = $GUID .generateCharacter().generateCharacter().generateCharacter().generateCharacter()."-";
    $GUID = $GUID .generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter().generateCharacter();
    return $GUID;
}

function error_handle($nvpArray) {
    echo '<h2>Error!</h2><p>We were unable to process your order.</p>';
    echo '<p>Error '.$nvpArray['RESULT'].': '.$nvpArray['RESPMSG'].'.</p>';
    while (list($key, $val) = each($nvpArray)) {
        echo "\n" .  $key .  ": " . $val .  "\n<br>";
    }
}
?>