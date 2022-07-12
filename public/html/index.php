<?php
// most files will include the initialize.php file.  This file turns on sessions, and is where we bring in all the libraries we use globaly
include('../../private/initialize.php');
session_destroy();
// kill the session if there is one.  The rest of the page is just an attractive login screen.  It also brings in Google invisible reCAPTCHA v2
// to check if the person is a bot.  If they are they are presented with a picture challenge.  When they pass the challenge a session variable is set
// and we check for that in the next page.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GLAC Online Card Registration</title>
    <script>
        function onSubmit(token) {
            document.getElementById("loginpage").submit();
        }
    </script>
    <link href="assets/multistepform/css/style.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]-->
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>
<style>

    body {
        background-image: url('images/background.png');
    }


</style>
<body>

<center><img src="images/glac.png" width="15%" style="padding-top: 25px;padding-bottom: 5px;" alt="ecard Title"/> </center>
<br>
<div class="col-md-6 col-md-offset-3">

    <div class="row">
        <div class="col-xs-1"></div>
        <div class="col-xs-10 transbox" style="color: black"><H3>Don't have a Glendale Library card yet? Sign up for an e-card today and get instant access to all of our electronic resources and eBooks. Please see our <a href="https://www.eglendalelac.org/faqs">FAQ</a> for more information.</H3><br>
            <h4>Please note - if you already have a Glendale Library card you don't need to sign up for an e-card.  Your Glendale Library card already has access to all of our <a href="https://www.eglendalelac.org/digital-library">amazing electronic resources.</a></h4>

            <form action="form.php" id="loginpage" method="post">
                <?php if(useRecaptcha == '1') { ?>
                    <center><button class="g-recaptcha button" data-sitekey="<?php echo recaptchaSiteKey; ?>" data-callback='onSubmit'>Get Started Now</button></center>
                <?php } ?>
                <?php if (useRecaptcha == '0') { ?>
                    <center><button class="button">Get Started Now</button></center>
                <?php } ?>


            </form></div>
        <div class="col-xs-1"></div>
    </div>
</div>

</body>

</html>