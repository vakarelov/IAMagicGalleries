<?php
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>
<head>
    <link rel="profile" href="https://gmpg.org/xfn/11"/>
    <meta http-equiv="Content-Type"
          content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>"/>

    <title><?php echo wp_get_document_title(); ?></title>

    <link rel="stylesheet" href="<?php bloginfo( 'stylesheet_url' ); ?>" type="text/css" media="screen"/>
    <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>"/>

	<?php if ( file_exists( get_stylesheet_directory() . '/images/kubrickbgwide.jpg' ) ) { ?>
        <style type="text/css" media="screen">

            <?php
			// Checks to see whether it needs a sidebar.
			if ( empty( $withcomments ) && ! is_single() ) {
				?>
            #page {
                background: url("<?php bloginfo( 'stylesheet_directory' ); ?>/images/kubrickbg-<?php bloginfo( 'text_direction' ); ?>.jpg") repeat-y top;
                border: none;
            }

            <?php } else { // No sidebar. ?>
            #page {
                background: url("<?php bloginfo( 'stylesheet_directory' ); ?>/images/kubrickbgwide.jpg") repeat-y top;
                border: none;
            }

            <?php } ?>

        </style>
	<?php } ?>
    <style>
        body, html {
            height: 100%;
            overflow: hidden; /* Hide scrollbars */
            margin: 0;
        }

        .IA_Presenter_Container, .IA_Designer_Container {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 50%;
            left: 50%;
            /* bring your own prefixes */
            transform: translate(-50%, -50%);
            /*vertical-align: top;*/
        }

        #wpadminbar {
            opacity: .3;
        }

        #wpadminbar:hover {
            opacity: 1;
        }
    </style>
	<?php //wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div id="page">

</div>
<?php
//the_content();
echo IAMG_posttype::render_post();
?>
<div id="footer" role="contentinfo">
    <?php wp_footer(); ?>
</div>
</body>
</html>
