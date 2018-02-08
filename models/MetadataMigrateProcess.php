<?php
/**
 * PDF Text
 * 
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * @package Omeka\Plugins\MetadataMigrator
 */
class MetadataMigrateProcess extends Omeka_Job_AbstractJob
{
    /**
     * Process all PDF files in Omeka.
     */
    public function perform()

    {
        $logfile = fopen("/home/bitnami/htdocs/plugins/MetadataMigrator/models/logfile.log", "w");
        $MetaDataMigratorPlugin = new MetaDataMigratorPlugin;
        $fileTable = $this->_db->getTable('File');

        
        //first make sure we delete any existing file-level metadata that might be in existence
        
        //get all PDF files
        $selectFiles = $this->_db->select()
        ->from($this->_db->File)
        ->where('mime_type IN (?)', $MetaDataMigratorPlugin->getPdfMimeTypes());


        $metadataOptions = array('no_escape' => true, 'no_filter' => true);
        
        
        //now cycle through them, wiping dc metadata wherever it is
        $pageNumber = 1;
        while ($files = $fileTable->fetchObjects($selectFiles->limitPage($pageNumber, 50))) {
            $pageNumber++;
            foreach ($files as $file) {
                fwrite($logfile, $file->item_id);
                

                $dcTitle = $file->getElement('Dublin Core', 'Title');

                $dcDescription = $file->getElement('Dublin Core', 'Description');

                $dcSource = $file->getElement('Dublin Core', 'Source');

                $dcIdentifier = $file->getElement('Dublin Core', 'Identifier');

                
                $file->deleteElementTextsByElementId(array($dcTitle->id));

                $file->deleteElementTextsByElementId(array($dcDescription->id));
                
                $file->deleteElementTextsByElementId(array($dcSource->id));

                $file->deleteElementTextsByElementId(array($dcIdentifier->id));
                   

                
                //get the parent item 
                

                $Item = $file->getItem();
               
               
                //loop through Item DC metadata, grab the text of the element from the item,
                //attach it to the file
               
                $itemTitle = metadata($Item, array('Dublin Core', 'Title'), $metadataOptions);

                $itemDescription = metadata($Item, array('Dublin Core', 'Description'), $metadataOptions);

                $itemSource = metadata($Item, array('Dublin Core', 'Source'), $metadataOptions);

                $itemIdentifier = metadata($Item, array('Dublin Core', 'Identifier'), $metadataOptions);

                
                $file->addTextForElement(
                    $dcSource,
                    $itemSource
                
                );
                
                $file->addTextForElement(
                    $dcTitle,
                    "PDF File from: $itemTitle"
                
                );
        
                $file->addTextForElement(
                    $dcDescription,
                    $itemDescription
                
                );

                $file->addTextForElement(
                    $dcIdentifier,
                    $itemIdentifier
                
                );
        
            
        
                

                //save changes to file
                $file->save();
                
                //dump the object from memory,
                release_object($Item);
                
                release_object($file);

            }
        
       
        }
        fclose($logfile);
       
    }
}
?>