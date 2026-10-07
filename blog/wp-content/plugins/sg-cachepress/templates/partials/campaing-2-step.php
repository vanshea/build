				<!-- Header Image With link -->
				<tr>
					<td style="padding: 0 0 40px 0">
						<a href="<?php echo esc_url( $args['campaign_link'] ); ?>"
						   class="body-text"
						   target="_blank" rel="noreferrer"><img
								src="<?php echo esc_url( $args['header_image'] ); ?>"
								width="600"
								alt="<?php echo esc_url( $args['header_alt'] ); ?>"
								style="max-height: 300px;"/></a>
					</td>
				</tr>
				<!--End Header Image With link -->
				
				<!-- Primary Title / H1 -->
				<tr>
					<td class="body-text"
						style="color: #363636; font-weight: 600; font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 30px; line-height: 40px; padding: 0 0 30px 0"><?php echo wp_kses_post( $args['primary_title'] ); ?></td>
				</tr>
				<!-- End Primary Title / H1 -->
				<!-- Paragraph -->
				<tr>
					<td class="body-text"
						style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0px 0 30px 0"><?php echo wp_kses_post( $args['paragraph_1'] ); ?></td>
				</tr>
				<!-- End Paragraph -->
				
				<!-- Paragraph with styled link-->
				<tr>
					<td class="body-text"
						style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0 0 30px 0"><?php echo wp_kses_post( $args['paragraph_2'] ); ?></td>
				</tr>
				<!-- End Paragraph with styled link-->
				
				<tr>
					<td style="padding: 0 0 30px 0;">
						<!--Start List-->
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td class="body-text"
									style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px;">
									<span style="color: #363636; display: inline-block; padding: 0 5px 0 0;"
										  class="body-text">&#9679;</span>                                  <?php echo wp_kses_post( $args['list_text'] ); ?></td>
							</tr>
							<tr>
								<td class="body-text"
									style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px;">
									<span style="color: #363636; display: inline-block; padding: 0 5px 0 0;"
										  class="body-text">&#9679;</span>                                  <?php echo wp_kses_post( $args['list_text_2'] ); ?></td>
							</tr>
							<tr>
								<td class="body-text"
									style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px;">
									<span style="color: #363636; display: inline-block; padding: 0 5px 0 0;"
										  class="body-text">&#9679;</span>                                  <?php echo wp_kses_post( $args['list_text_3'] ); ?></td>
							</tr>
							<tr>
								<td class="body-text"
									style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px;">
									<span style="color: #363636; display: inline-block; padding: 0 5px 0 0;"
										  class="body-text">&#9679;</span>                                  <?php echo wp_kses_post( $args['list_text_4'] ); ?></td>
							</tr>
							
						</table>
					</td>
				</tr>
				 
				 <!-- CTA -->
				<tr>
					<td class="body-text"
						style="padding: 0px 0 60px 0">
						<a href="<?php echo esc_url( $args['campaign_link'] ); ?>" target="_blank" class="body-text"
						   style="font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 18px; font-weight: 600; line-height: 20px; text-transform: uppercase; text-decoration: none; display: inline-block; border-top: 15px solid #4343f0; border-bottom: 15px solid #4343f0; border-left: 30px solid #4343f0; border-right: 30px solid #4343f0;  color: #ffffff; background: #4343f0; border-radius: 25px;" rel="noreferrer"><?php echo wp_kses_post( $args['button_text'] ); ?></a>
					</td>
				</tr>
				<!-- End CTA -->
				 
				<!-- Paragraph with styled link-->
				<tr>
					<td class="body-text"
						style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0 0 30px 0"><?php echo wp_kses_post( $args['paragraph_3'] ); ?></td>
				</tr>
				<!-- End Paragraph with styled link-->
