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
		
		<!-- Styles -->
		<!-- Bootstrap CSS -->
		<link href="<?=get_template_directory_uri()?>/css/bootstrap.min.css" rel="stylesheet">
		<!-- Font awesome CSS -->
		<link href="<?=get_template_directory_uri()?>/css/font-awesome.min.css" rel="stylesheet">		
		<!-- Custom CSS -->
		<link href="<?=get_template_directory_uri()?>/css/style.css" rel="stylesheet">
        
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
						<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
							<ul class="nav navbar-nav navbar-right">
								<li><a href="registration.html">Signup</a></li>
								<li><a href="login.html">Login</a></li>
								<li class="dropdown">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown">Menu <span class="caret"></span></a>
									<ul class="dropdown-menu" role="menu">
										<li><a href="#event">Events</a></li>
										<li><a href="#blog">New Blogs</a></li>
										<li><a href="#subscribe">Subscribe</a></li>
										<li><a href="#team">Executive Team</a></li>
										<li><a href="#">One more separated link</a></li>
									</ul>
								</li>
							</ul>
						</div><!-- /.navbar-collapse -->
					</div><!-- /.container-fluid -->
				</nav>
			</header>