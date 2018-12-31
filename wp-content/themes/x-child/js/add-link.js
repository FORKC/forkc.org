jQuery('.x-logobar-inner').each(function() {
  	var link = jQuery(this).html();
  	jQuery(this).contents().wrap('<a class="riverfest-cta"href="http://forkc.org/kc-riverfest"></a>');
}); 