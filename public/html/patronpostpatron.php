
<?php
 include('../../private/initialize.php');
 //include('../../private/PHPMailer.php');
 //include('../../private/Exception.php');
 // pull in the usual libraries, also pull in PHPMailer. You are free to re-write the code to use a different mail function
// we went with this one because it is easy to use, and does all the legwork.
//pre($_SESSION);
//Define name spaces
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\SMTP;
	use PHPMailer\PHPMailer\Exception;

// again check to ensure people aren't going right to this page
if(useRecaptcha == '1') {
    if (!isset($_SESSION['notabot'])) {
        header('Location: \index.php');
    }
}

// check if it's a post request, this page should only ever get post requests, if not kick them back to the start

// get all the POST details and do some formatting.  We like all uppercase in our patron records for instance.  This helps
// keep patron records consistent.

 if (!is_post_request()) {
header('Location: \index.php');
 }

 // this checks to see that they came from the verifypatron.php page. If not kick them back to the index page.
 if($_SESSION['about_to_post'] != 'TRUE') {
     header('Location: \index.php');
 }

 if($_SESSION['about_to_post'] == 'TRUE') {
     if (is_post_request()) {

        $_SESSION['about_to_post'] = 'NULL';
        if(isset($_POST['email']))
          {
              $post_email = $_POST['email'];
              $post_name = strtoupper($_POST['lname']) . ', ' . strtoupper($_POST['fname']);
              $post_street = strtoupper($_POST['street']);
              $post_cityprovince = strtoupper($_POST['city']) . ', ' . strtoupper($_POST['province']);
              $post_postalcode = strtoupper($_POST['postalcode']);
              $post_phone = $_POST['phone'];
              $post_bithdate = $_POST['date_of_birth_field'];
              $post_city = strtoupper($_POST['city']);
              $post_province = strtoupper($_POST['province']);
              $post_notice = $_POST['notice_preference'];
              $post_marketing = $_POST['marketing_preference'];
              $post_homelibrary = $_POST['homelibrary'];
              //$post_patronagency = $_POST['patronagency'];

                // write the data to a session varibale, we might have to kick them back to the verify page and this will auto fill the data back in for them.
              $_SESSION['email'] = $_POST['email'];
              $_SESSION['first_name'] = strtoupper($_POST['fname']);
              $_SESSION['last_name'] = strtoupper($_POST['lname']);
              $_SESSION['name'] = strtoupper($_SESSION['last_name']) . ', ' . strtoupper($_SESSION['first_name']);
              $_SESSION['street'] = strtoupper($_POST['street']);
              $_SESSION['city'] = strtoupper($_POST['city']);
              $_SESSION['postalcode'] = strtoupper($_POST['postalcode']);
              $_SESSION['province'] = strtoupper($_POST['province']);
              $_SESSION['phonenumber'] = strtoupper($_POST['phone']);
              $_SESSION['date_of_birth'] = $_POST['date_of_birth_field'];
              $_SESSION['notice_preference'] = $_POST['notice_preference'];
              $_SESSION['marketing_preference'] = $_POST['marketing_preference'];
              $_SESSION['homelibrary'] = $_POST['homelibrary'];
              //$_SESSION['patronagency'] = $_POST['patronagency'];
          }

      else {
        $post_email = $_SESSION['email'];
        $post_name = strtoupper($_SESSION['last_name']) . ', ' . strtoupper($_SESSION['first_name']);
        $post_street = strtoupper($_SESSION['street']);
        $post_cityprovince = strtoupper($_SESSION['city']) . ', ' . $_SESSION['province'];
        $post_postalcode = strtoupper($_SESSION['postalcode']);
        $post_phone = $_SESSION['phonenumber'];
        $post_bithdate = $_SESSION['date_of_birth'];
        $post_notice = $_SESSION['notice_preference'];
        $post_marketing = $_SESSION['marketing_preference'];
        $post_patrontype = $_SESSION['patron_type'];
        $post_city = strtoupper($_SESSION['city']);
        $post_homelibrary = $_POST['homelibrary'];
        //$post_patronagency = $_POST['patronagency'];
        //$post_city =
      }
        // create an array of all the POST info that we will pass to a function that will add the patron to the ILS
       $newPatronInfo = array(
       'email'=>$post_email,
       'name'=>$post_name,
       'addressStreet'=>$post_street,
       '$citycommaProvince'=>$post_cityprovince,
       'postalCode'=>$post_postalcode,
       'addressType'=>'a',
       'phonenumber'=>$post_phone,
       'numberType'=>'t',
       'birthdate'=>$post_bithdate,
       'homelibrary'=>$post_homelibrary);
       //'patronagency'=>'2'

        // get their address into its own array - we will pass this to a function to do address verificaton.
       $myAddress = [
         'country' => 'US',
         'city' => $post_city,
         'postalCode' => $post_postalcode,
         'street' => $post_street];

       // leftover testing code.
    //echo $post_email;
    //pre($_SESSION);
    //pre($_POST);
    //pre($newPatronInfo);
    //exit();
    //pre($_SESSION);

       if(verifyAddress == '1') {
         $addressCheck  = isAddressValid($myAddress);
         if(!$addressCheck) {
            $_SESSION['addressissue'] = TRUE;
            // there was an issue with the address. Postal code did not match the provided address.  Redirect back and ask patron to correct
             header('Location: verifynewpatron.php');
             die();
         }
       }

         if(verifyCatchment == '1') {
             // Patron address has been verified as an actual address.  check to see that they fall inside the catchment area.  IF they don't redirect to a page explaining card policy.
             if (!isPatronInsideCatchmentArea($myAddress)) {
                 header('Location: ' . catchmentFailedRedirectPage); // set this to a url of a page that explains your policies on who can get a card.
                 die();
             }
         }

        // All checks have been passed so go ahead and create the patron.  Part of the process of creating the patron it to create
         // a random 6 digit pin.  The ILS has some (in my opinion stupid) checks to ensure the pin is non-trivial, so 1111 is not valid.
         // the function makes a random pin with non-repeating numbers.

       $myNewPatron = createOnlinePatron($newPatronInfo);
      // get the pin from the patron record to display to the user
       $patronPIN = $myNewPatron['pin'];
       // get the patronID from the record so we can get all the details.
       $justpatronID = linkStripped($myNewPatron['patronIDString']);
       //echo 'patron id string that was created is: ' . $justpatronID;
       lb();
       $allPatronDetails = getAllPatronDetails($justpatronID);
       // legacy code

       // update the patron type to Online
       $updatePatronType = updatePatronType($justpatronID, patronTypeNumber);
       // update how they want notifications
       $updateNoticePreference = updateNoticePreference($justpatronID, $post_notice);
       // update their marketing preferences, we just use a patron note for this.
       if($post_marketing == 'y') {
         $updateMarketingPreference = updatePatronNotes($justpatronID, 'MARKETING_PREFERENCE = TRUE');
         }
       $todaysDate = date('m/d/Y');
       // add a patron note saying that the record was created using the online tool.
       $addPatronCreateDate = updatePatronNotes($justpatronID, 'Created via Online Registration on ' . $todaysDate);

     }
 }
 //pre($allPatronDetails);
    // Code to create the library card image.  It takes a static image (the background of the library card) and superimposes a barcode ontop of it.


// the code below shows the image to the user on the screen and also emails them a copy of the card and our welcome email.
   createLibraryCardImage($allPatronDetails['barcodes']['0']);
   $libraryCardFile = $allPatronDetails['barcodes']['0'] . ".png";
  ?>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <!--<link rel="icon" href="../../favicon.ico">-->
    <title>GLAC Online Card Registration</title>
    <!-- Bootstrap core CSS -->
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <!--<link href="../../assets/css/ie10-viewport-bug-workaround.css" rel="stylesheet">-->
    <!-- Custom styles -->


    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-formhelpers/2.3.0/css/bootstrap-formhelpers.min.css" rel="stylesheet">
    <link href="assets/multistepform/css/style.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]-->
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo captcha_site_key; ?>"></script>
</head>

