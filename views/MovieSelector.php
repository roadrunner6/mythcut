<script type="text/javascript">

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
	
	$.ajax({
	url: url,
	success: function(data) {
		$("#moviecontainer").html('');
		$(".pagelist").html('');        
		for(var i in data.data.Movies) {			
			$("#seriestemplate").tmpl(data.data.Movies[i]).appendTo("#moviecontainer");
		}


		// render pagelist
		$("#pagelisttemplate").tmpl(data.data.PageList).appendTo(".pagelist");
	}
	});
}

function doSearch() {
	var query = $("#search").first().val();
	updateMovieList(baseURL + '&search=' +query);
}

function filterSeries(name) {
	updateMovieList(baseURL + '&series=' + name);
}

$(document).ready(function() {		
	updateMovieList();
});
</script>

<div class="main">
	<div class="title">Select movie</div>
</div>

<div class="searchbox">
	<input type="text" name="search" id="search" value=""/>
	<button onClick="doSearch()">Seach</button>
</div>

<div class="pagelist right"></div>
<div class="clear">
<br/>
</div>

<div id="moviecontainer">
</div>

<div class="pagelist right"></div> 
<div class="clear">
<br/>
<br/>
</div>

<div id="templates" style="display:none">    
	<div id="seriestemplate">
		<div>
			<div class="series">
				<span class="seriestitle">${Title}</span> : ${Subtitle}<br/><div class="moviedescription">${Description}</div>
				Channel: ${Channel}, Size: ${FilesizeGB} GB{{if IsSeries}},
				<a href="#" onClick="filterSeries('${Title}')" title="View all episodes of this series">Episodes</a>: ${Episodes.NumEpisodes}, Total size: ${Episodes.FilesizeGB} GB 
				{{else}}
				{{/if}}
				<div class="right">
					<a onclick="editMovie('${Selector}')" href="#">Edit movie</a>
				</div>
			</div>			
		</div>
	</div>

	<div id="pagelisttemplate">
	    {{if href.length}}
	       <a title="Goto page ${Page}" href="#" onClick="page('${href}')">${Label}</a>	        
	    {{else}}
	       ${Label}
	    {{/if}}
	    &nbsp;
	</div>	
</div>
