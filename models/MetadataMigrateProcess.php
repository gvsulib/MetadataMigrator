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
        $itemTable = $this->_db->getTable('Item');
        

        
        //first make sure we delete any existing file-level metadata that might be in existence
        
        //get all PDF files
        $selectFiles = $this->_db->select()
        ->from($this->_db->File)
        ->where('mime_type IN (?)', $MetaDataMigratorPlugin->getPdfMimeTypes());

        //now cycle through them, wiping text capture wherever it is
        $pageNumber = 1;
        while ($files = $fileTable->fetchObjects($selectFiles->limitPage($pageNumber, 50))) {
            
            foreach ($files as $file) {
            

                //get title and description metadata
                $dcTitle = $file->getElement(
                    "Dublin Core",
                    "Title"
                );
                $dcDescription = $file->getElement(
                    "Dublin Core",
                    "Description"
                );
                //wipe it
                $file->deleteElementTextsByElementId(array($dcTitle->id));
                $file->deleteElementTextsByElementId(array($dcDescription->id));

                //now get the parent file and pull it's metadata
                $selectItem = $this->_db->select()
                    ->from($this->_db->Item)
                    ->where('id = ?', $file->item_id);

                $Item = $itemTable->fetchObject($selectItem);

                $ElementTexts = $Item->getAllElementTexts();

                foreach ($ElementTexts as $ElementText) {
                    if ($ElementText->element_id == $dcTitle->id) {
                        $itemTitle = $ElementText->text;
                    } else if ($ElementText->element_id == $dcDescription->id) {
                        $itemDescription = $ElementText->text;
                    }


                }
        
                
                //add it to the file

                
                $file->addTextForElement(
                    $dcTitle,
                    "PDF Document from: $itemTitle"
                    
                );
        
                $file->addTextForElement(
                    $dcDescription,
                    $itemDescription
                    
                );
                $file->save();
                
                //dump the object from memory, save changes to the file
                release_object($Item);
                
                release_object($file);

            }
            $pageNumber++;
        }
       fclose($logfile);
        
    }
}
