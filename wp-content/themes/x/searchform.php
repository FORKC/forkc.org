<?php

// =============================================================================
// SEARCHFORM.PHP
// -----------------------------------------------------------------------------
// Template for displaying search forms in X.
// =============================================================================

$input_atts = apply_filters( 'x_search_input_atts', array(
  'class' => 'search-query',
  'placeholder' => __( 'Search', '__x__' )
) );

?>

<form method="get" id="searchform" class="form-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
  <label for="s" class="visually-hidden"><?php _e( 'Search', '__x__' ); ?></label>
  <input type="text" id="s" name="s" <?php echo x_atts( $input_atts ); ?> />
</form>
