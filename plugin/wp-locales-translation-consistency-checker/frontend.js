(function($) {
	$( document ).ready(
		function() {
			$( 'button.wp-locales-translation-consistency-checker' ).on(
				'click',
				function(e) {
					var $parent = $( this ).closest( 'tr' );
					e.preventDefault();
					$.ajax(
						{
							url: $( this ).closest( 'table' ).data( 'ajaxurl' ),
							data: {
								action: 'wp_locales_translation_consistency_checker_mark_done',
								id: $( this ).data( 'id' ),
								_wpnonce: $( this ).data( 'nonce' )
							},
							type: "post",
							success: function(response) {
								if (response.success) {
									$parent.detach();
								}
							},
						}
					);
					return false;
				}
			);
		}
	);
}(jQuery));
