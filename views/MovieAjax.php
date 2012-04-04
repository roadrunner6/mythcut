<script type="text/javascript" src="js/movieAjax.js"></script>
<script type="text/javascript">
	var cutpoints = [<?php echo $viewbag->CutPoints ?>];
</script>

<div class="menu" style="display:none" id="actionmenu">
	<strong>Actions</strong><br /> <a href="?action=selectMovie">Select new
		movie</a><br /> <a
		href="?action=movie&startAgain=true&amp;useCommercialBreaks=true">Load commercial
		breaks</a><br />
	
	
        <a style="display:none" id="btnClearCutlist" href="?action=movie&clearCutlist=true">Clear cutlist</a><br/>        
        <a href="?action=saveToDB">Save cutlist to DB</a><br/>

        <div id="frameactions" style="display:none">
            <p>
            <a href="javascript:tnAction('expandLeft', 'all=1')">All frames left</a><br/>
            <a href="javascript:tnAction('expandRight', 'all=1')">All frames right</a><br/>
	    <br/>
            <a href="javascript:tnAction('expandLeft', '')">More frames left</a><br/>
            <a href="javascript:tnAction('expandRight', '')">More frames right</a><br/><br/>

	   		<strong>Cutpoint editing</strong><br/>
            <a href="javascript:tnAction('cutLeft', '')">Cut left</a><br/>
            <a href="javascript:tnAction('cutRight', '')">Cut right</a><br/>
            <a id="del_cutpoint" href="javascript:tnAction('deleteCutpoint', '')">Delete cutpoint</a><br/>

<!--	    <a href="javascript:moveCutpoint(-1)" title="Move cut start">Move cut start</a><br/>
	    <a href="javascript:moveCutpoint(1)" title="Move cut end">Move cut end</a><br/> -->
	    <a href="javascript:tnAction('moveCutpoint','')" title="Move the nearest cutpoint (if any) to this position">Move cutpoint</a><br/>
            </p>
        </div>

        <br/><br/>
	<div class="cutlist">
       <?php
	foreach($viewbag->CutList as $v) {
		echo $v->Timestamp;
		echo " ";
                echo sprintf('<a href="#o%1$d">%1$d-</a>', $v->Left);
                echo sprintf('<a href="#o%1$d">%1$d</a>', $v->Right);
		echo "<br/>";
	}
       ?>
	<img alt="Moviestripe" id="moviestripe" src="?action=moviestripe" width="140"/>
	</div>

        <p>
        <strong>Information</strong><br/>
        Length: <?php echo $viewbag->Length ?><br/>
        Size: <?php echo $viewbag->Size ?><br/>
        Channel: <?php echo $viewbag->Channel ?><br/>
        Starttime: <?php echo $viewbag->Starttime ?><br/>
        </p>
    </div>

<div class="main">
	<div class="title movielist">
	<?php echo html($viewbag->Title) ?>
		<br />
	<?php echo html($viewbag->Subtitle) ?>
    <p class="description"><?php echo html($viewbag->Description) ?></p>	
    </div>
	
	<div class="movielist">
		<span id="thumbnails">
		</span>
	</div>
</div>
<br/>
