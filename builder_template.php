<?php

require('db.php');
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 300);
//place this before any script you want to calculate time
$time_start = microtime(true); 

echo "<h2>Form POC - Submitted Results</h2>";
   
	if(! $conn ) {
	   die('Could not connect, please try again later: ' . mysqli_error($conn));
	} else {
        
            echo '<p>Input 1 <input name="input1" type="text" value="' . htmlspecialchars($_POST["input1"]) . '"/></p>';
            echo '<p>Input 2 <input name="input2" type="text" value="' . htmlspecialchars($_POST["input2"]) . '"/></p>';
            echo '<p>Input 3 <input name="input3" type="text" value="' . htmlspecialchars($_POST["input3"]) . '"/></p>'; 
        
            echo '<p> Radio question?</p>';
            echo '<p>';
                if($_POST["radio1"] == "yes")
                {
                    echo '(If "yes" was selected) ';
                    echo '<label for="yes">Yes</label><input type="radio" name="radio1" id="yes" value="yes" checked>';
                    echo '<label for="no">No</label><input type="radio" name="radio1" id="no" value="no">';
                }
                else
                {
                    echo '(If "no" was selected) ';
                    echo '<label for="yes">Yes</label><input type="radio" name="radio1" id="yes" value="yes">';
                    echo '<label for="no">No</label><input type="radio" name="radio1" id="no" value="no" checked>';  
                }  
        
            echo '</p>';
        //verify form was submitted
        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            //create insert statement for case where no files were uploaded
            if ($stmt = mysqli_prepare($conn, "INSERT INTO form_poc (input1, input2, input3, radio1) VALUES (?, ?, ?, ?)")) {
                mysqli_stmt_bind_param($stmt, 'ssss', $_POST["input1"], $_POST["input2"], $_POST["input3"], $_POST["radio1"]);
    
                    // determine number of individual files, maybe...
                    $successCount = array_reduce($_FILES, function($val, $file) {
                    if($file['error'] == UPLOAD_ERR_OK) return $val + 1;
                        return $val;
                    }, 0);
                    
                    echo "<p>individual file count: $successCount</p>";
                    
                    if($successCount > 0 || $_FILES['images']['size'][0] > 0){ //if any files were included in the form data
                        $imgArr = array(); // create array to hold individual upload images
                        $i=1; // initialize counter to access the individual images
                        
                        /* Another check to verify files were included, this one may be redundant, the $_FILES array is tricky
                        depending on single or multiple */
                        if(is_uploaded_file($_FILES["image{$i}"]['tmp_name']) || $_FILES['images']['size'][0] > 0) { 
                            foreach($_FILES as $file){ //loop through individual upload images
                                if(!empty($_FILES["image{$i}"]['tmp_name'])){ // only want to process those in the array that are populated
                                    echo "<p>file{$i} tmp_name: " . $_FILES["image{$i}"]['tmp_name'] ."</p>";
                                    $image =addslashes($_FILES["image{$i}"]['tmp_name']); //process image file
                                    $image = base64_encode(file_get_contents($image));      //process image file, used base64 encoding, suggested for transport of binary data as text and string type in mysqli parameter binding
                                    $imgArr[$i] = $image;  //add image file to array
                                }
                                $i++;
                            }

                            if($_FILES['images']['size'][0] > 0){  // again verifying if multiple upload was used by checking size of first file in array
                                //print_r($_FILES['images']['tmp_name']);
                                echo "<p>multiple file count: " . count($_FILES['images']['tmp_name']) . "</p>";
                                $imagesArr = array();  //create array to hold multiple upload images
                                for($j=0; $j<sizeof($_FILES['images']['tmp_name']); $j++){ // loop through multiple upload images
                                    if(!empty($_FILES['images']['tmp_name'])){ // only want to process actual files
                                        echo "<p>images[{$j}] tmp_name: " . $_FILES['images']['tmp_name'][$j] ."</p>";
                                        $image =addslashes($_FILES['images']['tmp_name'][$j]); //process image file
                                        $image = base64_encode(file_get_contents($image));      //process image file
                                        $imagesArr[$j] = $image;   //add image file to array for multiple uploaded files
                                    }
                                }
                            }

                            // insert form data into the database
                            if ($stmt = mysqli_prepare($conn, "INSERT INTO form_poc(input1, input2, input3, radio1, image1, image2, image3, image4, image5, image6) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
                                mysqli_stmt_bind_param($stmt, 'ssssssssss', $_POST["input1"], $_POST["input2"], $_POST["input3"], $_POST["radio1"], $imgArr[1], $imgArr[2], $imgArr[3], $imgArr[4], $imagesArr[0], $imagesArr[1]);
                                if (mysqli_stmt_execute($stmt)) {
                                    printf("%d Row inserted with %d images.\n", mysqli_stmt_affected_rows($stmt), $successCount+count($_FILES['images']['tmp_name']));
                                } else {
                                    $retval = sprintf("Error: %s.\n", mysqli_stmt_error($stmt));
                                }
                            }
                        }
                    } else { // no files to process
                        if (mysqli_stmt_execute($stmt)) {
                             printf("%d Row inserted with no images.\n", mysqli_stmt_affected_rows($stmt));
                        } else {
                            $retval = sprintf("Error: %s.\n", mysqli_stmt_error($stmt));
                        }
                    }
                } //if ($stmt = mysqli_prepare($conn, "INSERT...
            } //end if ($_SERVER['REQUEST_METHOD'] == 'POST'){
		
        // report errors if database inserts had issues
        
		if ($retval) {
            //may need to wrap the entire process in a transaction so it can rollback if any part fails
			echo '<p>Some of your data could not be saved. Please try again later.</p>';
			echo '<p>Database Error, could not enter data: ' . mysqli_error($conn);
            echo '</p>';
            echo '<p>retval is ' . $retval;
            echo '</p>';
		} else {
			echo "<p>Entered data successfully</p>";
		}
        
        //display images
        
            //echo '<p> Image 1 <input name="image1" type="file" /></p>';
            //echo '<p> Image 2 <input name="image2" type="file" /></p>';
            //echo '<p> Image 3 <input name="image3" type="file" /></p>';
            //echo '<p> Image 4 <input name="image4" type="file" /></p>';
                
            //echo '<p>Multiple File Upload <input name="images[]" id="images" type="file" multiple="multiple" /></p>';
        
        $stmt = "SELECT image1, image2, image3, image4, image5, image6 from form_poc ORDER BY id DESC LIMIT 0, 1"; 
        $result=mysqli_query($conn,$stmt);
        $row=mysqli_fetch_assoc($result);
        foreach ($row as $key=>$value){
            if($value){
                echo $key .'=> <img height="150" width="150" src="data:image;base64,'.$value.'">'; //using data stream from database query
            }
        }
         
        //release database connection
		mysqli_close($conn);
	}


//**
//** line to provide timing information for processing form data
//**

echo '<p> Total execution time in seconds: ' . (microtime(true) - $time_start) . '</p>';
