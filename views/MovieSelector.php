<script type="text/javascript" src="js/movieselector.js"></script>

<form id="mainform" method="post" action="">

<div class="main">
	<div class="title">Select movie</div>
</div>

<div class="searchbox">

	Items/Page: <select id="hpp">
	<option selected="selected" value="10">10</option>
	<option value="25">25</option>
	<option value="50">50</option>
	<option value="100">100</option>
	</select>

	&nbsp;
	Skip transcoded movies: <input type="checkbox" id="skipTranscoded" value="1" checked="checked"/>
	Skip movies with cutlists: <input type="checkbox" id="skipHasCutlist" value="1" checked="checked"/>
	
	Search: 
	<input type="text" name="search" id="search" value=""/>
	<button onclick="doSearch(); return false">Search</button>
	<br/>
	<div id="episodeShown" style="display:none">
	Showing only episodes of this series: <b><span id="episodeShownTitle"/></b> <a href="javascript:showAllMovies()">Show all movies</a>
	<input type="hidden" id="episodeFilter" value=""/>
	</div>
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
				<a href="#" onclick="filterSeries('${Title}')" title="View all episodes of this series">Episodes</a>: ${Episodes.NumEpisodes}, Total size: ${Episodes.FilesizeGB} GB 
				{{else}}
				{{/if}}
				<div class="right">
					<a onclick="editMovie('${Selector}')" href="#">Edit movie</a>
				</div>
				${formatDate(Date)}
			</div>			
		</div>
	</div>

	<div id="pagelisttemplate">
	    {{if href.length}}
	       <a title="Goto page ${Page}" href="#" onclick="page('${href}')">${Label}</a>	        
	    {{else}}
	       ${Label}
	    {{/if}}
	    &nbsp;
	</div>	

        <div id="totalhitstemplate">
	    Movies: ${TotalHits} (${Pages} pages)
	    &nbsp;
	    &nbsp;
	    &nbsp;
        </div>
</div>
</form>
