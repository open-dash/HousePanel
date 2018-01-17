<?php
	$rootdir = getcwd();
	$skindir = $_GET['skindir'];
	$pathstr = $rootdir . "/" . $skindir . "/icons/";
	$filename = basename( $_FILES["fileInput"]["name"]);
if (move_uploaded_file($_FILES["fileInput"]["tmp_name"], $pathstr . $filename)) {
	echo json_encode($filename);
} else {

}
	 
function debug_to_console( $data ) {
    $output = $data;
    if ( is_array( $output ) )
        $output = implode( ',', $output);
    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}
 
?>



