    var currentSelected = "nosuchitem";
    function selected(el) {
        var item = $("#img"+el);
        var current = $("#img"+currentSelected);

        if(currentSelected == el) {
            current.removeClass().addClass(isCutpoint(el)?"cutpoint":"normal");
            currentSelected = "nosuchitem";
            frameActions(null);
        } else {
            item.removeClass().addClass("selected");
            current.removeClass().addClass("normal");
            currentSelected = el;
            frameActions(el);
        }
    }

    var selectedFrame = null;
    function frameActions(frame) {
        selectedFrame = frame;

        if(frame === null) {
            $("#frameactions").css("display", "none");
        } else {
            $("#frameactions").css("display", "");
            $("#del_cutpoint").css("display", isCutpoint(frame)?"":"none");
        }
    }

  function expandLeft(allframes) {
    location.href = "?expandLeft=" + selectedFrame + '&amp;all=' + allframes;
  }

  function expandRight(allframes) {
    location.href = "?expandRight=" + selectedFrame + '&amp;all=' + allframes;
  }

  function cutLeft() {
    location.href = "?cutLeft=" + selectedFrame;
  }

  function cutRight() {
    location.href = "?cutRight=" + selectedFrame;
  }

  function deleteCutpoint() {
    location.href = "?deleteCutpoint=" + selectedFrame;
  }

  function moveCutpoint() {
    location.href = "?moveCutpoint=" + selectedFrame;
  }

  function isCutpoint(frame) {
      for(var i in cutpoints) {
        if(cutpoints[i] == frame) return true;
      }
      return false;
  }

if(!console) {
	console = {
		log: function() {
		}
	};
}
