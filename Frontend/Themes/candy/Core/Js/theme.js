//
// Parallax
//

var scene = document.getElementById('scene');
var parallax = new Parallax(scene);


//
// Navigation scroll
//

$("nav a").click(function(evn){
    evn.preventDefault();
    $('html,body').scrollTo(this.hash, this.hash); 
});

//
// Image scroll speed 
//

var MoveItItem = function(el){
  this.el = $(el);
  this.speed = parseInt(this.el.attr('data-scroll-speed'));
};

$.fn.moveIt = function(){
  var $window = $(window);
  var instances = [];
  
  $(this).each(function(){
    instances.push(new MoveItItem($(this)));
  });
  
  window.onscroll = function(){
    var scrollTop = $window.scrollTop();
    instances.forEach(function(inst){
      inst.update(scrollTop);
    });
  };
};

MoveItItem.prototype.update = function(scrollTop){
  var pos = scrollTop / this.speed;
  this.el.css('transform', 'translateY(' + pos + 'px)');
};

$(function(){
  $('[data-scroll-speed]').moveIt();
});




//
// Gallery Slider
//
$(document).ready(function() {
 
  $("#owl-gallery").owlCarousel({
 
      autoPlay: 3000, //Set AutoPlay to 3 seconds
 
      items : 4,
      itemsDesktop : [1199,3],
      itemsDesktopSmall : [979,3]
 
  });
 
});


//
// Gallery overlay
//

// Click on item

// if ($(window).width() > 600){ 
// }

  $("#owl-gallery .item").click(function(){
      var galleryImage = $('img', this).attr("src");
      var sequence = $(this).attr("data-sequence");
    
      $('.gallery-overlay-image').attr("src", galleryImage);
      $('.gallery-overlay').attr("data-sequence", sequence);

      $('.gallery-overlay').addClass('displayBlock');
      $('.gallery-overlay').removeClass('displayNone');
   
  });


// Click on clickregistration 

$(".clickRegistration").click(function(){
  $('.gallery-overlay').addClass('displayNone');
  $('.gallery-overlay').removeClass('displayBlock');
});



// Close button

$(".gallery-overlay-close").click(function(){
  $('.gallery-overlay').addClass('displayNone');
  $('.gallery-overlay').removeClass('displayBlock');
});



// previous image

$(".gallery-overlay-left").click(function() {
  
  var qnty = $("#owl-gallery .item").size();
  var theone = $('.gallery-overlay').attr("data-sequence");
  var theprev = 0;

  if (theone > 1)
  {
    theprev = theone - 1;
  }

  else
  {
    theprev = qnty;
  }
  
  var newGalleryImage = $("#owl-gallery .item[data-sequence='" + theprev + "'] img").attr("src");

  $('.gallery-overlay').attr("data-sequence", theprev);
  $('.gallery-overlay-image').attr("src", newGalleryImage);

});


// next image


$(".gallery-overlay-right").click(function() {
  
  var qnty = $("#owl-gallery .item").size();
  var theone = $('.gallery-overlay').attr("data-sequence");
  var thenext = $("#owl-gallery .item").size();

  if (theone < thenext)
  {
    thenext = parseInt(theone) + 1 ;
  }

  else
  {
    thenext = 1;
  }
  
  var newGalleryImage = $("#owl-gallery .item[data-sequence='" + thenext + "'] img").attr("src");

  $('.gallery-overlay').attr("data-sequence", thenext);
  $('.gallery-overlay-image').attr("src", newGalleryImage);


  console.log(qnty);
  console.log(theone);
  console.log(thenext);

});



//
// Overview slider
//

$(document).ready(function() {
 
  $("#owl-overview").owlCarousel({
 
      autoPlay: 14300, //Set AutoPlay to 3 seconds
 
      items : 3,
      itemsDesktop : [1199,3],
      itemsDesktopSmall : [979,3]
 
  });
 
});


// Same height

equalheight = function(container){

var currentTallest = 0,
     currentRowStart = 0,
     rowDivs = new Array(),
     $el,
     topPosition = 0;
 $(container).each(function() {

   $el = $(this);
   $($el).height('auto')
   topPostion = $el.position().top;

   if (currentRowStart != topPostion) {
     for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
       rowDivs[currentDiv].height(currentTallest);
     }
     rowDivs.length = 0; // empty the array
     currentRowStart = topPostion;
     currentTallest = $el.height();
     rowDivs.push($el);
   } else {
     rowDivs.push($el);
     currentTallest = (currentTallest < $el.height()) ? ($el.height()) : (currentTallest);
  }
   for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
     rowDivs[currentDiv].height(currentTallest);
   }
 });
}

$(window).load(function() {
  equalheight('#owl-overview .owl-item');
});


$(window).resize(function(){
  equalheight('#owl-overview .owl-item');
});




//
// Remove overlay on contact
//


$(".maps-overlay").click(function(){
    $(".parseMap").removeClass("parseMapDisplay");
});


//
// Cookiebar
//

$("#cookieBarClose").click(function(){
    $("#cookieBar").addClass("removeCookie");
});





