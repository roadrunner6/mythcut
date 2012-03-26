<div class="main">
	<div class="title">MythCut Fatal Error</div>

	
		<strong><?php echo html($viewbag->ErrorTitle) ?></strong>
		<pre>
		<?php echo $viewbag->Error ?>
		</pre>
		
		<br/>
		<pre>
		<?php print_r($viewbag->Stacktrace) ?>
		</pre>
	
</div>
