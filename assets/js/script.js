var timer;

$(document).ready(function(){
  
  $(".result").on("click", function() {

    var id = $(this).attr("data-linkId");
    var url = $(this).attr("href");
    
    if(!id){
      alert("data-linkId attribute not found");
    }

    increaseLinkClicks(id, url);

    return false; // don't do default behavior which link is go to another page
  });

  var $container = $('.imageResults');
  $container.on('layoutComplete', function() {
    $(".gridItem img").css("visibility", "visible");
  });
  $container.masonry({
    itemSelector: '.gridItem',
    columnWidth: 220,
    gutter: 20
  });

  $("[data-fancybox]").fancybox({
    caption: function( instance, item ) {
      var caption = $(this).data('caption') || '';
      var siteUrl = $(this).data('siteurl') || '';

      if (item.type === 'image'){
        caption = (caption.length ? caption + '<br />' : '')
         + '<a href="' + item.src + '">View image</a><br>'
         +  '<a href="' + siteUrl + '">Visit page</a>';
      }

      return caption;
    },
    afterShow: function( instance, item ) {
      increaseImageClicks(item.src);
    }
    
  });

});

function loadImage(src, className) {
  
  var image = $("<img>");

  image.on("load", function(){
    $("." + className + " a").append(image); // with a puts it in anchor tag instead of class

    clearTimeout(timer);

    timer = setTimeout(function() {
      $(".imageResults").masonry();
    }, 500);
  });

  image.on("error", function(){
    // console.log("Broken");
    $("." + className).remove();

    $.post("ajax/setBroken.php", {src:src});
  });

  image.attr("src", src);
}

function increaseLinkClicks(linkId, url){

  $.post("ajax/updateLinkCount.php", {linkId: linkId})
  .done(function(result) {
    if(result != ""){
      alert(result);
      return;
    }
    
    window.location.href = url;
  });

}

function increaseImageClicks(imageUrl){

  $.post("ajax/updateImageCount.php", {imageUrl: imageUrl})
  .done(function(result) {
    if(result != ""){
      alert(result);
      return;
    }
  });

}