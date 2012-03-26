<script type="text/javascript">
	var cutpoints = [<?php echo $viewbag->CutPoints ?>];
</script>

<div class="menu">
	<strong>Actions</strong><br /> <a href="?action=selectMovie">Select
		new movie</a><br /> <a
		href="?startAgain=true&amp;useCommercialBreaks=true">Load commercial
		breaks</a><br />
	
        <?php if($viewbag->NumberCutpoints > 0) { ?>
        <a href="?clearCutlist=true">Clear cutlist</a><br/>        
        <?php } ?>
        <a href="?action=saveToDB">Save cutlist to DB</a><br/>

        <div id="frameactions" style="display:none">
            <p>
            <a href="javascript:expandLeft(1)">All frames left</a><br/>
            <a href="javascript:expandRight(1)">All frames right</a><br/>
	    <br/>
            <a href="javascript:expandLeft()">More frames left</a><br/>
            <a href="javascript:expandRight()">More frames right</a><br/><br/>

	   		<strong>Cutpoint editing</strong><br/>
            <a href="javascript:cutLeft()">Cut left</a><br/>
            <a href="javascript:cutRight()">Cut right</a><br/>
            <a id="del_cutpoint" href="javascript:deleteCutpoint()" style="display:none">Delete cutpoint</a>

<!--	    <a href="javascript:moveCutpoint(-1)" title="Move cut start">Move cut start</a><br/>
	    <a href="javascript:moveCutpoint(1)" title="Move cut end">Move cut end</a><br/> -->
	    <a href="javascript:moveCutpoint(0)" title="Move the nearest cutpoint (if any) to this position">Move cutpoint</a><br/>
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
	<img alt="Moviestripe" src="?action=moviestripe" width="140"/>
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
		
		
		
		
    <?php
	if(trim($viewbag->Subtitle) == "") {
    ?>


	<form method="post"><p class="subtitle">Change subtitle: <input type="text" name="subtitle" size="50"/><input type="submit" value="Set" name="change_subtitle"/></p></form>
    <?php } else { 
      echo html($viewbag->Subtitle);
    } ?>
    <p class="description"><?php echo html($viewbag->Description) ?></p>


    </div>

	<div class="movielist">
		<span>
		<?php
		$template = '<a title="{mark}" name="o{mark}" href="javascript:selected({mark})"><img alt="" class="{class}" width="{width}" height="{height}" src="{url}" id="img{mark}"/></a>';
		$viewbag->List->printItems($template, $viewbag->Thumbnailer);
		?> </span>
	</div>
</div>
