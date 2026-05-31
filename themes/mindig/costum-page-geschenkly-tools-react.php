<?php
/*
Template Name: Geschenkly Externe Tools
*/

// Entfernen Sie unerwünschte Aktionen und Filter hier, falls nötig
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

get_header(); // Lädt den Header Ihres Themes
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            max-width: 100%;
            background-color: #f5f5f5;
        }
        .full-width-container {
            width: 100% !important;
            max-width: 1200px !important;
            margin: 0 auto !important;
            padding: 20px !important;
        }
        .react-app-wrapper {
            width: 100%;
            max-width: 100%;
            margin: 20px 0;
            padding: 30px;
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .page-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        /* Entfernen Sie alle einschränkenden Styles */
        .row, .container, .content-area, .site-content {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: auto !important;
            margin-right: auto !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        @media (max-width: 768px) {
            .full-width-container {
                padding: 10px !important;
            }
            .react-app-wrapper {
                padding: 15px;
            }
        }
    </style>
</head>
<body <?php body_class('full-width-page'); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
    <div id="content" class="site-content">
        <div id="primary" class="content-area">
            <main id="main" class="site-main" role="main">
                <div class="full-width-container">
                    <?php
                    while ( have_posts() ) :
                        the_post();
                        ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <div class="entry-content">
                                <div class="react-app-wrapper">
                                    <?php the_content(); ?>
                                </div>
                            </div>
                        </article>
                    <?php
                    endwhile;
                    ?>
                </div>
            </main>
        </div>
    </div>
</div>
<?php wp_footer(); ?>
</body>
</html>
