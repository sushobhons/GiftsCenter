<?php

// Function to send SOAP request using cURL
function sendSoapRequest($url, $xmlString) {

  // Create a cURL handle
  $ch = curl_init($url);

  // Set cURL options
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: text/xml; charset=utf-8',
//    'SOAPAction: "http://tempuri.org/IEcrComInterface/Settlement"',  // Replace with actual SOAPAction if needed
	'SOAPAction: "http://tempuri.org/IEcrComInterface/Sale"' ,

  ));

  // Execute the request and handle errors
  $response = curl_exec($ch);
  $error = curl_error($ch);
  $curlInfo = curl_getinfo($ch);

  curl_close($ch);

  if ($error) {
    echo "Error sending SOAP request: " . $error . PHP_EOL;
    return false;
  } else {
    // Check for successful HTTP response code (replace 200 with expected code if different)
    if ($curlInfo['http_code'] !== 200) {
      echo "Unsuccessful HTTP response: " . $curlInfo['http_code'] . PHP_EOL;
      return false;
    }

    return $response;  // Return the response if successful
  }
}

// Replace with your actual values (ensure they match MEPS requirements)
$xmlString = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/" xmlns:ns="http://schemas.datacontract.org/2004/07/">
   <soapenv:Header/>
   <soapenv:Body>
      <tem:Settlement>
         <tem:webReq>
            <ns:Config>
               <ns:EcrCurrencyCode>400</ns:EcrCurrencyCode>
               <ns:MerchantSecureKey>0123456789ABCDEF0123456789ABCDEF</ns:MerchantSecureKey>  
			   <ns:Mid>888888880000000</ns:Mid>  
			   <ns:Tid>04042024</ns:Tid>  
			   </ns:Config>
         </tem:webReq>
      </tem:Settlement>
   </soapenv:Body>
</soapenv:Envelope>';

$xmlSaleString = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/" xmlns:ns="http://schemas.datacontract.org/2004/07/">
   <soapenv:Header/>
   <soapenv:Body>
      <tem:Sale>
         <!--Optional:-->
         <tem:webReq>
            <!--Optional:-->
            <ns:Config>
               <!--Optional:-->
               <ns:EcrCurrencyCode>400</ns:EcrCurrencyCode>
               <!--Optional:-->
               <ns:EcrTillerFullName>flan</ns:EcrTillerFullName>
               <!--Optional:-->
               <ns:EcrTillerUserName>flanflan</ns:EcrTillerUserName>
               <!--Optional:-->
			<ns:MerchantSecureKey>0123456789ABCDEF0123456789ABCDEF</ns:MerchantSecureKey>
               <!--Optional:-->
               <ns:Mid>888888880000000</ns:Mid>
               <!--Optional:-->
               <ns:Tid>04042024</ns:Tid>
            </ns:Config>
            <!--Optional:-->
            <ns:EcrAmount>1.50</ns:EcrAmount>
            <!--Optional:-->
            <ns:Printer>
               <!--Optional:-->
               <ns:EnablePrintPosReceipt>1</ns:EnablePrintPosReceipt>
               <!--Optional:-->
               <ns:EnablePrintReceiptNote>0</ns:EnablePrintReceiptNote>
               <!--Optional:-->
               <ns:InvoiceNumber>INV_0001</ns:InvoiceNumber>
               <!--Optional:-->
               <ns:PrinterWidth>40</ns:PrinterWidth>
               <!--Optional:-->
               <ns:ReceiptNote>ff</ns:ReceiptNote>
               <!--Optional:-->
               <ns:ReferenceNumber>dddd</ns:ReferenceNumber>
            </ns:Printer>
         </tem:webReq>
      </tem:Sale>
   </soapenv:Body>
</soapenv:Envelope>';


// Target URL (replace with the actual URL for your environment)
$url = 'https://gprs.mepspay.com:6680/apex.smartpos.ecr/EcrComInterface.svc';

// Send the SOAP request and handle the response
//$response = sendSoapRequest($url, $xmlString);
$response = sendSoapRequest($url, $xmlSaleString);

//print_r($response);

if ($response) {
  echo "SOAP response:\n" . $response . PHP_EOL;
}

?>