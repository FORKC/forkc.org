jQuery('.x-logobar-inner').each(function() {
  	var link = jQuery(this).html();
  	jQuery(this).contents().wrap('<a class="riverfest-cta"href="http://dev.forkc.org/events/kc-riverfest"></a>');
}); 