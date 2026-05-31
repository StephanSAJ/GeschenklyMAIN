<?php
/**
 * Your Inspiration Themes
 *
 * This file contains a collection of functions useful for the core
 * of the framework.
 *
 * @package    WordPress
 * @subpackage Your Inspiration Themes
 * @author     Your Inspiration Themes Team <info@yithemes.com>
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

$footer_type = apply_filters( 'yit_footer_type', yit_get_option( 'footer-type' ) );

if ( $footer_type && $footer_type !== 'none' ) : ?>
    <div id="footer-copyright-group">
        <?php if ( strpos( $footer_type, 'big' ) !== false ) : ?>
            <?php
            /**
             * Fires when a big footer type is used.
             * 
             * @hooked yit_footer_big - Displays the big footer layout.
             */
            do_action( 'yit_footer_big' );
            ?>
        <?php endif; ?>

        <?php do_action( 'yit_before_copyright' ); ?>
        
        <!-- START COPYRIGHT -->
        <div id="copyright">
            <div class="container">
                <div class="border">
                    <div class="row fluid">
                        <?php
                        /**
                         * Fires in the copyright area.
                         * 
                         * @hooked yit_copyright - Outputs the copyright text or content.
                         */
                        do_action( 'yit_copyright' );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- END COPYRIGHT -->
        
        <?php do_action( 'yit_after_copyright' ); ?>
    </div>
<?php endif; ?>
