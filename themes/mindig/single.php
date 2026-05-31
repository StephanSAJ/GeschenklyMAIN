<?php
/**
 * Template File for YIT Framework
 *
 * This file belongs to the YIT Framework.
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package Yithemes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Load the header template
get_header(); ?>

<main id="primary-content" role="main">
    <?php 
    /**
     * Hook: yit_primary
     * Used for rendering primary content in the template.
     */
    do_action( 'yit_primary' ); 
    ?>
</main>

<?php
// Load the footer template
get_footer(); 