<body background="images/background.png">

 <center>
     <div class="imgbox">
         <H1>Here is your new e-Library Card!</H1>
          <img src="<?php echo $libraryCardFile; ?>" class="center-fit" alt="Library Card Image" img style="border:1px solid black">
          <h3>Your PIN is: <?php echo $patronPIN; ?></h3>
         <br/>
         <h3>Check your email for a copy of your e-card</h3>
     </div>
 </center>
</body>
</html>
 <?php
// CODE TO EMAIL PATRON A COPY OF THEIR CARD - NEED TO CHANGE THIS SO I ATTACH THEIR LIBRARY CARD AND THEN DISPLAY IT  - DONE IN HEADERS CID

//GLAC - email code is near the bottom of this file line 856

 $bodytext = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
<!--[if gte mso 9]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
<meta content="width=device-width" name="viewport"/>
<!--[if !mso]><!-->
<meta content="IE=edge" http-equiv="X-UA-Compatible"/>
<!--<![endif]-->
<title></title>
<!--[if !mso]><!-->
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css"/>
<!--<![endif]-->
<style type="text/css">
		body {
			margin: 0;
			padding: 0;
		}

		table,
		td,
		tr {
			vertical-align: top;
			border-collapse: collapse;
		}

		* {
			line-height: inherit;
		}

		a[x-apple-data-detectors=true] {
			color: inherit !important;
			text-decoration: none !important;
		}
	</style>
<style id="media-query" type="text/css">
		@media (max-width: 660px) {

			.block-grid,
			.col {
				min-width: 320px !important;
				max-width: 100% !important;
				display: block !important;
			}

			.block-grid {
				width: 100% !important;
			}

			.col {
				width: 100% !important;
			}

			.col>div {
				margin: 0 auto;
			}

			img.fullwidth,
			img.fullwidthOnMobile {
				max-width: 100% !important;
			}

			.no-stack .col {
				min-width: 0 !important;
				display: table-cell !important;
			}

			.no-stack.two-up .col {
				width: 50% !important;
			}

			.no-stack .col.num4 {
				width: 33% !important;
			}

			.no-stack .col.num8 {
				width: 66% !important;
			}

			.no-stack .col.num4 {
				width: 33% !important;
			}

			.no-stack .col.num3 {
				width: 25% !important;
			}

			.no-stack .col.num6 {
				width: 50% !important;
			}

			.no-stack .col.num9 {
				width: 75% !important;
			}

			.video-block {
				max-width: none !important;
			}

			.mobile_hide {
				min-height: 0px;
				max-height: 0px;
				max-width: 0px;
				display: none;
				overflow: hidden;
				font-size: 0px;
			}

			.desktop_hide {
				display: block !important;
				max-height: none !important;
			}
		}
	</style>
