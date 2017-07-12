<?php
// header and footer
function htmlHeader($skindir) {
    $tc = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
    $tc.= '<html><head><title>Smart Motion Sensor Authorization</title>';
    $tc.= '<meta content="text/html; charset=iso-8859-1" http-equiv="Content-Type">';
    
    // load jQuery and themes
    $tc.= '<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';
    $tc.= '<script src="https://code.jquery.com/jquery-1.12.4.js"></script>';
    $tc.= '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
    
    // include hack from touchpunch.furf.com to enable touch punch through for tablets
    $tc.= '<script src="jquery.ui.touch-punch.min.js"></script>';
    
    // load custom .css and the main script file
    if (!$skindir) {
        $skindir = "skin-housepanel";
    }
    $tc.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$skindir\housepanel.css\">";
    $tc.= '<script type="text/javascript" src="housepanel.js"></script>';  
        // dynamically create the jquery startup routine to handle all types
        $tc.= '<script type="text/javascript">';
        $thingtypes = array("switch","lock","momentary","heat-dn","heat-up",
                            "cool-dn","cool-up","thermomode","thermofan",
                            "musicmute","musicstatus", 
                            "music-previous","music-pause","music-play","music-stop","music-next",
                            "level-dn","level-up", "level-val","mode");
        $tc.= '$(document).ready(function(){';
        foreach ($thingtypes as $thing) {
            $tc.= '  setupPage("' . $thing . '");';
        }
        $tc.= "});";
    $tc.= '</script>';

    // begin creating the main page
    // can be wrapped in a table but that messes up sortable feature
    // changed this to a div
    $tc.= '</head><body>';
    $tc.= '<div class="maintable">';
    return $tc;
}

function htmlFooter() {
    $tc = "";
    $tc.= "</div>";
    $tc.= "</body></html>";
    return $tc;
}

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
        $nvpResArray = array( "error" => curl_errno($ch), "response" => print_r($response,true) );
    } else {
        // convert json returned by Groovy into associative array
        $nvpResArray = json_decode($response, TRUE);
        if (!$nvpResArray) {
            $nvpResArray = array( "error" => curl_errno($ch), "response" => print_r($response,true) );
            // $nvpResArray = "Error - not json<br />" . print_r($response,true);
        }
    }
    curl_close($ch);

    return $nvpResArray;
}
?>
