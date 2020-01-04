<div class="wrap tc_wrap">
	<div id="poststuff" class="metabox-holder tc-settings">
		<form action="" method="post" enctype = "multipart/form-data">

			<div id="store_settings" class="postbox">
				<h3><span><?php _e( 'Attendee List (PDF Export)', 'tc' ); ?></span></h3>
				<div class="inside">
					<table class="form-table">

						<tbody>
							<tr valign="top">
								<th scope="row"><label for="tc_export_event_data"><?php _e( 'Event', 'tc' ); ?></label></th>
								<td>
									<?php
									$wp_events_search = new TC_Events_Search( '', '', -1 );
									?>
									<select name="tc_export_event_data">
										<?php
										foreach ( $wp_events_search->get_results() as $event ) {
											$event_obj		 = new TC_Event( $event->ID );
											$event_object	 = $event_obj->details;
											?>
											<option value="<?php echo (int)$event_object->ID; ?>"><?php echo $event_object->post_title; ?></option>
											<?php
										}
										?>
									</select>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><label for="attendee_export_field"><?php _e( 'Show Columns', 'tc' ); ?></label></th>
								<td>
									<fieldset>
										<label for="col_checkbox" class="tc_checkboxes_label">
											<input type="checkbox" id="col_checkbox" name="col_checkbox">
											<?php _e( 'Check Field (useful for manually check-ins)', 'tc' ); ?>
										</label>

										<label for="col_owner_name" class="tc_checkboxes_label">
											<input type="checkbox" id="col_owner_name" name="col_owner_name" checked="checked">
											<?php _e( 'Ticket Owner', 'tc' ); ?>
										</label>

										<label for="col_payment_date" class="tc_checkboxes_label">
											<input type="checkbox" id="col_payment_date" name="col_payment_date" checked="checked">
											<?php _e( 'Payment Date', 'tc' ); ?>
										</label>

										<label for="col_ticket_id" class="tc_checkboxes_label">
											<input type="checkbox" id="col_ticket_id" name="col_ticket_id" checked="checked">
											<?php _e( 'Ticket ID', 'tc' ); ?>
										</label>


										<label for="col_ticket_type" class="tc_checkboxes_label">
											<input type="checkbox" id="col_ticket_type" name="col_ticket_type" checked="checked">
											<?php _e( 'Ticket Type', 'tc' ); ?>
										</label>

										<label for="col_buyer_name" class="tc_checkboxes_label">
											<input type="checkbox" name="col_buyer_name" id="col_buyer_name" checked="checked">
											<?php _e( 'Buyer Name', 'tc' ); ?>
										</label>

										<label for="col_buyer_email" class="tc_checkboxes_label">
											<input type="checkbox" name="col_buyer_email" id="col_buyer_email" checked="checked">                                                                                
											<?php _e( 'Buyer Email', 'tc' ); ?>                                                                                
										</label>
                                                                            
                                                                            
                                                                                <label for="col_checked_in" class="tc_checkboxes_label">
											<input type="checkbox" name="col_checked_in" id="col_checked_in" checked="checked">                                                                                
											<?php _e( 'Checked-in', 'tc' ); ?>                                                                                
										</label>
                                                                            
                                                                                <label for="col_checkins" class="tc_checkboxes_label">
											<input type="checkbox" name="col_checkins" id="col_checkins" checked="checked">                                                                                
											<?php _e( 'Check-ins', 'tc' ); ?>                                                                                
										</label>
                                                                            

