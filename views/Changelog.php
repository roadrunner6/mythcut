<div class="main">
	<div class="title">MythCut Changelog</div>
	
	

	<?php
	foreach($viewbag->Releases as $release) {
		echo sprintf("<p><strong>%s</strong>: %s<br/><ul>",
		             html($release->Version),
					 html($release->Date));
		foreach($release->Items as $v) {
			echo "<li>";
			echo $v;
			echo "</li>";
		}
		
		echo "</ul></p>";
	}
	?>
	
	<p>
	<a href="?">Back</a>
	</p>	
</div>
