
	function loadThumbnails(url_params) {
		var callUrl  = '?action=json&call=get-thumbnails';
		if(url_params) {
			callUrl += '&' + url_params;
		}
				
		$.ajax({
			url: callUrl,
			success: function(data) {
				var tpl = '<a title="{mark}" id="o{mark}" name="o{mark}" href="javascript:selected({mark})"><img style="background:url(misc/prepare.jpg)" alt="" class="{class} moviethumbnail" width="{width}" height="{height}" src="{href}" id="img{mark}"/></a>';
				if(data.success) {
					//console.log("Processing data");
					cutpoints = [];
					var lastMark = null;
					for(var i in data.data) {
						var html = tpl;
						var item = data.data[i];

						var mark = item.mark;
						var el = $("#o" + mark);
				
						if(item.class=="cutpoint") {
							cutpoints[cutpoints.length] = parseInt(item.mark);
						}
						
						if(el.length != 0) {
							var el1 = el.first();							
							el1.attr('class', item.class);			
							$("img", el1).attr("class", item.class);
						} else {
						    var e = $("<a>").attr({
						    	                    class: item.class,
													title: item.seconds,
													id: "o" + item.mark,
													name: "o" + item.mark,													
                                             		href: "javascript:selected(" + item.mark + ")"
								    	    })
                                .append($("<img>").attr({
			                                    class: item.class,
			                                    width: item.w,
			                                    height: item.h,
			                                    src: item.href,
			                                    style: "background:url(misc/prepare.jpg)",	
			                                    id: "img" + item.mark,
			                                    alt: item.seconds
			                            }));

							if(lastMark === null) {
								//console.log("Inserting item at start of list: o" + mark);
								$("#thumbnails").append(e);
							} else {
								//console.log("Inserting item o" + mark + " after o" + lastMark);
								$("#o" + lastMark).after(e);								
							}
						}							

						lastMark = mark; 				
					}

					$("#btnClearCutlist").attr("style", "display:" + (cutpoints.length > 0?'block':'none'));
					$("#moviestripe").first().removeAttr("src").attr("src", "?action=moviestripe&rand=" + Math.random());				    
					$("#actionmenu").attr("style", "display:block");
				}
			}

		});
	}	

	function tnAction(action, params) {
		if(selectedFrame) {
			var p = escape(action) + '=' + selectedFrame;
			if(params) {
				p += '&' + params;
			}

			loadThumbnails(p);
		}
	}
	
	$(document).ready(function() {
		loadThumbnails();
	});