<!--<input type="checkbox" name="col_barcode"><?php _e( 'Barcode', 'tc' ); ?><br />-->
<!--<input type="checkbox" name="col_qrcode"><?php _e( 'QR code', 'tc' ); ?><br />-->
										<?php do_action( 'tc_pdf_admin_columns' ); ?>
									</fieldset>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><label for="document_font"><?php _e( 'Document Font', 'tc' ); ?></label></th>
								<td>
									<label>
										<select name="document_font">
											<option value='aealarabiya'><?php _e( 'Al Arabiya', 'tc' ); ?></option>
											<option value='aefurat'><?php _e( 'Furat', 'tc' ); ?></option>
											<option value='cid0cs'><?php _e( 'Arial Unicode MS (Simplified Chinese)', 'tc' ); ?></option>
											<option value='cid0jp'><?php _e( 'Arial Unicode MS (Japanese)', 'tc' ); ?></option>
											<option value='cid0kr'><?php _e( 'Arial Unicode MS (Korean)', 'tc' ); ?></option>
											<option value='courier'><?php _e( 'Courier', 'tc' ); ?></option>
											<option value='dejavusans'><?php _e( 'DejaVu Sans', 'tc' ); ?></option>
											<option value='dejavusanscondensed'><?php _e( 'DejaVu Sans Condensed', 'tc' ); ?></option>
											<option value='dejavusansextralight'><?php _e( 'DejaVu Sans ExtraLight', 'tc' ); ?></option>
											<option value='dejavusansmono'><?php _e( 'DejaVu Sans Mono', 'tc' ); ?></option>
											<option value='dejavuserif'><?php _e( 'DejaVu Serif', 'tc' ); ?></option>
											<option value='dejavuserifcondensed'><?php _e( 'DejaVu Serif Condensed', 'tc' ); ?></option>
											<option value='freemono'><?php _e( 'FreeMono', 'tc' ); ?></option>
											<option value='freesans'><?php _e( 'FreeSans', 'tc' ); ?></option>
											<option value='freeserif'><?php _e( 'FreeSerif', 'tc' ); ?></option>
											<option value='helvetica' selected=""><?php _e( 'Helvetica', 'tc' ); ?></option>
											<option value='hysmyeongjostdmedium'><?php _e( 'MyungJo Medium (Korean)', 'tc' ); ?></option>
											<option value='kozgopromedium'><?php _e( 'Kozuka Gothic Pro (Japanese Sans-Serif)', 'tc' ); ?></option>
											<option value='kozminproregular'><?php _e( 'Kozuka Mincho Pro (Japanese Serif)', 'tc' ); ?></option>
											<option value='msungstdlight'><?php _e( 'MSung Light (Traditional Chinese)', 'tc' ); ?></option>
											<option value='pdfacourier'><?php _e( 'PDFA Courier', 'tc' ); ?></option>
											<option value='pdfahelvetica'><?php _e( 'PDFA Helvetica', 'tc' ); ?></option>
											<option value='pdfatimes'><?php _e( 'PDFA Times', 'tc' ); ?></option>
											<option value='stsongstdlight'><?php _e( 'STSong Light (Simplified Chinese)', 'tc' ); ?></option>
											<option value='symbol'><?php _e( 'Symbol', 'tc' ); ?></option>
											<option value='times'><?php _e( 'Times-Roman', 'tc' ); ?></option>
										</select>
									</label>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><label for="document_font_size"><?php _e( 'Document Font Size', 'tc' ); ?></label></th>
								<td>
									<select name="document_font_size">
										<?php
										$i = 0;
										for ( $i = 8; $i <= 40; $i++ ) {
											?>
											<option value="<?php echo $i; ?>" <?php
											if ( $i == 14 ) {
												echo 'selected';
											}
											?>><?php echo $i; ?></option>
													<?php
												}
												?>
									</select>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><label for="document_orientation"><?php _e( 'Document Orientation', 'tc' ); ?></label></th>
								<td>
									<label>
										<input type="radio" name="document_orientation" value="L" checked="checked"><?php _e( 'Landscape', 'tc' ) ?></label>
									<label>
										<input type="radio" name="document_orientation" value="P"><?php _e( 'Portrait', 'tc' ) ?>	</label>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><label for="document_size"><?php _e( 'Document Size', 'tc' ); ?></label></th>
								<td>
									<select name="document_size">
										<option value="A3"><?php _e( 'A3 (297 × 420 mm)', 'tc' ); ?></option>
										<option value="A4" selected="selected"><?php _e( 'A4 (210 × 297)', 'tc' ); ?></option>
										<option value="A5"><?php _e( 'A5 (148 × 210)', 'tc' ); ?></option>
										<option value="A6"><?php _e( 'A6 (105 × 148)', 'tc' ); ?></option>
										<option value="ANSI_A"><?php echo _e( 'ANSI A (216x279 mm)', 'tc' ); ?></option>
									</select>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><label for="document_title"><?php _e( 'Document Title', 'tc' ); ?></label></th>
								<td>
									<input type="text" name='document_title' value='<?php echo esc_attr( __( 'Attendee List', 'tc' ) ); ?>' />
								</td>
							</tr>

						</tbody>
					</table>
				</div>
			</div>

			<p class="submit">
				<input type="submit" name="export_event_data" id="export_event_data" class="button button-primary" value="Export Data">
			</p>
		</form>
	</div>
</div>