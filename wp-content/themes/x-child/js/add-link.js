jQuery('.x-logobar-inner').each(function() {
  	var link = jQuery(this).html();
  	jQuery(this).contents().wrap('<a href="http://www.no-where.net/forkc/River-Fest"></a>');
}); 