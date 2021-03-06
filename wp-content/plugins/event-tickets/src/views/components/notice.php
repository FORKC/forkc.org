<?php
/**
 * View: Notice
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/components/notice.php
 *
 * See more documentation about our views templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.11.0
 *
 */

$notice_classes  = [ 'tribe-tickets__notice' ];
$content_classes = [
	'tribe-common-b2',
	'tribe-tickets-notice__content'
];
$id              = $this->get( 'id' );
$title           = $this->get( 'title' ) ?: '';
$content         = $this->get( 'content' );
$c_classes       = $this->get( 'content_classes' ) ?: [];
$n_classes       = $this->get( 'notice_classes' ) ?: [];
$notice_classes  = array_merge( $notice_classes, (array) $n_classes );
$content_classes = array_merge( $content_classes, (array) $c_classes );

?>
<div id="<?php echo esc_attr( $id ); ?>" <?php tribe_classes( $notice_classes ); ?>>
	<h3 class="tribe-common-h7 tribe-tickets-notice__title"><?php echo esc_html( $title ); ?></h3>

	<div <?php tribe_classes( $content_classes ); ?>>
		<?php echo wp_kses_post( $content ); ?>
	</div>
</div>
