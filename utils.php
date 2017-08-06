<?php

// helper function to put a hidden field inside a form
function hidden($pname, $pvalue, $id = false) {
    $inpstr = "<input type=\"hidden\" name=\"$pname\"  value=\"$pvalue\"";
    if ($id) { $inpstr .= " id=\"$id\""; }
    $inpstr .= " />";
    return $inpstr;
}

function putdiv($value, $class="error") {
    $tc = "<div class=\"" . $class . "\">" . $value . "</div>";
    return $tc;
}

// function to make a curl call
function curl_call($host, $headertype=FALSE, $nvpstr=FALSE, $calltype="GET")
{
	//setting the curl parameters.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $host);
	if ($headertype) {
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headertype);
    }

	//turning off peer verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	// curl_setopt($ch, CURLOPT_VERBOSE, TRUE);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if ($calltype==="POST" && $nvpstr) {
    	curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpstr);
    } else {
    	curl_setopt($ch, CURLOPT_POST, FALSE);
        if ($calltype!="GET") { curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $calltype); }
        if ($nvpstr) { curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpstr); }
    }

	//getting response from server
    $response = curl_exec($ch);
    
    // handle errors
    if (curl_errno($ch)) {
        // moving to display page to display curl errors
        $_SESSION['curl_error_no']=curl_errno($ch) ;
        $_SESSION['curl_error_msg']= curl_error($ch) . 
                 "<br />host= $host .
                  <br />headertype= $headertype .
                  <br />nvpstr = $nvpstr . 
                  <br />response = " . print_r($response,true);
        // $location = "authfailure.php";
        // header("Location: $location");  
        // echo "Error from curl<br />" . $_SESSION['curl_error_msg'];
        // $nvpResArray = false;
        $nvpResArray = false; // array( "error" => curl_errno($ch), "response" => print_r($response,true) );
    } else {
        // convert json returned by Groovy into associative array
        $nvpResArray = json_decode($response, TRUE);
        if (!$nvpResArray) {
            $nvpResArray = false; // array( "error" => curl_errno($ch), "response" => print_r($response,true) );
            // $nvpResArray = "Error - not json<br />" . print_r($response,true);
        }
    }
    curl_close($ch);

    return $nvpResArray;
}
?>
