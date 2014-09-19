<?php
if(_meta('description')) 	$lc_metaDescription = _meta('description');
if(_meta('keywords')) 	 	$lc_metaKeywords = _meta('keywords');

$ogTitle 	= (_meta('og:title')) ? _meta('og:title') : $lc_siteName;
$ogURL 		= (_meta('og:url')) ? _meta('og:url') : _url();
$ogType 	= (_meta('og:type')) ? _meta('og:type') : 'website';
$ogImage 	= (_meta('og:image')) ? _meta('og:image') : _img('logo-200x200.jpg');
$ogDesc 	= (_meta('og:description')) ? _meta('og:description') : $lc_metaDescription;
$ogSiteName = (_meta('og:site_name')) ? _meta('og:site_name') : $lc_siteName;

$twCard 	= (_meta('twitter:card')) ? _meta('twitter:card') : 'summary';
$twSite 	= (_meta('twitter:site')) ? _meta('twitter:site') : current(explode('.', $lc_siteDomain));
$twTitle 	= (_meta('twitter:title')) ? _meta('twitter:title') : $lc_siteName;
$twDesc 	= (_meta('twitter:description')) ? _meta('twitter:description') : $lc_metaDescription;
$twImage 	= (_meta('twitter:image')) ? _meta('twitter:image') : _img('logo-120x120.jpg');
?>
<link rel="canonical" href="<?php echo _canonical(); ?>" />
<?php _hreflang(); ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="<?php echo $lc_metaDescription; ?>" />
<meta name="keywords" content="<?php echo $lc_metaKeywords; ?>" />
<!-- Facebook -->
<meta property="og:title" content="<?php echo $ogTitle; ?>" />
<meta property="og:url" content="<?php echo $ogURL; ?>" />
<meta property="og:type" content="<?php echo $ogType; ?>" />
<meta property="og:image" content="<?php echo $ogImage; ?>" />
<meta property="og:description" content="<?php echo $ogDesc; ?>" />
<meta property="og:site_name" content="<?php echo $ogSiteName; ?>" />
<!-- Twitter -->
<meta name="twitter:card" content="<?php echo $twCard; ?>">
<meta name="twitter:site" content="@<?php echo $twSite; ?>">
<meta name="twitter:title" content="<?php echo $twTitle; ?>">
<meta name="twitter:description" content="<?php echo $twDesc; ?>">
<meta name="twitter:image" content="<?php echo $twImage; ?>">

<link rel="shortcut icon" href="<?php echo _img('favicon.ico'); ?>" type="image/x-icon" />
<?php _css('base.css'); ?>
<?php _css('base.'._lang().'.css'); ?>
<?php _js('jquery-1.7.1.min.js'); ?>
<?php _js('jquery-ui-1.8.17.custom.js'); ?>
<?php _script(); ?>
<?php _js('LC.js'); ?>
<?php _js('site.js'); ?>