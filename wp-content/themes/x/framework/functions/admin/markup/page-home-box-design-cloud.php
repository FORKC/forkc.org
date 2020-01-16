<?php

// =============================================================================
// FUNCTIONS/GLOBAL/ADMIN/ADDONS/MARKUP/PAGE-HOME-BOX-SUPPORT.PHP
// -----------------------------------------------------------------------------
// Addons home page output.
// =============================================================================

// =============================================================================
// TABLE OF CONTENTS
// -----------------------------------------------------------------------------
//   01. Page Output
// =============================================================================

// Page Output
// =============================================================================

?>

<div class="tco-column">
  <div class="tco-box tco-box-min-height tco-box-design-cloud">

    <header class="tco-box-header">
      <?php echo $status_icon_dynamic; ?>
      <h2 class="tco-box-title"><?php _e( 'Design Cloud', '__x__' ); ?></h2>
    </header>

    <div class="tco-box-content">
      <ul class="tco-box-features">
        <li>
          <?php x_tco()->admin_icon( 'layout', 'tco-box-feature-icon' ); ?>
          <div class="tco-box-feature-info">
            <h4 class="tco-box-content-title"><?php _e( 'Sites', '__x__' ); ?></h4>
            <span class="tco-box-content-text"><?php _e( 'Complete designs. Done for you.', '__x__' ); ?></span>
          </div>
        </li>
        <li>
          <?php x_tco()->admin_icon( 'layers', 'tco-box-feature-icon' ); ?>
          <div class="tco-box-feature-info">
            <h4 class="tco-box-content-title"><?php _e( 'Templates', '__x__' ); ?></h4>
            <span class="tco-box-content-text"><?php _e( 'Individual assets to use anywhere.', '__x__' ); ?></span>
          </div>
        </li>
        <li>
          <?php x_tco()->admin_icon( 'cog', 'tco-box-feature-icon' ); ?>
          <div class="tco-box-feature-info">
            <h4 class="tco-box-content-title"><?php _e( 'Automatic', '__x__' ); ?></h4>
            <span class="tco-box-content-text"><?php _e( 'Install with one click.', '__x__' ); ?></span>
          </div>
        </li>
      </ul>
      <?php if ( $is_validated && function_exists('CS') ) : ?>
        <a class="tco-btn" href="<?php echo CS()->common()->get_app_route_url('design-cloud'); ?>" target="_blank"><?php _e( 'Launch Design Cloud', '__x__' ); ?></a>
      <?php else : ?>
        <?php x_validation()->preview_unlock( '.tco-box-design-cloud', __( 'Get Design Cloud', '__x__' )); ?>
      <?php endif; ?>
    </div>

    <footer class="tco-box-footer">
      <div class="tco-box-bg" style="background-image: url(<?php x_tco()->admin_image( 'box-design-cloud-tco-box-bg.jpg' ); ?>);"></div>
      <?php if ( ! $is_validated ) : ?>
        <?php x_validation()->preview_overlay( '.tco-box-design-cloud' ); ?>
      <?php endif; ?>
    </footer>

  </div>
</div>
