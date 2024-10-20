<?php include 'header.php'; ?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Finder - RecorderInfo</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/info.css">
    <!-- <script src="../js/lost_item_form.js" defer></script> -->


</head>

<body>
    <div class="container">
        <section class="question">
            <h1>What Item are you recording?</h1>
        </section>
        <div class="item_form_container">
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form id = "infoForm" action="recorder_info.php" method="post">
                <div class="page page-1 index active">
                    <div class="form_group">
                            <input type="text" name="Description" placeholder="General Description of the item?" required>
                    </div>
                    <div class="form_group">
                        <input type="text" name="Brand" placeholder="Brand?" required>
                    </div>
                    <div class="form_group">
                        <input type="text" name="Color" placeholder="Color?" required>
                    </div>
                    
                    <button type="button" class="next-btn">Continue</button> 

                </div>

                <div class="page page-2">
                    
                    <div class="form_group">
                            <input type="text" name="Date" placeholder="Date?" required>
                    </div>
                    <div class="form_group">
                        <input type="text" name="Time" placeholder="Time?" required>
                    </div>
            
                    <button type="button" class="prev-btn">Go Back</button> 
                    <button type="button" class="next-btn">Continue</button> 

                </div>

                <div class="page page-3 ">
                    
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

        
    });
</script>



</body>

</html>