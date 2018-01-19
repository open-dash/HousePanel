<?php
// Check if the form was submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Check if file was uploaded without errors
    if(isset($_FILES["fileInput"]) && $_FILES["fileInput"]["error"] == 0){
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = basename( $_FILES["fileInput"]["name"]);
        $filetype = $_FILES["fileInput"]["type"];
        $filesize = $_FILES["fileInput"]["size"];
		$rootdir = getcwd();
		$skindir = $_GET['skindir'];
		$pathstr = $rootdir . "/" . $skindir . "/icons/";	
		
    
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) echo json_encode("Error: Please select a valid file format.");
    
        // Verify file size - 5MB maximum
        $maxsize = 1.96 * 1024 * 1024;
        if($filesize > $maxsize) echo json_encode("Error: File size is larger than the allowed limit.");
    
        // Verify MYME type of the file
        if(in_array($filetype, $allowed)){
            // Check whether file exists before uploading it

		if (move_uploaded_file($_FILES["fileInput"]["tmp_name"], $pathstr . $filename)) {
			echo json_encode($filename);
			chmod($pathstr . $_FILES["fileInput"]["name"], 0777);				
		}

        } else{
            echo json_encode("Error: There was a problem uploading your file. Please try again."); 
        }
    } else{
         echo json_encode("Error: " . $filename);
    }

}

?>
