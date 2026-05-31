<!DOCTYPE html>
<html <?php language_attributes(); ?> prefix="og: http://ogp.me/ns#">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#e9a400">
    <link rel="icon" sizes="32x32" href="https://geschenkly.de/wp-content/uploads/2017/08/favicon-1.png">
    <link rel="icon" sizes="192x192" href="https://geschenkly.de/wp-content/uploads/2017/08/favicon-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://geschenkly.de/wp-content/uploads/2017/08/apple-icon.png">
   <!-- <link rel="profile" href="https://gmpg.org/xfn/11">  -->
   <!-- <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">  -->
	<link rel="preload" href="https://geschenkly.de/wp-content/fontawesome/css/all.css" as="style">
<link rel="stylesheet" href="https://geschenkly.de/wp-content/fontawesome/css/all.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://geschenkly.de/wp-content/fontawesome/css/all.css"></noscript>

    <!-- Matomo Tracking -->
    <script>
        var _paq = window._paq = window._paq || [];
        _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        _paq.push(['setTrackerUrl', 'https://geschenklyanalytics.de/matomo/matomo.php']);
        _paq.push(['setSiteId', '1']);
        (function() {
            var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
            g.async=true; g.defer=true; g.src='https://geschenklyanalytics.de/matomo/matomo.js'; 
            s.parentNode.insertBefore(g,s);
        })();
    </script>
    <noscript>
        <p>
            <img 
                referrerpolicy="no-referrer-when-downgrade" 
                src="https://geschenklyanalytics.de/matomo/matomo.php?idsite=1&amp;rec=1" 
                style="border:0;" 
                alt="" 
                loading="lazy"
            />
        </p>
    </noscript>
    <!-- End Matomo Tracking -->

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?> id="home">
    <?php do_action('yit_header'); ?>
</body>
</html>
