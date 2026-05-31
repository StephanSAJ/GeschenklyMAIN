<?php
if (!defined('ABSPATH')) exit;
$width_tagline = (yit_get_option('header-logo-tagline') == 'yes') ? 'with_tagline' : 'no-tagline';

// Preload Logo Image
$logo_url = '';
if (function_exists('has_custom_logo') && has_custom_logo()) {
    $custom_logo_id = get_theme_mod('custom_logo');
    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
    $logo_url = $logo[0] ?? '';
} elseif (yit_get_option('header-custom-logo') == 'yes') {
    $logo_url = yit_get_option('header-custom-logo-image');
}

if ($logo_url) {
    echo '<link as="image" href="' . esc_url($logo_url) . '">';
}
?>

<div id="logo" class="<?php echo esc_attr($width_tagline) ?>">
    <?php if (function_exists('has_custom_logo') && has_custom_logo()) : ?>
        <?php
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
        if ($logo) :
            // Inline critical CSS für Logo
           	 echo '<style>
                .custom-logo-link img {
                    display: block;
                    max-width: 100%;
                    height: auto;
                    width: ' . esc_attr($logo[1]) . 'px;
                    aspect-ratio: ' . esc_attr($logo[1]) . ' / ' . esc_attr($logo[2]) . ';
                }
            </style>';
        ?>
            <a href="<?php echo esc_url(home_url('/')) ?>" class="custom-logo-link" rel="home">
                <img src="<?php echo esc_url($logo[0]) ?>"
                     class="custom-logo"
                     alt="<?php echo esc_attr(get_bloginfo('name')) ?>"
                     width="<?php echo esc_attr($logo[1]) ?>"
                     height="<?php echo esc_attr($logo[2]) ?>"
                     loading="eager"
                     decoding="sync"
                     fetchpriority="high"
                     importance="high"
                     data-no-lazy="1">
            </a>
        <?php endif; ?>

    <?php elseif (yit_get_option('header-custom-logo') == 'yes' && ($logo_url = yit_get_option('header-custom-logo-image'))) : ?>
        <?php
        $size = @getimagesize($logo_url);
        $width = $size[0] ?? '';
        $height = $size[1] ?? '';

        // Inline dimensions für Custom Logo
        if ($width && $height) {
            echo '<style>
                #logo-img img {
                    width: ' . esc_attr($width) . 'px;
                    aspect-ratio: ' . esc_attr($width) . ' / ' . esc_attr($height) . ';
                    display: block;
                }
            </style>';
        }
        ?>
        <a id="logo-img" href="<?php echo esc_url(home_url()) ?>">
            <img src="<?php echo esc_url(yit_ssl_url($logo_url)) ?>"
                 <?php if (yit_get_option('logo-retina-url')): ?>
                     srcset="<?php echo esc_url(yit_ssl_url($logo_url)) ?> 1x,
                             <?php echo esc_url(yit_ssl_url(yit_get_option('logo-retina-url'))) ?> 2x"
                 <?php endif; ?>
                 alt="<?php echo esc_attr(get_bloginfo('name')) ?>"
                 <?php if ($width && $height): ?>
                     width="<?php echo esc_attr($width) ?>"
                     height="<?php echo esc_attr($height) ?>"
                 <?php endif; ?>
                 loading="eager"
                 decoding="sync"
                 fetchpriority="high"
                 importance="high"
                 data-no-lazy="1">
        </a>

    <?php else : ?>
        <a id="textual" href="<?php echo esc_url(home_url()) ?>">
            <?php echo wp_kses_post(yit_decode_title(get_bloginfo('name'))) ?>
        </a>
    <?php endif; ?>

    <?php if (yit_get_option('header-logo-tagline') == 'yes'): ?>
        <?php
        $tagline_classes = [];
        if (strpos(get_bloginfo('description'), '|')) {
            $tagline_classes[] = 'multiline';
        }
        if (yit_get_option('header-logo-tagline-mobile') == 'no') {
            $tagline_classes[] = 'hidden-xs';
        }
        ?>
        <p id="tagline"<?php echo !empty($tagline_classes) ? ' class="' . esc_attr(implode(' ', $tagline_classes)) . '"' : '' ?>>
            <?php echo wp_kses_post(yit_decode_title(get_bloginfo('description'))) ?>
        </p>
    <?php endif; ?>
</div>

<?php
// Inline Critical CSS
echo '<style>
    #logo {
        display: block;
        max-width: 100%;
        content-visibility: auto;
        contain-intrinsic-size: auto;
    }
    #logo img {
        display: block;
        max-width: 100%;
        height: auto;
        will-change: transform;
    }
    @media (prefers-reduced-motion: no-preference) {
        #logo img {
            backface-visibility: hidden;
        }
    }
</style>';
?>
