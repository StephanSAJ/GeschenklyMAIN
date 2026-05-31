<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!-- START NAVIGATION -->
<div id="nav" class="nav">
    <?php
    if ( has_nav_menu( 'nav' ) ) {
        include_once YIT_THEME_ASSETS_PATH . '/lib/Walker_Nav_Menu_Div.php';
        $nav_args = array(
            'theme_location' => 'nav',
            'container'      => false,
            'menu_class'     => 'level-1 clearfix',
            'depth'          => apply_filters( 'yit_main_nav_depth', 3 ),
            'walker'         => new YIT_Walker_Nav_Menu_Div(),
        );
        wp_nav_menu( $nav_args );
    }
    ?>
</div>
<!-- END NAVIGATION -->

<a href="https://geschenkly.de/erlebnisgeschenke/" class="anna-support">
    Erlebnisgeschenke 2026
</a>

<?php if ( is_active_sidebar( 'Mobile Sidebar' ) ) : ?>
    <!-- MOBILE SIDEBAR -->
    <div class="mobile-sidebar hidden">

        <?php dynamic_sidebar( 'Mobile Sidebar' ); ?>

    </div>
    <!-- END MOBILE SIDEBAR -->
<?php endif; ?>


