//**************************************************************
// jQZoom allows you to realize a small magnifier window,close
// to the image or images on your web page easily.
//
// jqZoom version 2.2
// Author Doc. Ing. Renzi Marco(www.mind-projects.it)
// First Release on Dec 05 2007
// i'm looking for a job,pick me up!!!
// mail: renzi.mrc@gmail.com
//**************************************************************

function MouseEvent(e) {
	this.x = e.pageX;
	this.y = e.pageY;
}

(function($){

	var jqZoomCount = 0;
		
	$.fn.jqueryzoom = function(options){

		var settings = {
			xzoom: 300,		//zoomed width default width
			yzoom: 300,		//zoomed div default width
			offset: 40,		//zoomed div default offset
			position: "right" ,//zoomed div default position,offset position is to the right of the image
			lens:1, //zooming lens over the image,by default is 1;
			preload: 1
		};

		if(options) 
		{
			$.extend(settings, options);
		}

		//var bigimage;
		//var smallimage;

		$(this).each(function(){
			smallimage = $(this).children("img").get(0);
			this.smallimage = smallimage;
			this.bigimage = $(smallimage).attr("alt");

			if(settings.preload)
			{
				$('body').append("<div style='display:none;' class='jqPreload"+jqZoomCount+"'>sdsdssdsd</div>");
		
				var content = jQuery('div.jqPreload'+jqZoomCount+'').html();
				jQuery('div.jqPreload'+jqZoomCount+'').html(content+'<img src=\"'+this.bigimage+'\">');
				
				jqZoomCount++;
			}
		})
		.hover(function()
		{

			var self = this;
			var smallimage = this.smallimage;
			var bigimage = this.bigimage;

			if(typeof bigimage == "undefined")
			{
				return;
			}
	
			var offset = $(this).offset();			
			var containerLeft = offset.left;
			var containerTop = offset.top;
			var containerOffsetLeft = $('#wrapperLocation').offset().left;

			var offset = $(smallimage).offset();
			var imageLeft = offset.left;
			var imageTop = offset.top;
			
			
			var imageWidth = smallimage.offsetWidth;
			var imageHeight = smallimage.offsetHeight;

			$(smallimage).attr("alt",'');

			if($(this).next("div.zoomdiv").get().length == 0)
			{
				$(this).after("<div class='zoomdiv'><img class='bigimg' src='"+bigimage+"'/></div>");
				$(this).append("<div class='jqZoomPup'>&nbsp;</div>");
			}

			if(settings.position == "right")
			{
				leftpos = containerLeft + $(this).width() + settings.offset;
				if(leftpos + settings.xzoom > screen.width)
				{
					leftpos = containerLeft - settings.offset - settings.xzoom;
				}
			}
			else
			{
				leftpos = containerLeft - settings.xzoom - settings.offset;
				if(leftpos < 0)
				{
					leftpos = containerLeft + $(this).width() + settings.offset
				}
			}

			var zoomPup = $(this).children("div.jqZoomPup").get(0);
			var zoomdiv = $(this).next().get(0);
			var bigimg = $(zoomdiv).children(".bigimg").get(0);
			
			$(bigimg).load(function(){
				$(zoomdiv).css({ top: containerTop, left: leftpos - containerOffsetLeft});
				$(zoomdiv).width(settings.xzoom);
				$(zoomdiv).height(settings.yzoom);
				$(zoomdiv).show();
		
				var bigwidth = bigimg.offsetWidth;
				var bigheight = bigimg.offsetHeight;
				var scalex = (bigwidth/imageWidth);
				var scaley = (bigheight/imageHeight);
		
				if(settings.lens && (scalex>1 || scaley>1)){
					$(zoomPup).width((settings.xzoom)/scalex );
					$(zoomPup).height((settings.yzoom)/scaley);
					$(zoomPup).css('visibility','visible');
					$(zoomPup).click(function(){$(smallimage).click()});
				}
				else
				{
					$(this).css('cursor','crosshair');
				}
				var zoomPupWidth = $(zoomPup).width();
				var zoomPupHeight = $(zoomPup).height();		
				$(document.body).mousemove(function(e){
	
					mouse = new MouseEvent(e);
					xpos = mouse.x - zoomPupWidth/2 - imageLeft;
					ypos = mouse.y - zoomPupHeight/2 - imageTop ;
					if(settings.lens)
					{
						xpos = (xpos < 0) ? 0 : (xpos > imageWidth - zoomPupWidth) ?(imageWidth - zoomPupWidth -2) : xpos;
						ypos = (ypos < 0) ? 0 : (ypos > imageHeight - zoomPupHeight) ?(imageHeight - zoomPupHeight -2 ) : ypos;
						
						var leftZoomPup = xpos + imageLeft - containerOffsetLeft;
						$(zoomPup).css({ top: ypos + imageTop, left: leftZoomPup});
					}
		
					scrolly = ypos;
					scrollx = xpos;
					zoomdiv.scrollTop = scrolly * scaley;
					zoomdiv.scrollLeft = (scrollx) * scalex;
				});
			});
		},function(){
			var smallimage = this.smallimage;
			var bigimage = this.bigimage;
			$(smallimage).attr("alt", bigimage);
		 	$(document.body).unbind("mousemove");
			if(settings.lens){
				$(this).children("div.jqZoomPup").remove();
			}
		 	$(this).next("div.zoomdiv").remove();
		});
	}

})(jQuery);

// ZOOM
$(document).ready(function(){
	$("img.jqzoom").parent().jqueryzoom();
	$("img.jqzoom").attr('title', '');
});