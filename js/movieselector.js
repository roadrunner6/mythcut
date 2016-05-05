function editMovie(selector) {
        location.href="?action=movie&selectedMovie=" + selector + '&init=true';
}

function page(url) {
       updateMovieList(url);
}

var baseURL = '?action=json&call=getMovieList';
function updateMovieList(url) {
      if(!url)
            url = baseURL;
	url += '&skipTranscoded=' + ($("#skipTranscoded").is(":checked")?1:0);
	url += '&skipHasCutlist=' + ($("#skipHasCutlist").is(":checked")?1:0);
	url += '&skipLiveTV=' + ($("#skipLiveTV").is(":checked")?1:0);
	url += '&series=' + $("#episodeFilter").val();
	url += '&hpp=' + $("#hpp").val();

        $.ajax({
        url: url,
        success: function(data) {
                $("#moviecontainer").html('');
                $(".pagelist").html('');

		$("#seriestemplate").tmpl(data.data.Movies).appendTo("#moviecontainer");

                // render pagelist
                $("#totalhitstemplate").tmpl(data.data).appendTo(".pagelist");
                $("#pagelisttemplate").tmpl(data.data.PageList).appendTo(".pagelist");
	
		// change form values
		$("#search").attr("value", data.data.Params.search);
		$("#skipTranscoded").attr("checked", data.data.Params.skipTranscoded == 1);
		$("#skipHasCutlist").attr("checked", data.data.Params.skipHasCutlist == 1);
		$("#skipLiveTV").attr("checked", data.data.Params.skipLiveTV == 1);
		$("#hpp").val(data.data.Params.hpp);
		$("#episodeFilter").val(data.data.Params.series);
		$("#episodeShownTitle").html(data.data.Params.series);
		$("#episodeShown").css("display", data.data.Params.series != '' ? '' : 'none');
        }
        });
}

function doSearch() {
        var query = $("#search").first().val();
        updateMovieList(baseURL + '&search=' +query);
}

function filterSeries(name) {
	$("#episodeFilter").attr("value", encodeURIComponent(name));
        updateMovieList(baseURL);
}

function showAllMovies() {
	$("#episodeFilter").attr("value", "");
	updateMovieList();
}


$(document).ready(function() {
        updateMovieList(baseURL + '&restore=true');
});

