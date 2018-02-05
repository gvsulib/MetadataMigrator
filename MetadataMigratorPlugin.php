<?php
/**
 * PDF Text
 * 
 * @copyright Copyright 2018 Kyle Felker
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The MetadataMigrator plugin.
 * 
 * @package Omeka\Plugins\MetadataMigrator
 */
class MetadataMigratorPlugin extends Omeka_Plugin_AbstractPlugin
{

    protected $_hooks = array(
        'install',
        'uninstall',
        'config_form',
        'config',
        'before_save_file',
    );

    protected $_pdfMimeTypes = array(
        'application/pdf',
        'application/x-pdf',
        'application/acrobat',
        'text/x-pdf',
        'text/pdf',
        'applications/vnd.pdf',
    );

    /**
     * Install the plugin.
     */
    public function hookInstall()
    {
        // Don't install if the pdftotext command doesn't exist.
        // See: http://stackoverflow.com/questions/592620/check-if-a-program-exists-from-a-bash-script
        if ((int) shell_exec('hash pdftotext 2>&- || echo 1')) {
            throw new Omeka_Plugin_Installer_Exception(__('The pdftotext command-line utility ' 
            . 'is not installed. pdftotext must be installed to install this plugin.'));
        }
        // Don't install if a PDF element set does not exist.
        if (!$this->_db->getTable('ElementSet')->findByName('PDF Text')) {
            throw new Omeka_Plugin_Installer_Exception(__('An element set by the name "PDF text" does not ' 
            . 'exist. You must install the PdfToText plugin to use this plugin.'));
        }
        
    }

    /**
     * Uninstall the plugin
     */
    public function hookUninstall()
    {
        // Delete all file-level metadata this plugin creates
        $dataBase = get_db();
        $fileTable = $dataBase->getTable('File');
        
        $mimeTypes = $this->getPdfMimeTypes();

        $dcElementSet = $dataBase->getTable('ElementSet')->findByName('Dublin Core');

        $dcElements = $dcElementSet->getElements();

        $select = $dataBase->select()
        ->from($dataBase->File)
        ->where('mime_type IN (?)', $mimeTypes);
        $pageNumber = 1;
        while ($files = $fileTable->fetchObjects($select->limitPage($pageNumber, 50))) {
            foreach ($files as $file) {
                foreach ($dcElements as $dcElement) {

                    $file->deleteElementTextsByElementId(array($dcElement->id));
                
                }
            }
            $pageNumber++;
        }

        
    }

    
    
    /**
     * Display the config form.
     */
    public function hookConfigForm()
    {
        echo get_view()->partial(
            'plugins/metadata-migrate-config-form.php'
            
        );
    }

    /**
     * Handle the config form.
     */
    public function hookConfig()
    {
        // Run the migration process if directed to do so.
        if ($_POST['metadata_process']) {
            Zend_Registry::get('bootstrap')->getResource('jobs')
                ->sendLongRunning('MetadataMigrateProcess');
        }
    }

    /**
     * Add the PDF text to the file record.
     * 
     * This has a secondary effect of including the text in the search index.
     */

    
    public function hookBeforeSaveFile($args)
    {
        $file = $args['record'];
        // Move Metadata only on file insert.
        if (!$args['insert']) {
            return;
        }

        // Ignore non-PDF files.
        if (!in_array($file->mime_type, $this->_pdfMimeTypes)) {
            return;
        }

        //get the item table
        
        $dataBase = get_db();
        $itemTable = $dataBase->_db->getTable('Item');

        //now get the item record

        $select = $dataBase->select()
        ->from($dataBase->Item)
        ->where('id = ?', $file->item_id);
        
        $Item = $itemTable->fetchObject($select);

        $dcElementSet = $dataBase->getTable('ElementSet')->findByName('Dublin Core');

        $dcElements = $dcElementSet->getElements();

        $metadataOptions = array('no_escape' => true, 'no_filter' => true);

        foreach ($dcElements as $dcElement) {

            $itemMetadataText = metadata($Item, array('Dublin Core', $dcElement->name), $metadataOptions);
            
 

        }

    
        release_object($Item);
        
        
    }
    

    /**
     * Get the PDF MIME types.
     * 
     * @return array
     */
    public function getPdfMimeTypes()
    {
        return $this->_pdfMimeTypes;
    }
}
?>