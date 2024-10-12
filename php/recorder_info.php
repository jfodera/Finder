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
                <input type="text" name="Description" placeholder="General Description of the item?" required>
                <input type="text" name="Brand" placeholder="Brand?" required>
                <input type="text" name="Color" placeholder="Color?" required>
                <button type="submit" class="button button-account">Continue</button>
            </form>

    
    </div>

   
        </div>
    </div>


</body>

</html>