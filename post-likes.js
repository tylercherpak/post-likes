(function($){
  $(document).ready( function() {
    $( '.like' ).click(function(e){
      e.preventDefault();
      var element = $('.like');
      var post_id = element.data('post-id');
      if( element.data( 'like-toggle' ) == 'like' ) {
        var data = {
          action: 'post_like',
          nonce: ajax_object.ajax_nonce,
          post_id: post_id
        };
        $.post( ajax_object.ajax_url, data, function( response ) {
          if (typeof response.success != 'undefined') {
            if ( response.success == true ) {
              element.data('like-toggle', 'unlike');
              element.text(function () {
                return $(this).text().replace('click to like', 'click to unlike');
              });
            }
          }
        });
      }else if( element.data( 'like-toggle' ) == 'unlike' ){
        var data = {
          action: 'post_unlike',
          nonce: ajax_object.ajax_nonce,
          post_id: post_id
        };
        $.post( ajax_object.ajax_url, data, function( response ) {
          if (typeof response.success != 'undefined') {
            if ( response.success == true ) {
              element.data('like-toggle', 'like');
              element.text(function () {
                return $(this).text().replace('click to unlike', 'click to like');
              });
            }
          }
        });
      }
    });
  });
})(jQuery);