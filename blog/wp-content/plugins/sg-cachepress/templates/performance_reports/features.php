<?php foreach ( $args['features'] as $feature ) { ?>
				<tr>
					<!--Table -->
					<td class="body-text"
						style="padding: 0 0 30px 0">
						<table class="datatable" border="0" cellpadding="0" cellspacing="0" width="" style=" width: 100%; border: 1px solid #ddd; border-bottom: none; border-radius: 16px;">
							<tr>
								<th style="font-size: 0; line-height: 0; padding: 0 0 0 20px; border-bottom: 1px solid #ddd; border-radius: 16px 0 0 0;" width="23">
									<img src="https://www.siteground.com/static/en/img/emails/icons/<?php echo esc_html( $feature['status'] ); ?>.png" width="23" height="23" alt="<?php echo esc_url( $feature['status'] ); ?>">
								</th>
								<th class="body-text" align="left"
									style="color: #363636; font-weight: 600; font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 14px; line-height: 20px; border-bottom: 1px solid #ddd; padding: 20px 0px 20px 10px;"><?php echo esc_html( $feature['title'] ); ?></th>
								<th class="body-text" align="right"
									style="width:25%; color: #363636;  font-weight: 600; font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 14px; line-height: 20px; border-bottom: 1px solid #ddd; padding: 20px 20px 20px 0px; border-radius: 0 16px 0 0"><?php echo esc_html( $feature['score_text'] ); ?></th>
							</tr>
							<tr>
								<td colspan="3" class="body-text" align="left"
									style="color: #363636; font-weight: 400; font-family: 'Roboto', Arial, Helvetica, sans-serif; font-size: 14px; line-height: 20px; border-radius: 0 0 16px 16px;
									<?php
									if ( empty( $feature['button_text'] ) ) {
										?>
										 border-bottom: 1px solid #ddd; padding: 20px 20px 20px 20px
										<?php
									} else {
										?>
										 border-bottom: 0px; padding: 20px 20px 0px 20px <?php } ?>"><?php echo wp_kses_post( $feature['text'] ); ?></td>
							</tr>
							<?php if ( ! empty( $feature['button_text'] ) ) { ?>
							<tr>
								<td colspan="3" class="body-text" align="left"
									style="color: #363636; font-weight: 600; font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 14px; line-height: 20px; border-bottom: 1px solid #ddd; border-radius: 0 0 16px 16px; padding: 20px 20px 20px 20px"><a href="<?php echo esc_url( $feature['button_link'] ); ?>" target="_blank" rel="noreferrer" style="color: #4343f0; outline: none; text-decoration: none; text-transform: uppercase;"><?php echo esc_html( $feature['button_text'] ); ?></a> </td>
							</tr>
							<?php } ?>
						</table>
					</td>
				</tr>
<?php } ?>
