<form action<?php echo "" id<?php echo "Movies" method<?php echo "post">

	<div class<?php echo "main">
		<div class<?php echo "title">Select movie</div>

		<div class<?php echo "content">
			<select name<?php echo "Movie">
			<?php
			foreach($viewbag->Movies as $v) {
				echo sprintf('<option value<?php echo "%s">%s (%s, %s, %.1f GB)</option>',
				$v->Value,
				html($v->Title),
				html($v->Subtitle),
				html($v->Starttime),
			 $v->Size);
			}
			?>
			</select> <input type<?php echo "submit" name<?php echo "submit" value<?php echo "Select" /> <input
				type<?php echo "hidden" name<?php echo "action" value<?php echo "selectMovie" />

			<p>
				Search: <input type<?php echo "text" name<?php echo "search"
					value<?php echo "<?php echo html($viewbag->Query); ?>" /><br /> Series: <select
					name<?php echo "series">
					<option value<?php echo "">All...</option>
					
					
        <?php
        foreach($viewbag->Series as $v) {
            echo sprintf("<option %s value<?php echo \"%s\">%s (%d recordings, %.1f GB)</option>",
                         $v->Selected ?'selected<?php echo "selected"' : '',
			 html($v->Title),
                         html($v->Title), 
		         $v->Recordings,
			 $v->Size);
        }
        ?>
        </select><br /> <input type<?php echo "checkbox" name<?php echo "skip_cutted"
					value<?php echo "J" <?php echo $viewbag->SkipCutted; ?' checked':'' ?> /> Skip movies
				with existing cutlists<br /> <input type<?php echo "submit" name<?php echo "submit2"
					value<?php echo "Filter list" />
			</p>
		</div>
	</div>
</form>
