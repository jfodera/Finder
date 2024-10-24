<?php include 'header.php'; 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once '../db/db_connect.php';
    
    $Description = $_POST['Description'];
    $Brand = $_POST['Brand'];
    $Color = $_POST['Color'];
    $Date = $_POST['Date'];
    $Time = $_POST['Time'];
    $Image = $_POST['Image'];
    $Location = $_POST['Location'];
  
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Finder - Recorder Item Info</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/info.css">
    <!-- <script src="../js/lost_item_form.js" defer></script> -->


</head>

<body>
    <div class="container">
        <section class="question">
            <h1>Describe the Item Being Recorded:</h1>
        </section>
        <div class="item_form_container">
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form id = "infoForm" action="item_form_recorder.php" method="post">
                <div class="page page-1 index active">
                        <h2>What type of item? </h2>

                        <div class="form_group">
                            <input type="text" name="type" placeholder="Type?" required>
                        </div>
                        <div class="form_group">
                            <input type="text" name="Brand" placeholder="Brand?" required>
                        </div>
                        <div class="form_group">
                            <input type="text" name="Color" placeholder="Color?" required>
                        </div>
                        <div class="form_group">
                            <input type="text" name="addInfo" placeholder="Any Additional Informtion" required>
                        </div>
                        
                        <button type="button" class="next-btn">Continue</button> 

                </div>

                <div class="page page-2">
                        <h2>When was it Found?</h2>
                        <h3>*Give approximate time*</h3>
                        <div class="form_group">
                                <input type="datetime-local" name="Date" placeholder="Date?" required>
                        </div>
                
                        <button type="button" class="prev-btn">Go Back</button> 
                        <button type="button" class="next-btn">Continue</button> 

                </div>

                <div class="page page-3 ">
                    <h2>Upload Item Image</h2>
                    <h3>*not required*</h3>
                    <img id="upload_image" src="../default_image.png" alt="image of the item">
                    <label id = "item_img" for = "input-file">upload image</label>
                        <div class="form_group">
                                <input id = "input-file" type="file" name="Image" accept = "image/jpeg,image/png,image/jpg" >
                        </div>
            
                        <button type="button" class="prev-btn">Go Back</button> 
                        <button type="button" class="next-btn">Continue</button> 

                </div>


                <div class="page page-4 ">
                    <h2>Where was it found?</h2>
                    <h3>*Can select multiple*</h3>
                    <div class="form_group">
                            <input type="text" name="Location" placeholder="Location Type?" required>
                    </div>
        
                    <button type="button" class="prev-btn">Go Back</button> 
                    <button type="submit" class="submit-btn">Submit</button> 

                </div>
                    
            </form>

        </div>

    </div>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const pages = Array.from(document.querySelectorAll('#infoForm .page'));
        const nextBtns = document.querySelectorAll('.next-btn');
        const prevBtns = document.querySelectorAll('.prev-btn');

        nextBtns.forEach(button => {
        button.addEventListener('click', () => {
            changePage('next');
        });
    });

    prevBtns.forEach(button => {
        button.addEventListener('click', () => {
            changePage('prev');
        });
    });

    
    function changePage(btn){
        const active = document.querySelector('#infoForm .page.active');
        let index = pages.indexOf(active);
        index = pages.indexOf(active);
        pages[index].classList.remove('active');
        if(btn ==='next'){
            index ++;
        }else if(btn ==='prev'){
            index --;
        }
        pages[index].classList.add('active')
        console.log(index)
    }

    let uploadImg = document.getElementById("upload_image");
    let inputFile = document.getElementById("input-file");
    inputFile.onchange = function(){
        uploadImg.src = URL.createObjectURL(inputFile.files[0]);
    }

        
    });
</script>



</body>

</html>