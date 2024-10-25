<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Finder - Contact</title>
    <link rel="stylesheet" href="../contact.css">
   
    <link href="../fontawesome-free-6.6.0-web/css/all.css" rel="stylesheet" />

    
</head>

<body>

    <div class = "contact">
        <div class = "content">
            <h2> Contact US</h2>
        </div>
        <div class = "contact_container">
            <div class = "contactInfo">
                <div class = "box">
                    <div class = "icon"><i class="fa-solid fa-location-pin"></i> </div>
                    <div class = "text">
                        <h3>Address</h3>
                        <p> 15th Street & Sage Avenue,<br>
                            Troy, New York<br> 12180, US</p>
                    </div>
                </div>

                <div class = "box">
                    <div class = "icon"> <i class="fa-solid fa-phone"></i></div>
                    <div class = "text">
                        <h3>Phone</h3>
                        <p> 123 - 123 - 1234 </p>
                    </div>
                </div>
                

                <div class = "box">
                    <div class = "icon"> <i class="fa-solid fa-envelope"></i></div>
                    <div class = "text">
                        <h3>Email</h3>
                        <p> Something@rpi.edu</p>
                    </div>
                </div>
                    
            </div>
            
            <div class = "contactForm">
                <form action="https://api.web3forms.com/submit" method="POST">
                    <h2> Send Message</h2>
                    <input type="hidden" name="access_key" value="d282ad35-0e50-45be-8cdc-a08565f915ce">
                    <div class = "inputBox">
                        <input type = "text" name = "full_name" required = "required">
                        <span> Full Name</span>
                    </div>

                    <div class = "inputBox">
                        <input type = "text" name = "email" required = "required">
                        <span>Email</span>
                    </div>

                    <div class = "inputBox">
                        <textarea type = "text" name = "message" required = "required"> </textarea>
                        <span>Type your message...</span>

                    </div>

                    <div class = "inputBox">
                        <input type = "submit" name = "" required = "Send">
                    </div>
                        
                    
                </form>


            </div>

               
            
        </div>
    </div>
        
    
    

    

    <script src="script.js"></script>
     


</body>

</html>