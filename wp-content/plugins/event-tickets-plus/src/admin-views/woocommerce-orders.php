<div class="wrap tribe-attendees-page">
	<div id="icon-edit" class="icon32 icon32-tickets-orders"><br></div>

	<div id="tribe-attendees-summary" class="welcome-panel">
		<div class="welcome-panel-content">
			<div class="welcome-panel-column-container">

				<div class="welcome-panel-column welcome-panel-first">
					<h3><?php esc_html_e( 'Event Details', 'event-tickets-plus' ); ?></h3>
					<ul>
						<?php
						/**
						 * Provides an action that allows for the injections of fields at the top of the order report details meta ul
						 *
						 * @var $event_id
						 */
						do_action( 'tribe_tickets_plus_report_event_details_list_top', $event_id );

						/**
						 * Provides an action that allows for the injections of fields at the bottom of the order report details ul
						 *
						 * @var $event_id
						 */
						do_action( 'tribe_tickets_plus_report_event_details_list_bottom', $event_id );
						?>
					</ul>

					<?php
					/**
					 * Fires after the event details list (in the context of the WooCommerce Orders admin view).
					 *
					 * @param WP_Post      $event
					 * @param bool|WP_User $organizer
					 */
					do_action( 'tribe_tickets_plus_after_event_details_list', $event, $organizer );
					?>

				</div>
				<div class="welcome-panel-column welcome-panel-middle">
					<h3><?php esc_html_e( 'Sales by Ticket', 'event-tickets-plus' ); ?></h3>
					<?php
					foreach ( $tickets_sold as $ticket_sold ) {

						//Only Display if a WooCommerce Ticket otherwise kick out
						if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' != $ticket_sold['ticket']->provider_class ) {
							continue;
						}

						$price        = '';
						$sold_message = '';

						if ( ! $ticket_sold['has_stock'] ) {
							$sold_message = sprintf( __( 'Sold %d', 'event-tickets-plus' ), esc_html( $ticket_sold['sold'] ) );
						} else {
							$sold_message = sprintf( __( 'Sold %d of %d', 'event-tickets-plus' ), esc_html( $ticket_sold['sold'] ), esc_html( $ticket_sold['ticket']->capacity() ) );
						}

						if ( $ticket_sold['ticket']->price ) {
							$price = ' (' . tribe_format_currency( number_format( $ticket_sold['ticket']->price, 2 ), $event_id ) . ')';
						}

						$sku = '';
						if ( $ticket_sold['sku'] ) {
							$sku = 'title="' . sprintf( esc_html__( 'SKU: (%s)', 'event-tickets-plus' ), esc_html( $ticket_sold['sku'] ) ) . '"';
						}
						?>
						<div class="tribe-event-meta tribe-event-meta-tickets-sold-itemized">
							<strong <?php echo $sku; ?>><?php echo esc_html( $ticket_sold['ticket']->name . $price ); ?>:</strong>
							<?php
							echo esc_html( $sold_message );
							?>
						</div>
						<?php
					}
					?>
				</div>
				<div class="welcome-panel-column welcome-panel-last alternate">

					<?php

					if ( $total_sold ) {
						$total_sold = absint( $total_sold );
					}; ?>

					<div class="totals-header">
						<h3>
							<?php
							$totals_header = sprintf(
								__( 'Total Sales: %1$s (%2$s)', 'event-tickets-plus' ),
								tribe_format_currency( number_format( $event_revenue, 2 ), $event_id ),
								$total_sold
							);
							echo esc_html( $totals_header );
							?>
						</h3>
					</div>

					<div id="sales_breakdown_wrapper" class="tribe-event-meta-note">
						<div>
							<strong><?php esc_html_e( 'Completed:', 'event-tickets-plus' ); ?></strong>
							<?php echo esc_html( tribe_format_currency( number_format( $tickets_breakdown['wc-completed']['_line_total'], 2 ), $event_id ) ); ?>
							<span id="total_issued">(<?php echo esc_html( $tickets_breakdown['wc-completed']['_qty'] ); ?>)</span>
						</div>
						<div>
							<strong><?php esc_html_e( 'Processing:', 'event-tickets-plus' ); ?></strong>
							<?php echo esc_html( tribe_format_currency( number_format( $tickets_breakdown['wc-processing']['_line_total'], 2 ), $event_id ) ); ?>
							<span id="total_pending">(<?php echo esc_html( $tickets_breakdown['wc-processing']['_qty'] ); ?>)</span>
						</div>
						<div>
							<strong><?php esc_html_e( 'Pending Payment:', 'event-tickets-plus' ); ?></strong>
							<?php echo esc_html( tribe_format_currency( number_format( $tickets_breakdown['wc-pending']['_line_total'], 2 ), $event_id ) ); ?>
							<span id="total_pending">(<?php echo esc_html( $tickets_breakdown['wc-pending']['_qty'] ); ?>)</span>
						</div>
						<div>
							<strong><?php esc_html_e( 'Canceled:', 'event-tickets-plus' ); ?></strong>
							<?php echo esc_html( tribe_format_currency( number_format( $tickets_breakdown['wc-cancelled']['_line_total'], 2 ), $event_id ) ); ?>
							<span id="total_issued">(<?php echo esc_html( $tickets_breakdown['wc-cancelled']['_qty'] ); ?>)</span>
						</div>
						<div>
							<strong><?php esc_html_e( 'Discounts:', 'event-tickets-plus' ); ?></strong>
							<?php
							$sign = $discounts > 0 ? '-' : '';
							echo esc_html( $sign . tribe_format_currency( number_format( $discounts, 2 ), $event_id ) );
							?>
						</div>
					</div>

					<?php
					if ( $event_fees ) {
						?>
						<div class="tribe-event-meta tribe-event-meta-total-ticket-sales">
							<strong><?php esc_html_e( 'Total Ticket Sales:', 'event-tickets-plus' ) ?></strong>
							<?php echo esc_html( tribe_format_currency( number_format( $event_sales, 2 ), $event_id ) ); ?>
						</div>
						<div class="tribe-event-meta tribe-event-meta-total-site-fees">
							<strong><?php esc_html_e( 'Total Site Fees:', 'event-tickets-plus' ) ?></strong>
							<?php echo esc_html( tribe_format_currency( number_format( $event_fees, 2 ), $event_id ) ); ?>
							<div class="tribe-event-meta-note">
								<?php
								echo apply_filters( 'tribe_events_orders_report_site_fees_note', '', $event, $organizer );
								?>
							</div>
						</div>
						<?php
					}//end if
					?>
				</div>
			</div>
		</div>
	</div>

	<form id="topics-filter" method="get">
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'page' : 'tribe[page]' ); ?>" value="<?php echo esc_attr( isset( $_GET['page'] ) ? $_GET['page'] : '' ); ?>"/>
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'event_id' : 'tribe[event_id]' ); ?>" id="event_id" value="<?php echo esc_attr( $event_id ); ?>"/>
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'post_type' : 'tribe[post_type]' ); ?>" value="<?php echo esc_attr( $event->post_type ); ?>"/>
		<?php echo $table; ?>
	</form>
</div>
