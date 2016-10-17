//
// Parallax
//

var scene = document.getElementById('scene');
var parallax = new Parallax(scene);


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
 
  $("#owl-demo").owlCarousel({
 
      autoPlay: 3000, //Set AutoPlay to 3 seconds
 
      items : 4,
      itemsDesktop : [1199,3],
      itemsDesktopSmall : [979,3]
 
  });
 
});


//
// Remove overlay on contact
//


$(".maps-overlay").click(function(){
    $(".parseMap").removeClass("parseMapDisplay");
});



