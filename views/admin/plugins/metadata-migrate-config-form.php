<div class="field">
    <div id="metadata_process_label" class="two columns alpha">
        <label for="metadata_process"><?php echo __('Process existing PDF item records'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
        <?php 
        echo __(
            'This plugin copies metadata from the item to the file level '
            . 'for file records representing PDF documents.  It\'s designed to be used  '
            . 'in tandem with the PDf Text Capture plugin.  It copies all DC metadata. '
            . 'Check the box below and submit '
            . 'this form to run the initial migration process, which may '
            . 'take some time to finish.');
        ?>
        </p>
        
        <?php echo $this->formCheckbox('metadata_process'); ?>

        
       
    </div>
</div>
