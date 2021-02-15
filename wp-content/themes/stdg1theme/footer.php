			<!-- footer -->
			<footer>
				<div class="container">
					<?php
						wp_nav_menu([
							'theme_location' => 'footer-menu',
							'container' => 'div',
							'menu_class' => 'footer-menu'
						]);
					?>
				</div>
			</footer>

		</div>
		
		
		<!-- Javascript files -->
		<!-- jQuery -->
		
        <?=wp_footer()?>
	</body>	
</html>