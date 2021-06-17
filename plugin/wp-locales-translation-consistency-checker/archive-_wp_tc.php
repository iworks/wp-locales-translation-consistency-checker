<?php
get_header();
if ( have_posts() ) {
	echo '<header class="page-header alignwide">';
	the_archive_title( '<h1 class="page-title">', '</h1>' );
	echo '</header>';
	echo '<article>';
	echo '<div class="entry-content">';
	printf( '<table data-ajaxurl="%s">', admin_url( 'admin-ajax.php' ) );
	// Load posts loop.
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	echo '</table>';
	echo '</div>';
	echo '</article>';
	the_posts_pagination();
}
get_footer();


