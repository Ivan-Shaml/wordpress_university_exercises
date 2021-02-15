<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title><?=get_bloginfo('name')?></title>
		<!-- Description, Keywords and Author -->
		<meta name="description" content="Your description">
		<meta name="keywords" content="Your,Keywords">
		<meta name="author" content="ResponsiveWebInc">
		
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<!-- Favicon -->
		<link rel="shortcut icon" href="#">
        <?=wp_head()?>
	</head>
	
	<body>
	
		<div class="wrapper">
		
			<!-- header -->
			<header>
				<!-- navigation -->
				<nav class="navbar navbar-default" role="navigation">
					<div class="container">
						<!-- Brand and toggle get grouped for better mobile display -->
						<div class="navbar-header">
							<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
								<span class="sr-only">Toggle navigation</span>
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>
							</button>
							<a class="navbar-brand" href="#"><img class="img-responsive" src="img/logo.png" alt="Logo" /></a>
						</div>

						<!-- Collect the nav links, forms, and other content for toggling -->
						<?php wp_nav_menu([
							'theme_location' => 'header-menu',
							'container_class' => 'collapse navbar-collapse',
							'container_id' => 'bs-example-navbar-collapse-1',
							'menu_class' => 'nav navbar-nav navbar-right',
							'walker' => new HeaderWalkerNavMenu()
							]); ?>
						
					</div><!-- /.container-fluid -->
				</nav>
			</header>