				
				<!-- Header Image With link -->
				<tr>
					<td style="padding: 0 0 40px 0">
						<a href="<?php echo esc_url( $args['campaign_link'] ); ?>"
						   class="body-text"
						   target="_blank" rel="noreferrer"><img
								src="<?php echo esc_url( $args['header_image'] ); ?>"
								width="600"
								alt="<?php echo esc_url( $args['header_image_alt'] ); ?>"
								style="max-height: 300px;"/></a>
					</td>
				</tr>
				<!--End Header Image With link -->
				
				<!-- Primary Title / H1 -->
				<tr>
					<td class="body-text"
						style="color: #363636; font-weight: 600; font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 30px; line-height: 40px; padding: 0 0 30px 0"><?php echo esc_html( $args['primary_title'] ); ?></td>
				</tr>
				<!-- End Primary Title / H1 -->
				<!-- Paragraph -->
				<tr>
					<td class="body-text"
						style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0px 0 30px 0"><?php echo esc_html( $args['paragraph_1'] ); ?><a href="<?php echo esc_url( $args['campaign_link'] ); ?>" target="_blank" rel="noreferrer" style="color: #4343f0; outline: none; text-decoration: none;"><?php echo esc_html( $args['paragraph_1_end'] ); ?></a></td>
				</tr>
				<!-- End Paragraph -->
				
				<!-- CTA -->
				<tr>
					<td class="body-text"
						style="padding: 0px 0 60px 0">
						<a href="<?php echo esc_url( $args['campaign_link'] ); ?>" target="_blank" class="body-text"
						   style="font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 18px; font-weight: 600; line-height: 20px; text-transform: uppercase; text-decoration: none; display: inline-block; border-top: 15px solid #4343f0; border-bottom: 15px solid #4343f0; border-left: 30px solid #4343f0; border-right: 30px solid #4343f0;  color: #ffffff; background: #4343f0; border-radius: 25px;" rel="noreferrer"><?php echo esc_html( $args['button_text'] ); ?></a>
					</td>
				</tr>
				<!-- End CTA -->
				
				<!-- Primary Title / H1 -->
				<tr>
					<td class="body-text"
						style="color: #363636; font-weight: 600; font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 30px; line-height: 40px; padding: 0 0 60px 0"><?php echo esc_html( $args['primary_title_2'] ); ?></td>
				</tr>
				<!-- End Primary Title / H1 -->
				
				<tr>
		<td style="padding: 0 0 30px 0;">
			<!-- Features Table -->
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="60" style="padding: 0 0 20px 20px;" align="center" valign="top">
						<img src="<?php echo esc_url( $args['features_table_img'] ); ?>"
							 width="60" alt=""/>
					</td>
					<td class="body-text"
						style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0 0 30px 20px">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td class="body-text"
									style="color: #363636; font-weight: 600; font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 22px; line-height: 30px; padding: 0 0 20px 0"><?php echo wp_kses_post( $args['features_table_txt'] ); ?></td>
							</tr>
							<tr>
								  <td height="90" class="body-text"
										style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0px"><?php echo wp_kses_post( $args['features_table_txt_2'] ); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td width="60" style="padding: 0 0 20px 20px;" align="center" valign="top">
						<img src="<?php echo esc_url( $args['features_table_img_2'] ); ?>"
							 width="60" alt=""/>
					</td>
					<td class="body-text"
						style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0 0 30px 20px">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td class="body-text"
									style="color: #363636; font-weight: 600; font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 22px; line-height: 30px; padding: 0 0 20px 0"><?php echo wp_kses_post( $args['features_table_txt_3'] ); ?></td>
							</tr>
							<tr>
								  <td class="body-text"
										style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0px"><?php echo wp_kses_post( $args['features_table_txt_4'] ); ?><a href="<?php echo esc_url( $args['features_table_text4_link'] ); ?>" target="_blank" rel="noreferrer" style="color: #4343f0; outline: none; text-decoration: none;"><?php echo wp_kses_post( $args['features_table_text4_link_text'] ); ?></a><?php echo wp_kses_post( $args['features_table_txt_5'] ); ?><a href="<?php echo esc_url( $args['features_table_txt5_link'] ); ?>" target="_blank" rel="noreferrer" style="color: #4343f0; outline: none; text-decoration: none;"><?php echo wp_kses_post( $args['features_table_txt5_link_text'] ); ?></a><?php echo wp_kses_post( $args['features_table_txt_6'] ); ?><a href="<?php echo esc_url( $args['features_table_txt6_link'] ); ?>" target="_blank" rel="noreferrer" style="color: #4343f0; outline: none; text-decoration: none;"><?php echo wp_kses_post( $args['features_table_txt6_link_text'] ); ?></a><?php echo wp_kses_post( $args['features_table_txt_7'] ); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				
			</table>
		</td>
	</tr>
				
			  
				
				<!-- Promo/Info LightBlue Box With CTA -->
				<tr>
					<td style="padding: 0 0 60px 0;">
						<table class="infobox" border="0" cellpadding="0" cellspacing="0" width="100%"
							   bgcolor="#dfecfc" style="border-radius: 16px;">
							<tr>
								<td style="padding: 30px;">
									<table width="100%">
										<!-- Secondary Title / H2 -->
										<tr>
											<td class="body-text"
												style="color: #363636; font-weight: 600; font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 22px; line-height: 30px; padding: 0 0 20px 0;"><?php echo wp_kses_post( $args['secondary_title'] ); ?></td>
										</tr>
										<!-- End Secondary Title / H2 -->
										
										<!-- Paragraph with styled link-->
										<tr>
											<td class="body-text"
												style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0 0 30px 0"><?php echo wp_kses_post( $args['paragraph_2'] ); ?></td>
										</tr>
										<!-- End Paragraph with styled link-->
										
										
										<!-- Standart List With Items -->
										<tr>
											<td style="padding: 0 0 30px 0;">
												<table border="0" cellpadding="0" cellspacing="0" width="100%">
													<!-- List Item -->
													<tr>
														<td class="body-text"
															style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px;">
															<!-- List Icon -->
															<span style="color: #363636; display: inline-block; padding: 0 5px 0 0;"
																  class="body-text">&#9679;</span>
															<!-- End List Icon -->
															<!-- List Content -->
															<?php echo wp_kses_post( $args['list_content'] ); ?>
															<!-- End List Content -->
														</td>
													</tr>
													<!-- End List Item -->
													<!-- List Item -->
													<tr>
														<td class="body-text"
															style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px;">
															<!-- List Icon -->
															<span style="color: #363636; display: inline-block; padding: 0 5px 0 0;"
																  class="body-text">&#9679;</span>
															<!-- End List Icon -->
															<!-- List Content -->
															<?php echo wp_kses_post( $args['list_content_2'] ); ?>
															<!-- End List Content -->
														</td>
													</tr>
													<!-- End List Item -->
													<!-- List Item -->
													<tr>
														<td class="body-text"
															style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px;">
															<!-- List Icon -->
															<span style="color: #363636; display: inline-block; padding: 0 5px 0 0;"
																  class="body-text">&#9679;</span>
															<!-- End List Icon -->
															<!-- List Content -->
															<?php echo wp_kses_post( $args['list_content_3'] ); ?>
															<!-- End List Content -->
														</td>
													</tr>
													<!-- End List Item -->

												</table>
											</td>
										</tr>
				<!-- End Standart List With Links -->
										<!-- Paragraph -->
										<tr>
											<td class="body-text"
												style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0 0 30px 0"><?php echo wp_kses_post( $args['paragraph_3'] ); ?></td>
										</tr>
										<!-- End Paragraph -->
										<!-- CTA -->
										<tr>
											<td class="body-text"
												style="padding: 0">
												<a href="<?php echo esc_url( $args['button_2_link'] ); ?>"
												   class="body-text"
												   target="_blank" rel="noreferrer"
												   style="font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 18px; font-weight: 600; line-height: 20px; text-transform: uppercase; text-decoration: none; display: inline-block; border-top: 15px solid #4343f0; border-bottom: 15px solid #4343f0; border-left: 30px solid #4343f0; border-right: 30px solid #4343f0;  color: #ffffff; background: #4343f0; border-radius: 25px;"><?php echo wp_kses_post( $args['button_2_text'] ); ?></a>
											</td>
										</tr>
										<!-- End CTA -->
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<!-- END Promo/Info LightBlue Box With CTA -->
				
				<!-- Paragraph with styled link-->
				<tr>
					<td class="body-text"
						style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 18px; line-height: 30px; padding: 0 0 30px 0"><?php echo wp_kses_post( $args['paragraph_4'] ); ?></td>
				</tr>
				<!-- End Paragraph with styled link-->
			
