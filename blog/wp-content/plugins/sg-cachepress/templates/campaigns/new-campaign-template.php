<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en"
	  xmlns:v="urn:schemas-microsoft-com:vml"
	  xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<!--[if !mso]><!-->
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<!--<![endif]-->
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="color-scheme" content="light dark"/>
	<meta name="supported-color-schemes" content="light dark"/>
	<meta name="description" content="<?php echo esc_html( $args['meta_title'] ); ?>"/>
	<title><?php echo esc_html( $args['meta_title'] ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com" />
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Roboto:wght@400;700&display=swap"
		  rel="stylesheet" />
	<style type="text/css">
		:root {color-scheme: light dark; supported-color-schemes: light dark;}
		body {margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;}
		img {max-width: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; display: block !important; border: none;}
		#bodytable, #headtable, #backgroundTable {margin: 0; width: 100% !important; line-height: 100%;}
		@media screen and (max-width: 480px), screen and (max-device-width: 480px) {
			.flex, [class=flex] {width: 100% !important;}
			.dblock, [class=dblock] {display: block !important; width: 100% !important; max-width: 100%; padding: 0 !important; max-height: none !important;}
			a {word-break: break-word;}
		}
		@media (prefers-color-scheme: dark) {
			.dark-img {display: block !important; width: auto !important; overflow: visible !important; float: none !important; max-height: inherit !important; max-width: inherit !important; line-height: auto !important; margin-top: 0 !important; visibility: inherit !important;}
			.light-img {display: none !important;}
			#bodytable, #backgroundTable, body {background: #363636 !important;}
			#headtable {background: #666 !important;}
			.body-text, h1, h2, p, strong, em, b {color: #f2f2f2 !important;}
			.infobox {background: #666 !important;}
			.datatable td, .datatable th {background: #363636 !important;}
			a, a strong {color: #b2b2f8 !important;}
			[data-ogsc] .dark-img {display: block !important; width: auto !important; overflow: visible !important; float: none !important; max-height: inherit !important; max-width: inherit !important; line-height: auto !important; margin-top: 0px !important; visibility: inherit !important;}
			[data-ogsc] .light-img {display: none !important;}
			[data-ogsb] #bodytable, [data-ogsb] #backgroundTable, [data-ogsb] body {background: #363636 !important;}
			[data-ogsc] #headtable {background: #666 !important;}
			[data-ogsc] .body-text, [data-ogsb] h1, [data-ogsb] h2, [data-ogsb] p, [data-ogsb] strong, [data-ogsb] em, [data-ogsb] b {color: #f2f2f2 !important;}
			[data-ogsb] .infobox {background: #666 !important;}
			[data-ogsb] .datatable td, [data-ogsb] .datatable th {background: #363636 !important;}
			[data-ogsc] a, [data-ogsb] a strong {color: #b2b2f8 !important;}
		}
	</style>
	<!--Fallback For Outlook -->
	<!--[if mso]>
	<style type=”text/css”>
		.body-text {
			font-family: Arial, sans-serif !important;
		}
	</style>
	<![endif]-->
	<!--MS Outlook 120 DPI fix-->
	<!--[if gte mso 9]>
	<xml>
		<o:OfficeDocumentSettings>
			<o:AllowPNG/>
			<o:PixelsPerInch>96</o:PixelsPerInch>
		</o:OfficeDocumentSettings>
	</xml>
	<![endif]-->
</head>
<body style="margin: 0; padding: 0;">
<!-- Preheader -->
<div class="preheader" style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0;">
	<?php echo esc_html( $args['preheader'] ); ?>
</div>
<!-- End Preheader -->
<!-- Main Wrapper Table-->
<table class="flex" border="0" cellpadding="0" cellspacing="0" width="100%" id="backgroundTable"
	   style="background: #ffffff;">
	<tr>
		<td style="padding: 0 30px; ">
			<!--Main Centered Container -->
			<table class="flex" align="center" border="0" cellpadding="0" cellspacing="0" width="600"
				   style="border-collapse: collapse; font-family: 'Roboto', Arial, Helvetica, sans-serif;">
				<!--Logo -->
				<tr>
					<td>
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td height="30"
									style="padding: 40px 0 40px 0;">
									<!-- Logo Link -->
									<a style="border: none"
									   href="<?php echo esc_url( $args['logo_link'] ); ?>"
									   target="_blank" rel="noreferrer">
										<img class="light-img" style="border: none; outline: none;"
											 src="<?php echo esc_url( $args['logo_image_light'] ); ?>"
											 width="170" alt="<?php echo esc_url( $args['logo_image_alt'] ); ?>"/>
										<!--[if !mso]><! -->
										<div class="dark-img"
											 style="display:none; overflow:hidden; float:left; width:0px; max-height:0px; max-width:0px; line-height:0px; visibility:hidden;">
											<img style="border: none; outline: none;"
												 src="<?php echo esc_url( $args['logo_image_dark'] ); ?>"
												 width="170" alt="<?php echo esc_url( $args['logo_image_alt'] ); ?>"/></div>
										<!--<![endif]-->
									</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<!--End Logo -->
				
				<!-- Include the campaing step body -->
				<?php include_once( \SiteGround_Optimizer\DIR . '/templates/partials/campaing-' . $args['campaign_step'] . '-step.php' ); ?>
				
				<!-- Siganture -->
				<tr>
					<td class="body-text"
						style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0 0 60px 0">
						<?php echo esc_html( $args['signature_1'] ); ?><br/>
						<?php echo esc_html( $args['signature_2'] ); ?>
					</td>
				</tr>
				<!-- End Signature -->
				
				<!-- Social Networks Icon - EN -->
				<tr>
					<td style="padding: 0 0 30px 0;">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<!-- Facebook EN-->
								<td width="32" style="padding: 0 0 20px 20px;" align="center" valign="middle">
									<a style="text-decoration: none;" target="_blank"
									   href="<?php echo esc_url( $args['facebook_link'] ); ?>"><img
											src="<?php echo esc_url( $args['facebook_img'] ); ?>"
											width="32" alt="Facebook"/></a>
								</td>
								<!-- End Facebook EN-->
								<!-- Instagram EN -->
								<td width="32" style="padding: 0 0 20px 20px;" align="center" valign="middle">
									<a style="text-decoration: none;" target="_blank"
									   href="<?php echo esc_url( $args['instagram_link'] ); ?>"><img
											src="<?php echo esc_url( $args['instagram_img'] ); ?>"
											width="32" alt="Instagram"/></a>
								</td>
								<!-- End Instagram EN -->
								<!-- Twitter EN -->
								<td width="32" style="padding: 0 0 20px 20px;" align="center" valign="middle">
									<a style="text-decoration: none;" target="_blank"
									   href="<?php echo esc_url( $args['twitter_link'] ); ?>"><img
											src="<?php echo esc_url( $args['twitter_img'] ); ?>"
											width="32" alt="Twitter"/></a>
								</td>
								<!-- End Twitter EN -->
								<!-- YouTube EN -->
								<td width="32" style="padding: 0 0 20px 20px;" align="center" valign="middle">
									<a style="text-decoration: none;" target="_blank"
									   href="<?php echo esc_url( $args['youtube_link'] ); ?>"><img
											src="<?php echo esc_url( $args['youtube_img'] ); ?>"
											width="32" alt="YouTube"/></a>
								</td>
								<!-- End YouTube EN -->
								<!-- LinkedIn EN-->
								<td width="32" style="padding: 0 20px 20px 20px;" align="center" valign="middle">
									<a style="text-decoration: none;" target="_blank"
									   href="<?php echo esc_url( $args['linkedin_link'] ); ?>"><img
											src="<?php echo esc_url( $args['linkedin_img'] ); ?>"
											width="32" alt="LinkedIn"/></a>
								</td>
								<!-- End LinkedIn EN -->
							</tr>
						</table>
					</td>
				</tr>
				<!-- End Social Networks Icon - EN -->
				
				<!--Footer Non Clients-->
				<tr>
					<td class="body-text"
						style="color: #a4a4a4; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 14px; line-height: 20px; padding: 0 0 30px 0">
						<p><?php echo esc_html( $args['unsubscribe_text'] ); ?><a href="<?php echo esc_url( $args['unsubscribe_link'] ); ?>" target="_blank" rel="noreferrer" style="color: #4343f0; text-decoration: none;"><?php echo esc_html( $args['unsubscribe_text_end'] ); ?></a>.</p>
					</td>
				</tr>
			  <!--End Footer Non Clients-->
				
			</table>
			<!-- End Main Centered Container -->
		</td>
	</tr>
</table>
<!-- End Main Wrapper Table-->

</body>
</html>
