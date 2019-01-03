<?php $meta_object = tribe( 'tickets-plus.main' )->meta(); ?>
<button class="accordion-header tribe_attendee_meta">
	<?php esc_html_e( 'Attendee Information', 'event-tickets-plus' ); ?>
</button>
<section class="accordion-content">
	<h4 class="accordion-label screen_reader_text"><?php esc_html_e( 'Attendee Information:', 'event-tickets-plus' ); ?></h4>
	<p class="tribe-intro"><?php esc_html_e( 'Need to collect information from your ticket buyers? Configure your own registration form with the options below.', 'event-tickets-plus' ); ?></p>
	<?php include tribe( 'tickets-plus.main' )->plugin_path . 'src/admin-views/meta.php'; ?>
</section>
