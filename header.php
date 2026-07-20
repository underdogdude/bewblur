<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<?php $bodyClass = ''; if (is_active_sidebar( 'headbar_d' )) { $bodyClass = 'headbar-d'; } if (is_active_sidebar( 'headbar_m' )) { $bodyClass .= ' headbar-m'; } ?>

<body <?php body_class($bodyClass); ?>>
    <?php 
    if ( function_exists( 'wp_body_open' ) ) {
        wp_body_open();
    } else {
        do_action( 'wp_body_open' );
    } ?>
    <div id="page-transition-overlay" class="page-transition-overlay is-covering" aria-hidden="true">
        <div class="page-transition-overlay__row page-transition-overlay__row--top">
            <span class="page-transition-overlay__panel"></span>
        </div>
        <div class="page-transition-overlay__row page-transition-overlay__row--bottom">
            <span class="page-transition-overlay__panel"></span>
        </div>
    </div>
    <a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'seed' ); ?></a>
    <div id="page" class="site">

        <header id="masthead" class="site-header _heading">
            <div class="s-container -wide">

                <div class="site-branding">
                    <div class="site-logo"><?php seed_logo(); ?></div>
                </div>

                <div class="site-toggle"><b></b></div>

                <nav id="site-navigation" class="site-nav-d _desktop">
                    <?php wp_nav_menu( array( 'theme_location' => 'primary', 'menu_id' => 'primary-menu' ) );?>
                </nav>

                <?php
                $cta_link = function_exists( 'get_field' ) ? get_field( 'cta_link', 'option' ) : false;
                if ( $cta_link ) :
                    $cta_target = ! empty( $cta_link['target'] ) ? $cta_link['target'] : '_self';
                ?>
                <a href="<?php echo esc_url( $cta_link['url'] ); ?>" class="site-cta" target="<?php echo esc_attr( $cta_target ); ?>"<?php echo '_blank' === $cta_target ? ' rel="noopener noreferrer"' : ''; ?>>
                    <span class="site-cta__inner">
                        <span class="site-cta__label"><?php echo esc_html( $cta_link['title'] ); ?></span>
                        <span class="site-cta__icon"><?php seed_icon( 'arrow-right' ); ?></span>
                    </span>
                </a>
                <?php endif; ?>

            </div>
            <nav id="site-nav-m" class="site-nav-m">
                <div class="s-container">
                    <?php wp_nav_menu( array( 'theme_location' => 'mobile', 'menu_id' => 'mobile-menu' ) ); ?>
                </div>
            </nav>
        </header>

        <div class="s-modal -full" data-s-modal="site-search">
            <span class="s-modal-close"><?php seed_icon('x'); ?></span>
            <?php get_search_form(); ?>
        </div>

        <div class="site-header-space"></div>

        <?php 
		if (is_front_page()) {
			if (is_active_sidebar( 'home_banner' )) {
				echo '<div class="home-banner">'; dynamic_sidebar( 'home_banner' ); echo '</div>';
			} 
		} else {
			if (is_active_sidebar( 'page_banner' )) {
				echo '<div class="page-banner">'; dynamic_sidebar( 'page_banner' ); echo '</div>';
			}
		}
		?>

        <div id="content" class="site-content">