</head>
<body class="clean-body" style="margin: 0; padding: 0; -webkit-text-size-adjust: 100%; background-color: #aed0c7;"> <!-- was  #dbb727 -->
<!--[if IE]><div class="ie-browser"><![endif]-->
<table bgcolor="#aed0c7" cellpadding="0" cellspacing="0" class="nl-container" role="presentation" style="table-layout: fixed; vertical-align: top; min-width: 320px; Margin: 0 auto; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #aed0c7; width: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td style="word-break: break-word; vertical-align: top;" valign="top">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color:#aed0c7"><![endif]-->
<div style="background-image:url(\'cid:Background_1_4.png\');background-position:top center;background-repeat:no-repeat;background-color:#e9e4d4;">
<div class="block-grid no-stack" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-image:url(\'cid:Background_1_4.png\');background-position:top center;background-repeat:no-repeat;background-color:#e9e4d4;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="640" style="background-color:transparent;width:640px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:0px; padding-bottom:0px;"><![endif]-->
<div class="col num12" style="min-width: 320px; max-width: 640px; display: table-cell; vertical-align: top; width: 640px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:0px; padding-bottom:0px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top: 25px; padding-bottom: 25px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#555555;font-family:\'Oswald\', Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:25px;padding-right:0px;padding-bottom:25px;padding-left:0px;">
<div style="font-family: \'Oswald\', Arial, \'Helvetica Neue\', Helvetica, sans-serif; line-height: 1.2; font-size: 12px; color: #555555; mso-line-height-alt: 14px;">
<p style="line-height: 1.2; text-align: center; font-size: 22px; mso-line-height-alt: 26px; margin: 0;"><span style="font-size: 22px; color: #000000;"><span style="font-size: 22px;"><span style="font-size: 22px;">Woohoo! Congratulations!</span></span></span></p>
<p style="line-height: 1.2; text-align: center; font-size: 22px; mso-line-height-alt: 26px; margin: 0;"><span style="font-size: 22px; color: #000000;"><span style="font-size: 22px;"><span style="font-size: 22px;">Below is your new</span></span></span></p>
<p style="line-height: 1.2; text-align: center; font-size: 22px; mso-line-height-alt: 26px; margin: 0;"><span style="color: #006368; font-size: 22px;"><span style="font-size: 22px;">Milton Public Library e-card</span><span style="font-size: 22px;"></span></span></p>

</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<div align="center" class="img-container center fixedwidth" style="padding-right: 20px;padding-left: 20px;">
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr style="line-height:0px"><td style="padding-right: 20px;padding-left: 20px;" align="center"><![endif]-->
<div style="font-size:1px;line-height:20px"> </div><img align="center" alt="Image" border="0" class="center fixedwidth" src="cid:patroncard.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; height: auto; width: 100%; max-width: 350px; display: block;" title="Image"/>
<p></p>

<div style="font-size:1px;line-height:20px"> </div>
<!--[if mso]></td></tr></table><![endif]-->
</div>
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top: 15px; padding-bottom: 0px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#555555;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:15px;padding-right:0px;padding-bottom:0px;padding-left:0px;">
<div style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 12px; line-height: 1.2; color: #555555; mso-line-height-alt: 14px;">
<p style="font-size: 22px; line-height: 1.2; text-align: center; mso-line-height-alt: 26px; margin: 0;"><span style="font-size: 22px; color: #000000;"><span style="font-size: 22px;">Your PIN is: ' . $patronPIN;

 $bodytext .= '</span></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-image:url(\'images/Border_1.png\');background-position:top left;background-repeat:no-repeat;background-color:transparent;">
<div class="block-grid" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-image:url(\'images/Border_1.png\');background-position:top left;background-repeat:no-repeat;background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="640" style="background-color:transparent;width:640px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:30px; padding-bottom:25px;"><![endif]-->
<div class="col num12" style="min-width: 320px; max-width: 640px; display: table-cell; vertical-align: top; width: 640px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:30px; padding-bottom:25px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<table border="0" cellpadding="0" cellspacing="0" class="divider" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td class="divider_inner" style="word-break: break-word; vertical-align: top; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; padding-top: 20px; padding-right: 20px; padding-bottom: 20px; padding-left: 20px;" valign="top">
<table align="center" border="0" cellpadding="0" cellspacing="0" class="divider_content" height="0" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-top: 0px solid #BBBBBB; height: 0px; width: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td height="0" style="word-break: break-word; vertical-align: top; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top"><span></span></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-color:transparent;">
<div class="block-grid" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="640" style="background-color:transparent;width:640px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num12" style="min-width: 320px; max-width: 640px; display: table-cell; vertical-align: top; width: 640px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#555555;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
<div style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; line-height: 1.2; font-size: 12px; color: #555555; mso-line-height-alt: 14px;">
<p style="line-height: 1.2; text-align: center; font-size: 12px; mso-line-height-alt: 14px; margin: 0;"><span style="color: #000000; font-size: 12px;"><span style="font-size: 22px;">Your new e-card allows you to access all of our e-resources:</span></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-image:url(\'images/Border.png\');background-position:top center;background-repeat:no-repeat;background-color:transparent;">
<div class="block-grid mixed-two-up" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-image:url(\'images/Border.png\');background-position:top center;background-repeat:no-repeat;background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="213" style="background-color:transparent;width:213px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num4" style="display: table-cell; vertical-align: top; max-width: 320px; min-width: 212px; width: 213px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 10px; padding-top: 0px; padding-bottom: 0px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#000000;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:10px;">
<div style="line-height: 1.2; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 12px; color: #000000; mso-line-height-alt: 14px;">
<p style="line-height: 1.2; font-size: 38px; text-align: center; mso-line-height-alt: 46px; margin: 0;"><span style="font-size: 38px;"><strong>Read</strong></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#9D8F69;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
<div style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 12px; line-height: 1.2; color: #9D8F69; mso-line-height-alt: 14px;">
<p dir="ltr" style="font-size: 16px; line-height: 1.2; text-align: center; mso-line-height-alt: 19px; margin: 0;"><span style="font-size: 16px;"><a href="https://ebook.yourcloudlibrary.com/library/milton/Featured" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">cloudLibrary E-Books</a></span><br/><span style="font-size: 16px;"><a href="http://www.biblioenfants.com/auto_login.aspx?U=miltonpl&amp;P=libra&amp;lang=fr" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">BiblioEnfants</a></span><br/><span style="font-size: 16px;"><a href="http://search.ebscohost.com/login.aspx?authtype=ip,cookie,cpid&amp;custid=miltonpl&amp;profile=eon" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Flipster</a></span><br/><span style="font-size: 16px;"><a href="http://www.hoopladigital.com/" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Hoopla Digital</a></span><br/><span style="font-size: 16px;"><a href="http://search.ebscohost.com/login.aspx?authtype=ip,cookie,cpid&amp;custid=miltonpl&amp;profile=novplus" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">NoveList Plus</a></span><br/><span style="font-size: 16px;"><a href="http://search.ebscohost.com/login.aspx?authtype=ip,cookie,cpid&amp;custid=miltonpl&amp;profile=novpk8" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">NoveList K-8 Plus</a></span><br/><span style="font-size: 16px;"><a href="http://www.tumblebooklibrary.com/autologin.aspx?userid=gliubjOtZMoNSnxRkX8o3w%3d%3d" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">TumbleBook Library</a></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td><td align="center" width="426" style="background-color:transparent;width:426px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num8" style="display: table-cell; vertical-align: top; min-width: 320px; max-width: 424px; width: 426px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<div align="center" class="img-container center fixedwidth" style="padding-right: 0px;padding-left: 0px;">
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr style="line-height:0px"><td style="padding-right: 0px;padding-left: 0px;" align="center"><![endif]--><img align="center" alt="Phone with cloudLibrary app open" border="0" class="center fixedwidth" src="cid:iphone.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; height: auto; width: 100%; max-width: 149px; display: block;" title="Phone with cloudLibrary app open" width="149"/>
<!--[if mso]></td></tr></table><![endif]-->
</div>
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-color:transparent;">
<div class="block-grid" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="640" style="background-color:transparent;width:640px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num12" style="min-width: 320px; max-width: 640px; display: table-cell; vertical-align: top; width: 640px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<table border="0" cellpadding="0" cellspacing="0" class="divider" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td class="divider_inner" style="word-break: break-word; vertical-align: top; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; padding-top: 0px; padding-right: 0px; padding-bottom: 0px; padding-left: 0px;" valign="top">
<table align="center" border="0" cellpadding="0" cellspacing="0" class="divider_content" height="0" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-top: 1px solid #000000; height: 0px; width: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td height="0" style="word-break: break-word; vertical-align: top; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top"><span></span></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-image:url(\'images/Border.png\');background-position:top center;background-repeat:no-repeat;background-color:transparent;">
<div class="block-grid mixed-two-up" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-image:url(\'images/Border.png\');background-position:top center;background-repeat:no-repeat;background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="213" style="background-color:transparent;width:213px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num4" style="display: table-cell; vertical-align: top; max-width: 320px; min-width: 212px; width: 213px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<div class="mobile_hide">
<div align="center" class="img-container center autowidth fullwidth" style="padding-right: 0px;padding-left: 0px;">
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr style="line-height:0px"><td style="padding-right: 0px;padding-left: 0px;" align="center"><![endif]--><img align="center" alt="Image" border="0" class="center autowidth fullwidth" src="cid:Bee.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; height: auto; width: 100%; max-width: 213px; display: block;" title="Image" width="213"/>
<!--[if mso]></td></tr></table><![endif]-->
</div>
</div>
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td><td align="center" width="426" style="background-color:transparent;width:426px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num8" style="display: table-cell; vertical-align: top; min-width: 320px; max-width: 424px; width: 426px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 10px; padding-top: 0px; padding-bottom: 0px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#000000;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:10px;">
<div style="line-height: 1.2; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 12px; color: #000000; mso-line-height-alt: 14px;">
<p style="line-height: 1.2; font-size: 38px; text-align: center; mso-line-height-alt: 46px; margin: 0;"><span style="font-size: 38px;"><strong>Learn</strong></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#9D8F69;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
<div style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 12px; line-height: 1.2; color: #9D8F69; mso-line-height-alt: 14px;">
<p dir="ltr" style="font-size: 16px; line-height: 1.2; text-align: center; mso-line-height-alt: 19px; margin: 0;"><span style="font-size: 16px;"><a href="http://find.galegroup.com/menu/start?userGroupName=ko_pl_mpl&amp;prod=AONE" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank"> Academic Onefile</a></span><br/><span style="font-size: 16px;"><a href="https://avod.infobase.com/PortalPlayLists.aspx?wid=237219" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Access Video on Demand</a></span><br/><span style="font-size: 16px;"><a href="http://main.miltonh.ca.brainfuse.com/authenticate.asp" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Brianfuse HelpNow</a></span><br/><span style="font-size: 16px;"><a href="http://education.gale.com/l-ko_pl_mpl/" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Gale Courses</a></span><br/><span style="font-size: 16px;"><a href="http://miltonpl.g1.ca/" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">G1 Practice Tests</a></span><br/><span style="font-size: 16px;"><a href="http://search.ebscohost.com/login.aspx?authtype=ip,cpid&amp;custid=miltonpl&amp;profile=8gh" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">GreenFILE</a></span><br/><span style="font-size: 16px;"><a href="http://search.ebscohost.com/login.aspx?authtype=ip,cookie,cpid&amp;custid=miltonpl&amp;profile=lrc" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Literary Reference Center</a></span><br/><span style="font-size: 16px;"><a href="https://www.pebblego.com/UserLogin.aspx?sqs=CGuh6LKmanbvcaMcXvwJ4w==" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">PebbleGo Animals &amp; Science &amp; Dinosaurs</a></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-color:transparent;">
<div class="block-grid" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="640" style="background-color:transparent;width:640px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num12" style="min-width: 320px; max-width: 640px; display: table-cell; vertical-align: top; width: 640px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<table border="0" cellpadding="0" cellspacing="0" class="divider" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td class="divider_inner" style="word-break: break-word; vertical-align: top; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; padding-top: 0px; padding-right: 0px; padding-bottom: 0px; padding-left: 0px;" valign="top">
<table align="center" border="0" cellpadding="0" cellspacing="0" class="divider_content" height="0" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-top: 1px solid #000000; height: 0px; width: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td height="0" style="word-break: break-word; vertical-align: top; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top"><span></span></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-image:url(\'images/Border.png\');background-position:top center;background-repeat:no-repeat;background-color:transparent;">
<div class="block-grid mixed-two-up" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-image:url(\'images/Border.png\');background-position:top center;background-repeat:no-repeat;background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="213" style="background-color:transparent;width:213px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num4" style="display: table-cell; vertical-align: top; max-width: 320px; min-width: 212px; width: 213px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 10px; padding-top: 0px; padding-bottom: 0px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#000000;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:10px;">
<div style="line-height: 1.2; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 12px; color: #000000; mso-line-height-alt: 14px;">
<p style="line-height: 1.2; text-align: center; font-size: 38px; mso-line-height-alt: 46px; margin: 0;"><span style="font-size: 38px;"><strong>Create</strong></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#9D8F69;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
<div style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; line-height: 1.2; font-size: 12px; color: #9D8F69; mso-line-height-alt: 14px;">
<p dir="ltr" style="line-height: 1.2; text-align: center; font-size: 16px; mso-line-height-alt: 19px; margin: 0;"><span style="font-size: 16px;"><a href="https://www.atozworldfood.com/?c=sdTMUmP3XS" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">A to Z World Food</a></span></p>
<p dir="ltr" style="line-height: 1.2; text-align: center; font-size: 12px; mso-line-height-alt: 14px; margin: 0;"><br/><span style="font-size: 16px;"><a href="http://search.ebscohost.com/login.aspx?authtype=ip,cpid&amp;custid=miltonpl&amp;profile=hcrc" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Hobbies &amp; Crafts Reference Center</a></span></p>
<p dir="ltr" style="line-height: 1.2; text-align: center; font-size: 12px; mso-line-height-alt: 14px; margin: 0;"><br/><span style="font-size: 16px;"><a href="https://www.worldbookonline.com/ewol/home?ed=lib&amp;subacct=CD23720" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Early World of Learning</a></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td><td align="center" width="426" style="background-color:transparent;width:426px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num8" style="display: table-cell; vertical-align: top; min-width: 320px; max-width: 424px; width: 426px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<div align="center" class="img-container center fixedwidth" style="padding-right: 0px;padding-left: 0px;">
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr style="line-height:0px"><td style="padding-right: 0px;padding-left: 0px;" align="center"><![endif]--><img align="center" alt="I\'m an image" border="0" class="center fixedwidth" src="cid:Bee_2.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; height: auto; width: 100%; max-width: 213px; display: block;" title="I\'m an image" width="213"/>
<!--[if mso]></td></tr></table><![endif]-->
</div>
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-color:transparent;">
<div class="block-grid" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="640" style="background-color:transparent;width:640px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num12" style="min-width: 320px; max-width: 640px; display: table-cell; vertical-align: top; width: 640px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<table border="0" cellpadding="0" cellspacing="0" class="divider" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td class="divider_inner" style="word-break: break-word; vertical-align: top; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; padding-top: 0px; padding-right: 0px; padding-bottom: 0px; padding-left: 0px;" valign="top">
<table align="center" border="0" cellpadding="0" cellspacing="0" class="divider_content" height="0" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-top: 1px solid #000000; height: 0px; width: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td height="0" style="word-break: break-word; vertical-align: top; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top"><span></span></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-image:url(\'images/Border.png\');background-position:top center;background-repeat:no-repeat;background-color:transparent;">
<div class="block-grid mixed-two-up" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-image:url(\'images/Border.png\');background-position:top center;background-repeat:no-repeat;background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="213" style="background-color:transparent;width:213px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num4" style="display: table-cell; vertical-align: top; max-width: 320px; min-width: 212px; width: 213px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<div align="center" class="img-container center fixedwidth" style="padding-right: 0px;padding-left: 40px;">
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr style="line-height:0px"><td style="padding-right: 0px;padding-left: 40px;" align="center"><![endif]--><img align="center" alt="Mac PC with Gale Courses open" border="0" class="center fixedwidth" src="cid:gale_comp.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; height: auto; width: 100%; max-width: 213px; display: block;" title="Mac PC with Gale Courses open" width="213"/>
<!--[if mso]></td></tr></table><![endif]-->
</div>
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td><td align="center" width="426" style="background-color:transparent;width:426px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num8" style="display: table-cell; vertical-align: top; min-width: 320px; max-width: 424px; width: 426px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 10px; padding-top: 0px; padding-bottom: 0px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#000000;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:10px;">
<div style="line-height: 1.2; font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 12px; color: #000000; mso-line-height-alt: 14px;">
<p style="line-height: 1.2; font-size: 38px; text-align: center; mso-line-height-alt: 46px; margin: 0;"><span style="font-size: 38px;"><strong>Connect</strong></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#9D8F69;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
<div style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 12px; line-height: 1.2; color: #9D8F69; mso-line-height-alt: 14px;">
<p dir="ltr" style="font-size: 16px; line-height: 1.2; text-align: center; mso-line-height-alt: 19px; margin: 0;"><span style="font-size: 16px;"><a href="http://library.eb.com/storelibrarycard?id=miltonpublib" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Britannica Online</a></span><br/><span style="font-size: 16px;"><a href="http://search.ebscohost.com/login.aspx?authtype=ip,cookie,cpid&amp;custid=miltonpl&amp;profile=refcentca" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Canadian Reference Centre</a></span><br/><span style="font-size: 16px;"><a href="http://eco.canadiana.ca/?usrlang=en" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Early Canadiana Online</a></span><br/><span style="font-size: 16px;"><a href="https://jfk.infobase.com/PortalPlayLists.aspx?wid=237219" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Just for Kids Access Video</a></span><br/><span style="font-size: 16px;"><a href="http://mpl.naxosmusiclibrary.com/" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Naxos Music Library</a></span><br/><span style="font-size: 16px;"><a href="https://library.transparent.com/miltonon/" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Transparent Languages</a></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-color:transparent;">
<div class="block-grid" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="640" style="background-color:transparent;width:640px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num12" style="min-width: 320px; max-width: 640px; display: table-cell; vertical-align: top; width: 640px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<table border="0" cellpadding="0" cellspacing="0" class="divider" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td class="divider_inner" style="word-break: break-word; vertical-align: top; min-width: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; padding-top: 0px; padding-right: 0px; padding-bottom: 0px; padding-left: 0px;" valign="top">
<table align="center" border="0" cellpadding="0" cellspacing="0" class="divider_content" height="0" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-top: 1px solid #000000; height: 0px; width: 100%;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td height="0" style="word-break: break-word; vertical-align: top; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;" valign="top"><span></span></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<div style="background-color:transparent;">
<div class="block-grid mixed-two-up" style="Margin: 0 auto; min-width: 320px; max-width: 640px; overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; background-color: transparent;">
<div style="border-collapse: collapse;display: table;width: 100%;background-color:transparent;">
<!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:transparent;"><tr><td align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:640px"><tr class="layout-full-width" style="background-color:transparent"><![endif]-->
<!--[if (mso)|(IE)]><td align="center" width="426" style="background-color:transparent;width:426px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num8" style="display: table-cell; vertical-align: top; min-width: 320px; max-width: 424px; width: 426px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 10px; padding-left: 10px; padding-top: 10px; padding-bottom: 10px; font-family: Arial, sans-serif"><![endif]-->
<div style="color:#555555;font-family:Arial, \'Helvetica Neue\', Helvetica, sans-serif;line-height:1.2;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
<div style="font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif; font-size: 12px; line-height: 1.2; color: #555555; mso-line-height-alt: 14px;">
<p style="font-size: 16px; line-height: 1.2; text-align: center; mso-line-height-alt: 19px; margin: 0;"><span style="color: #000000; font-size: 16px;">Please note: If you would like to borrow any physical materials, place holds or attend programmes, you will need to obtain a physical MPL card from any MPL location:</span><br/><span style="font-size: 16px;"><a href="https://www.beinspiredatmpl.ca/hours-and-locations/main-library" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Main Library</a></span><br/><span style="font-size: 16px;"><a href="https://www.beinspiredatmpl.ca/hours-and-locations/sherwood-branch" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Sherwood Branch</a></span><br/><span style="font-size: 16px;"><a href="https://www.beinspiredatmpl.ca/hours-and-locations/beaty-branch" rel="noopener" style="text-decoration: underline; color: #0068A5;" target="_blank">Beaty Branch</a></span></p>
</div>
</div>
<!--[if mso]></td></tr></table><![endif]-->
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td><td align="center" width="213" style="background-color:transparent;width:213px; border-top: 0px solid transparent; border-left: 0px solid transparent; border-bottom: 0px solid transparent; border-right: 0px solid transparent;" valign="top"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding-right: 0px; padding-left: 0px; padding-top:5px; padding-bottom:5px;"><![endif]-->
<div class="col num4" style="display: table-cell; vertical-align: top; max-width: 320px; min-width: 212px; width: 213px;">
<div style="width:100% !important;">
<!--[if (!mso)&(!IE)]><!-->
<div style="border-top:0px solid transparent; border-left:0px solid transparent; border-bottom:0px solid transparent; border-right:0px solid transparent; padding-top:5px; padding-bottom:5px; padding-right: 0px; padding-left: 0px;">
<!--<![endif]-->
<div align="center" class="img-container center autowidth fullwidth" style="padding-right: 20px;padding-left: 20px;">
<!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr style="line-height:0px"><td style="padding-right: 20px;padding-left: 20px;" align="center"><![endif]-->
<div style="font-size:1px;line-height:20px"> </div><img align="center" alt="Image" border="0" class="center autowidth fullwidth" src="cid:mpl_logo.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; border: 0; height: auto; width: 100%; max-width: 173px; display: block;" title="Image" width="173"/>
<!--[if mso]></td></tr></table><![endif]-->
</div>
<table cellpadding="0" cellspacing="0" class="social_icons" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;" valign="top" width="100%">
<tbody>
<tr style="vertical-align: top;" valign="top">
<td style="word-break: break-word; vertical-align: top; padding-top: 10px; padding-right: 10px; padding-bottom: 10px; padding-left: 10px;" valign="top">
<table activate="activate" align="center" alignment="alignment" cellpadding="0" cellspacing="0" class="social_table" role="presentation" style="table-layout: fixed; vertical-align: top; border-spacing: 0; border-collapse: undefined; mso-table-tspace: 0; mso-table-rspace: 0; mso-table-bspace: 0; mso-table-lspace: 0;" to="to" valign="top">
<tbody>
<tr align="center" style="vertical-align: top; display: inline-block; text-align: center;" valign="top">
<td style="word-break: break-word; vertical-align: top; padding-bottom: 5px; padding-right: 10px; padding-left: 10px;" valign="top"><a href="https://www.facebook.com/MiltonPublicLibrary/" target="_blank"><img alt="Facebook" height="32" src="cid:facebook.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: none; display: block;" title="Facebook" width="32"/></a></td>
<td style="word-break: break-word; vertical-align: top; padding-bottom: 5px; padding-right: 10px; padding-left: 10px;" valign="top"><a href="https://www.instagram.com/miltonpubliclibrary/" target="_blank"><img alt="Instagram" height="32" src="cid:insta.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: none; display: block;" title="Instagram" width="32"/></a></td>
<td style="word-break: break-word; vertical-align: top; padding-bottom: 5px; padding-right: 10px; padding-left: 10px;" valign="top"><a href="https://twitter.com/Milton_Library" target="_blank"><img alt="Twitter" height="32" src="cid:twitter.png" style="text-decoration: none; -ms-interpolation-mode: bicubic; height: auto; border: none; display: block;" title="Twitter" width="32"/></a></td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
<!--[if (!mso)&(!IE)]><!-->
</div>
<!--<![endif]-->
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
<!--[if (mso)|(IE)]></td></tr></table></td></tr></table><![endif]-->
</div>
</div>
</div>
<!--[if (mso)|(IE)]></td></tr></table><![endif]-->
</td>
</tr>
</tbody>
</table>
<!--[if (IE)]></div><![endif]-->
</body>
</html>';

//Include required PHPMailer files
	require 'includes/PHPMailer.php';
	require 'includes/SMTP.php';
	require 'includes/Exception.php';
//Create instance of PHPMailer
	$mail = new PHPMailer();
//Set mailer to use smtp
	$mail->isSMTP();
//Define smtp host
	$mail->Host = "az1-ss38.a2hosting.com"; //smtp.gmail.com if using gmail 
//Enable smtp authentication
	$mail->SMTPAuth = true;
//Set smtp encryption type (ssl/tls)
	$mail->SMTPSecure = "ssl";
//Port to connect smtp
	$mail->Port = 465; //465 25 587
//Set gmail username
	$mail->Username = "noreply@eglendalelac.org";

// USERNAME EMAIL AND SENDER EMAIL MUST BE THE SAME

//Set gmail password
	$mail->Password = "FOLDpaper101"; //FOLDpaper101
//Email subject
	$mail->Subject = "Your Glendale Library ecard";
//Set sender email
	$mail->setFrom("noreply@eglendalelac.org"); //noreply@eglendalelac.org
//Enable HTML
	$mail->isHTML(true);
//Attachment
    $file_to_attach = './' . $libraryCardFile;
	$mail->addAttachment( $file_to_attach , 'myecard.png' );
    $mail->AddEmbeddedImage( $file_to_attach , 'myecard.png');
//Email body
//divs for header, social media links, and footer 
//escaped all the " characters within the HTML with \ characters
	$mail->Body = "<div style=\"background-color:#ededed\"><div class=\"m_6602918289513662440block-grid\" style=\"Margin:0 auto;min-width:320px;max-width:500px;word-wrap:break-word;word-break:break-word;background-color:transparent\"><div style=\"border-collapse:collapse;display:table;width:100%;background-color:transparent\"><div class=\"m_6602918289513662440col\" style=\"min-width:320px;max-width:500px;display:table-cell;vertical-align:top;width:500px\"><div style=\"width:100%!important\"><div style=\"border-top:0px solid transparent;border-left:0px solid transparent;border-bottom:0px solid transparent;border-right:0px solid transparent;padding-top:10px;padding-bottom:10px;padding-right:10px;padding-left:10px\"><div class=\"m_6602918289513662440fullwidth\" align=\"center\"><img class=\"m_6602918289513662440fullwidth CToWUd a6T\" align=\"center\" border=\"0\" src=\"https://ci4.googleusercontent.com/proxy/hCdcBCGs8OvZILN-nX8rgnzHcTLGAotEe6pNAF6VznDxdAry8RSXdCvqZPUEeENVUe7FN7Ijb21OqnDpQzNSSfr5dzFw1Jpr88BfQ1-ZBLW1JakL6LqUhjnNV9FdzieEsT2C7MKriTRO1g0VABm_drJZriaEmmjv5moxtLY=s0-d-e1-ft#http://messages.librarycustomer.org/images/92eaf0a6-d709-46bf-b4af-07fc7faf8b4d/G8GJGI972020/GLAC_logo.png\" alt=\"Image\" title=\"Image\" style=\"text-decoration:none;height:auto;border:0;width:100%;max-width:240px;display:block\" width=\"240\" tabindex=\"0\"><div class=\"a6S\" dir=\"ltr\" style=\"opacity: 0.01; left: 346px; top: 92px;\"><div id=\":115\" class=\"T-I J-J5-Ji aQv T-I-ax7 L3 a5q\" role=\"button\" tabindex=\"0\" aria-label=\"Download attachment \" data-tooltip-class=\"a1V\" data-tooltip=\"Download\"><div class=\"aSK J-J5-Ji aYr\"></div></div></div></div></div></div></div></div></div></div>"
     . 
    "<h1>Congratulations!</h1><br><h2>Below is your new Glendale Public Library e-card</h2><br><h2><i>Your PIN is: " . $patronPIN 
     . 
    "</h2></i><br><img src=\"cid:myecard.png\" alt=\"myecard.png\" width=\"600\" height=\"392.39\" /><br>"
     . 
    "<div style=\"background-color:transparent\"><div class=\"m_6602918289513662440block-grid\" style=\"Margin:0 auto;min-width:320px;max-width:500px;word-wrap:break-word;word-break:break-word;background-color:transparent\"><div style=\"border-collapse:collapse;display:table;width:100%;background-color:transparent\"><div class=\"m_6602918289513662440col\" style=\"min-width:320px;max-width:500px;display:table-cell;vertical-align:top;width:500px\"><div style=\"width:100%!important\"><div style=\"border-top:0px solid transparent;border-left:0px solid transparent;border-bottom:0px solid transparent;border-right:0px solid transparent;padding-top:5px;padding-bottom:5px;padding-right:0px;padding-left:0px\"><div style=\"color:#555555;font-family:Arial,Helvetica Neue,Helvetica,sans-serif;line-height:1.2;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px\"><div style=\"line-height:1.2;font-size:12px;color:#555555;font-family:Arial,Helvetica Neue,Helvetica,sans-serif\"><p style=\"font-size:15px;line-height:1.2;word-break:break-word;text-align:center;margin:0\"><span style=\"font-size:15px\"><strong>Follow us. Like us. Tag Us.</strong></span></p><p style=\"font-size:14px;line-height:1.2;word-break:break-word;text-align:center;margin:0\">&nbsp;</p></div></div><table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" role=\"presentation\" style=\"table-layout:fixed;vertical-align:top;border-spacing:0;border-collapse:collapse\" valign=\"top\"><tbody><tr style=\"vertical-align:top\" valign=\"top\"><td style=\"word-break:break-word;vertical-align:top;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px\" valign=\"top\"><table align=\"center\" cellpadding=\"0\" cellspacing=\"0\" role=\"presentation\" style=\"table-layout:fixed;vertical-align:top;border-spacing:0;border-collapse:collapse\" valign=\"top\"><tbody><tr style=\"vertical-align:top;display:inline-block;text-align:center\" align=\"center\" valign=\"top\"><td style=\"word-break:break-word;vertical-align:top;padding-bottom:0;padding-right:5px;padding-left:0\" valign=\"top\"><a href=\"http://email.librarycustomer.org/ls/click?upn=ZBTqoOE-2FbngBBej6WYxn-2BE0luAVXgntFNKLVLNqkglYrr66p1TQ2RJnM3gH46mTAovUx_MdoaKvfOuShFcxUJ9loyP5ScUTHU4jacwErYTv-2F-2FhYiQGv7CpX-2BROBNsLP-2BWu2PLCN79P5Wz-2FL9XKHfmqqDqMrytpFPZk3Y09BeqcoqxiuDpENghsUGj-2BytJNJLJjRy0s72zP4RZOjYCdwLJfFK1MU0HMRCahGTvhVA-2BYSVNt9mWGtTnEH4CQEGXTj-2BTPwgslqLwUdy6XIh96CFVrkO8a1zkMw4-2BlxdfD2onO3fTq-2FVpZ6bFZahJL0Z4qZE128cJ1Re0kIo1tDIc-2FaMlNKJsBQrrmps-2BjpydSvwwSnaZxocGNU2Po7RnJiiNDO1gZhRs\" target=\"_blank\" data-saferedirecturl=\"https://www.google.com/url?q=http://email.librarycustomer.org/ls/click?upn%3DZBTqoOE-2FbngBBej6WYxn-2BE0luAVXgntFNKLVLNqkglYrr66p1TQ2RJnM3gH46mTAovUx_MdoaKvfOuShFcxUJ9loyP5ScUTHU4jacwErYTv-2F-2FhYiQGv7CpX-2BROBNsLP-2BWu2PLCN79P5Wz-2FL9XKHfmqqDqMrytpFPZk3Y09BeqcoqxiuDpENghsUGj-2BytJNJLJjRy0s72zP4RZOjYCdwLJfFK1MU0HMRCahGTvhVA-2BYSVNt9mWGtTnEH4CQEGXTj-2BTPwgslqLwUdy6XIh96CFVrkO8a1zkMw4-2BlxdfD2onO3fTq-2FVpZ6bFZahJL0Z4qZE128cJ1Re0kIo1tDIc-2FaMlNKJsBQrrmps-2BjpydSvwwSnaZxocGNU2Po7RnJiiNDO1gZhRs&amp;source=gmail&amp;ust=1598734940915000&amp;usg=AFQjCNG1w0B842ZeVrGShnd1eVHe01ThHQ\"><img width=\"32\" height=\"32\" src=\"https://ci4.googleusercontent.com/proxy/-DpEyafHzck0oHu_GFUW3kf-E2rD1r83-hMxzxeS6GWSA0BC2eLpxX4iwBIdxuwpcg89BAHYazLw6GNQXnPQR_Dyo5BjkSKq6qAHFE9aWEMlTkU92eaFQr-XqNnxur-d2S9ksfpEbCMCQKHpHUfrTqV8e8ypcbOCg1TPeVo=s0-d-e1-ft#https://d2fi4ri5dhpqd1.cloudfront.net/public/resources/social-networks-icon-sets/circle-color/facebook.png\" alt=\"Facebook\" title=\"Facebook\" style=\"text-decoration:none;height:auto;border:0;display:block\" class=\"CToWUd\"></a></td><td style=\"word-break:break-word;vertical-align:top;padding-bottom:0;padding-right:5px;padding-left:0\" valign=\"top\"><a href=\"http://email.librarycustomer.org/ls/click?upn=axxvoqJC-2FY0GlXuGTEdpSV4d8-2BPr0R0yXUpbnLIDnxzOV8-2Fch3vUmUIy1ipXMjMgloMv_MdoaKvfOuShFcxUJ9loyP5ScUTHU4jacwErYTv-2F-2FhYiQGv7CpX-2BROBNsLP-2BWu2PLCN79P5Wz-2FL9XKHfmqqDqMrytpFPZk3Y09BeqcoqxiuDpENghsUGj-2BytJNJLJjRy0s72zP4RZOjYCdwLJfFK1MU0HMRCahGTvhVA-2BYSVNt9nK-2BoJ2ezrV4IO-2BPYPr5PgRgieGs2lh8Y-2BgjjD9i-2Bao2t4pl05mL26dxvIXCJlXPg-2FW-2Fg-2BWTuD1St8FvxRhdFfzGofEEh3VGGNAtopV5Hr-2Bv-2FpZkrYes3ZuysQIDsDCs8lH8aF9Q6ZoUYCEsa9vl6Ki\" target=\"_blank\" data-saferedirecturl=\"https://www.google.com/url?q=http://email.librarycustomer.org/ls/click?upn%3DaxxvoqJC-2FY0GlXuGTEdpSV4d8-2BPr0R0yXUpbnLIDnxzOV8-2Fch3vUmUIy1ipXMjMgloMv_MdoaKvfOuShFcxUJ9loyP5ScUTHU4jacwErYTv-2F-2FhYiQGv7CpX-2BROBNsLP-2BWu2PLCN79P5Wz-2FL9XKHfmqqDqMrytpFPZk3Y09BeqcoqxiuDpENghsUGj-2BytJNJLJjRy0s72zP4RZOjYCdwLJfFK1MU0HMRCahGTvhVA-2BYSVNt9nK-2BoJ2ezrV4IO-2BPYPr5PgRgieGs2lh8Y-2BgjjD9i-2Bao2t4pl05mL26dxvIXCJlXPg-2FW-2Fg-2BWTuD1St8FvxRhdFfzGofEEh3VGGNAtopV5Hr-2Bv-2FpZkrYes3ZuysQIDsDCs8lH8aF9Q6ZoUYCEsa9vl6Ki&amp;source=gmail&amp;ust=1598734940915000&amp;usg=AFQjCNHeEG2ZcgLY6uzZn-anLgojwz0sbg\"><img width=\"32\" height=\"32\" src=\"https://ci3.googleusercontent.com/proxy/ztuVa5A-v_jNzXK718-CvD46pbdC4HGO3FTxvneMZZBDhlt0WQDkpJO52zOIAS1198H_JQsDenGjTQlfG-pLvV-dvp5kFdWNQEYPaomh1TJVKx824xml2MHEIXc3YrcBl_OkTppSVgPZv7vG5baUt4bWA-GYMh-257vS4Q=s0-d-e1-ft#https://d2fi4ri5dhpqd1.cloudfront.net/public/resources/social-networks-icon-sets/circle-color/twitter.png\" alt=\"Twitter\" title=\"Twitter\" style=\"text-decoration:none;height:auto;border:0;display:block\" class=\"CToWUd\"></a></td><td style=\"word-break:break-word;vertical-align:top;padding-bottom:0;padding-right:5px;padding-left:0\" valign=\"top\"><a href=\"http://email.librarycustomer.org/ls/click?upn=ZBTqoOE-2FbngBBej6WYxn-2BM1mYA9QrlFjIcNHzUmWYHH97MGHqIvTwLuHWeY1xTPF_3bN_MdoaKvfOuShFcxUJ9loyP5ScUTHU4jacwErYTv-2F-2FhYiQGv7CpX-2BROBNsLP-2BWu2PLCN79P5Wz-2FL9XKHfmqqDqMrytpFPZk3Y09BeqcoqxiuDpENghsUGj-2BytJNJLJjRy0s72zP4RZOjYCdwLJfFK1MU0HMRCahGTvhVA-2BYSVNt9mJ-2B5sp5yEc9wtjv3wJqMSnshidbY1qZfrA1kMrTFbF56QwJP96F6a-2FXyNVrqo01G1WdxIbYjeMHBjim-2F7IKr4T99er6E3cqHOpbG143S1AmgKjRWcLF7ODr0pnDxLc62iD66jdGNY2RLjuYJYcRNT-2B\" target=\"_blank\" data-saferedirecturl=\"https://www.google.com/url?q=http://email.librarycustomer.org/ls/click?upn%3DZBTqoOE-2FbngBBej6WYxn-2BM1mYA9QrlFjIcNHzUmWYHH97MGHqIvTwLuHWeY1xTPF_3bN_MdoaKvfOuShFcxUJ9loyP5ScUTHU4jacwErYTv-2F-2FhYiQGv7CpX-2BROBNsLP-2BWu2PLCN79P5Wz-2FL9XKHfmqqDqMrytpFPZk3Y09BeqcoqxiuDpENghsUGj-2BytJNJLJjRy0s72zP4RZOjYCdwLJfFK1MU0HMRCahGTvhVA-2BYSVNt9mJ-2B5sp5yEc9wtjv3wJqMSnshidbY1qZfrA1kMrTFbF56QwJP96F6a-2FXyNVrqo01G1WdxIbYjeMHBjim-2F7IKr4T99er6E3cqHOpbG143S1AmgKjRWcLF7ODr0pnDxLc62iD66jdGNY2RLjuYJYcRNT-2B&amp;source=gmail&amp;ust=1598734940915000&amp;usg=AFQjCNEM6PK9QkT25tUdMbnMNnkGvJ3bvQ\"><img width=\"32\" height=\"32\" src=\"https://ci5.googleusercontent.com/proxy/JnVBDuWoB86nDakRbB8DyKWNvMyjQYVHHS7m-FgGvYTJYlnpZgcJ9baqIQ7n5reueIVJEaVRlpiN80JYzpe43lPq1ILM1LCDaMDhnYRpHB_SvBRgAjxgMv9nCdJdI-CjeUFaF5kdF1SdlRwwH84jwO44YDiUoG9Q-RZ5j2-Y_RMX=s0-d-e1-ft#https://d2fi4ri5dhpqd1.cloudfront.net/public/resources/social-networks-icon-sets/circle-color/instagram@2x.png\" alt=\"Instagram\" title=\"Instagram\" style=\"text-decoration:none;height:auto;border:0;display:block\" class=\"CToWUd\"></a></td><td style=\"word-break:break-word;vertical-align:top;padding-bottom:0;padding-right:5px;padding-left:0\" valign=\"top\"><a href=\"https://www.youtube.com/channel/UCRgbjcUeahL2NbWHeh-cnTA\" target=\"_blank\" data-saferedirecturl=\"https://www.google.com/url?q=https://www.youtube.com/channel/UCRgbjcUeahL2NbWHeh-cnTA&amp;source=gmail&amp;ust=1598734940915000&amp;usg=AFQjCNFoGjI8NN4oCizkpKdsCfVdhP64LQ\"><img width=\"32\" height=\"32\" src=\"https://ci3.googleusercontent.com/proxy/VCwXmYraU1ze0eeZ79VTJKlBqwFvdcnLbSckv_onti1JVgfvDVjzbNAagPzrrxUZAKjIeBJD2Kez9LDSx-7a46EK7fekY1MXktddEOGljsQZGgq29Qnpkoo57YrEInViTtuB_yl_OjIWS4HBq1-qdn9ON8V63apwafjDPjyzIA=s0-d-e1-ft#https://d2fi4ri5dhpqd1.cloudfront.net/public/resources/social-networks-icon-sets/circle-color/youtube@2x.png\" alt=\"YouTube\" title=\"YouTube\" style=\"text-decoration:none;height:auto;border:0;display:block\" class=\"CToWUd\"></a></td></tr></tbody></table></td></tr></tbody></table></div></div></div></div></div></div>"
     . 
    "<div style=\"background-color:#444444\"><div class=\"m_6602918289513662440block-grid\" style=\"Margin:0 auto;min-width:320px;max-width:500px;word-wrap:break-word;word-break:break-word;background-color:transparent\"><div style=\"border-collapse:collapse;display:table;width:100%;background-color:transparent\"><div class=\"m_6602918289513662440col\" style=\"min-width:320px;max-width:500px;display:table-cell;vertical-align:top;width:500px\"><div style=\"width:100%!important\"><div style=\"border-top:0px solid transparent;border-left:0px solid transparent;border-bottom:0px solid transparent;border-right:0px solid transparent;padding-top:25px;padding-bottom:25px;padding-right:0px;padding-left:0px\"><div align=\"center\" style=\"padding-right:0px;padding-left:0px\"><a href=\"http://email.librarycustomer.org/ls/click?upn=ZBTqoOE-2FbngBBej6WYxn-2BAGUipKMCQujNhzFJcmnDSrP6-2FA-2Bl3qSiu8zS9xBUTSRizWo_MdoaKvfOuShFcxUJ9loyP5ScUTHU4jacwErYTv-2F-2FhYiQGv7CpX-2BROBNsLP-2BWu2PLCN79P5Wz-2FL9XKHfmqqDqMrytpFPZk3Y09BeqcoqxiuDpENghsUGj-2BytJNJLJjRy0s72zP4RZOjYCdwLJfFK1MU0HMRCahGTvhVA-2BYSVNt9mslUYjDRRw71MnczMSPU17TgjH-2BwlI2pZBFoKDmITBH1HqsHS-2B3aEFg7I3JsDqpixg3iWZ7fpVI3CQKxZJ-2FicWxz3pZ6NQ-2FJvIBraZB-2FWFVTIQek3naLNR6K54pJ3bFHccGyv7SP-2FARjeAf-2BSUiHik\" style=\"outline:none\" target=\"_blank\" data-saferedirecturl=\"https://www.google.com/url?q=http://email.librarycustomer.org/ls/click?upn%3DZBTqoOE-2FbngBBej6WYxn-2BAGUipKMCQujNhzFJcmnDSrP6-2FA-2Bl3qSiu8zS9xBUTSRizWo_MdoaKvfOuShFcxUJ9loyP5ScUTHU4jacwErYTv-2F-2FhYiQGv7CpX-2BROBNsLP-2BWu2PLCN79P5Wz-2FL9XKHfmqqDqMrytpFPZk3Y09BeqcoqxiuDpENghsUGj-2BytJNJLJjRy0s72zP4RZOjYCdwLJfFK1MU0HMRCahGTvhVA-2BYSVNt9mslUYjDRRw71MnczMSPU17TgjH-2BwlI2pZBFoKDmITBH1HqsHS-2B3aEFg7I3JsDqpixg3iWZ7fpVI3CQKxZJ-2FicWxz3pZ6NQ-2FJvIBraZB-2FWFVTIQek3naLNR6K54pJ3bFHccGyv7SP-2FARjeAf-2BSUiHik&amp;source=gmail&amp;ust=1598734940915000&amp;usg=AFQjCNHXIW-_To0OEgjQ6l5j_7eSXnUKLQ\"> <img align=\"center\" border=\"0\" src=\"https://ci5.googleusercontent.com/proxy/mAAjdPWczWfz801zHYQXlPTsB62AH1LOS_SSbCQO9MvEOsaBrjd-bUxQJFJdKM6qcxvYDqN7MYNYEM7RdG22UGa_RJ_p3eAvqlhqjEzF73nrhF2YEhUrDtZnHIzgTGUhKhfWfK5_WpMGkkzwnB19fJmiD0xxuVs0w_Ss2vf4YcE5dSL7JDd4akKi9-YdH08kwNT0KxXpN515XprbrSz-vC_GQiSdVWoaU4oSfCUC3P6Teoa5gSKymqm2tK46Gey1CkziZ1f5TReNkDrAj0QtVN0RHakRvjmCjGauTTRC0Titgg=s0-d-e1-ft#http://messages.librarycustomer.org/images/92eaf0a6-d709-46bf-b4af-07fc7faf8b4d/G8GJGI972020/Glendale_Library_Arts_Culture_Logo_Verbiage-WHITE%2520BG%2520-%2520ALL-OUTLINES-003%2520WHITE%2520TXT%2520new.png\" alt=\"Alternate text\" title=\"Alternate text\" style=\"text-decoration:none;height:auto;border:0;width:100%;max-width:200px;display:block\" width=\"200\" class=\"CToWUd\"></a></div><div style=\"color:#bbbbbb;font-family:Arial,Helvetica Neue,Helvetica,sans-serif;line-height:1.2;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px\"><div style=\"font-size:12px;line-height:1.2;color:#bbbbbb;font-family:Arial,Helvetica Neue,Helvetica,sans-serif\"><p style=\"font-size:14px;line-height:1.2;text-align:center;word-break:break-word;margin:0\"><span style=\"font-size:14px\">Glendale Library, Arts &amp; Culture</span></p><p style=\"font-size:14px;line-height:1.2;text-align:center;word-break:break-word;margin:0\"><span style=\"font-size:14px\">222 East Harvard Street</span></p><p style=\"font-size:14px;line-height:1.2;text-align:center;word-break:break-word;margin:0\"><span style=\"font-size:14px\">Glendale CA 91205</span></p><p style=\"font-size:14px;line-height:1.2;text-align:center;word-break:break-word;margin:0\"><span style=\"font-size:14px\">818.548.2021</span></p><p style=\"font-size:18px;line-height:1.2;text-align:center;word-break:break-word;margin:0\"><a style=\"text-decoration:underline;color:#0000ff\" href=\"http://email.librarycustomer.org/ls/click?upn=ZBTqoOE-2FbngBBej6WYxn-2BAGUipKMCQujNhzFJcmnDSrP6-2FA-2Bl3qSiu8zS9xBUTSRFtkF_MdoaKvfOuShFcxUJ9loyP5ScUTHU4jacwErYTv-2F-2FhYiQGv7CpX-2BROBNsLP-2BWu2PLCN79P5Wz-2FL9XKHfmqqDqMrytpFPZk3Y09BeqcoqxiuDpENghsUGj-2BytJNJLJjRy0s72zP4RZOjYCdwLJfFK1MU0HMRCahGTvhVA-2BYSVNt9lOAwrjGlXy58rpLHB85IqM1jl-2FueuAG5dzqUamgIuR21f9f4Wl49zgA3170jkOj0LjBwupC6sXS83T3TzDix1-2FKMaUDtrng-2BAAZt05YpkD5SSXbh8EiE9Qq5AXsJ1Sb9AgOXLMImDz-2ByhXZPC0Ia69\" rel=\"noopener\" target=\"_blank\" data-saferedirecturl=\"https://www.google.com/url?q=http://email.librarycustomer.org/ls/click?upn%3DZBTqoOE-2FbngBBej6WYxn-2BAGUipKMCQujNhzFJcmnDSrP6-2FA-2Bl3qSiu8zS9xBUTSRFtkF_MdoaKvfOuShFcxUJ9loyP5ScUTHU4jacwErYTv-2F-2FhYiQGv7CpX-2BROBNsLP-2BWu2PLCN79P5Wz-2FL9XKHfmqqDqMrytpFPZk3Y09BeqcoqxiuDpENghsUGj-2BytJNJLJjRy0s72zP4RZOjYCdwLJfFK1MU0HMRCahGTvhVA-2BYSVNt9lOAwrjGlXy58rpLHB85IqM1jl-2FueuAG5dzqUamgIuR21f9f4Wl49zgA3170jkOj0LjBwupC6sXS83T3TzDix1-2FKMaUDtrng-2BAAZt05YpkD5SSXbh8EiE9Qq5AXsJ1Sb9AgOXLMImDz-2ByhXZPC0Ia69&amp;source=gmail&amp;ust=1598734940915000&amp;usg=AFQjCNEykaF-jOrmF4KRmv3H3f9U6jFQwA\">www.eGlendaleLAC.org</a></p></div></div></div></div></div></div></div></div>";
    
//Add recipient
	$mail->addAddress($post_email);
//Finally send email
	if ( $mail->send() ) {
		echo "Email Sent..!";
	}else{
		echo "Message could not be sent. Mailer Error: "{$mail->ErrorInfo};
	}
//Closing smtp connection
	$mail->smtpClose();


/*
 $email = new PHPMailer();
 $email->IsHTML(true);
 $email->SetFrom(mailFrom, 'Glendale Public Library'); //Name is optional
 $email->Subject   = 'Welcome to GLAC!';
 $email->Body      = $bodytext;
 $email->AddAddress($post_email);

 $file_to_attach = './' . $libraryCardFile;


 //$email->AddAttachment( $file_to_attach , '21387009002003.png' );
 $email->AddEmbeddedImage('./html_email/images/Background_1_4.png','Background_1_4.png');
 $email->AddEmbeddedImage('./html_email/images/iphone.png','iphone.png');
 //$email->AddEmbeddedImage('./html_email/images/ecard.png','ecard.png');
 $email->AddEmbeddedImage('./html_email/images/Bee.png','Bee.png');
 $email->AddEmbeddedImage('./html_email/images/Bee_2.png','Bee_2.png');
 $email->AddEmbeddedImage('./html_email/images/gale_comp.png','gale_comp.png');
 $email->AddEmbeddedImage('./html_email/images/mpl_logo.png','mpl_logo.png');
 $email->AddEmbeddedImage('./html_email/images/facebook.png','facebook.png');
 $email->AddEmbeddedImage('./html_email/images/twitter.png','twitter.png');
 $email->AddEmbeddedImage('./html_email/images/insta.png','insta.png');
 $email->AddEmbeddedImage($file_to_attach,'patroncard.png');



 return $email->Send(); */

  ?>