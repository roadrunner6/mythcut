<form action="" method="get">
	<div class="main">
		<div class="title">
		<?php echo html($viewbag->Title) ?>
		</div>

		<div class="movielist">
			<input type="hidden" name="action" value="saveToDB" /> <input
				type="hidden" name="chosen" value="true" /> Schedule transcoding: <input
				type="checkbox" name="transcode" value="1" /><br />
			
				<input type="submit" name="Continue" value="Proceed to list" />
		</div>
	</div>
</form>
