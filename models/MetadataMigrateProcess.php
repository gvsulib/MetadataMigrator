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
               
        $MetaDataMigratorPlugin = new MetaDataMigratorPlugin;
        $fileTable = $this->_db->getTable('File');
        $itemTable = $this->_db->getTable('Item');
        

        
        //first make sure we delete any existing file-level metadata that might be in existence
        
        //get all PDF files
        $selectFiles = $this->_db->select()
        ->from($this->_db->File)
        ->where('mime_type IN (?)', $MetaDataMigratorPlugin->getPdfMimeTypes());

        

        //get the DC metadata set
        $dcElementSet = $this->_db->getTable('ElementSet')->findByName('Dublin Core');
        
        $dcElements = $dcElementSet->getElements();

        $metadataOptions = array('no_escape' => true, 'no_filter' => true);
        

        //now cycle through them, wiping dc metadata wherever it is
        $pageNumber = 1;
        while ($files = $fileTable->fetchObjects($selectFiles->limitPage($pageNumber, 50))) {
            
            foreach ($files as $file) {
               
                
                //first delete all existing DC metadata
                foreach ($dcElements as $dcElement) {
                    
                    $file->deleteElementTextsByElementId(array($dcElement->id));
                   
                }
                $file->save();
                release_object($file);
               
            }
            $pageNumber++;
        }
        //now cycle through again, this time attaching metadata
        
        $pageNumber = 1;
        while ($files = $fileTable->fetchObjects($selectFiles->limitPage($pageNumber, 50))) {
            $pageNumber++;
            
            
            foreach ($files as $file) {
            
                //get the parent item 
                $selectItem = $this->_db->select()
                    ->from($this->_db->Item)
                    ->where('id = ?', $file->item_id);

                $Item = $itemTable->fetchObject($selectItem);
               
               
                //loop through Item DC metadata, grab the text of the elment from the item,
                //attach it to the file

                
                foreach ($dcElements as $dcElement) {
                    $itemMetadataText = metadata($Item, array('Dublin Core', $dcElement->name), $metadataOptions);
                    

                    if ($dcElement->name == "Title") {

            
                        $file->addTextForElement(
                            $dcElement,
                            "PDF File from: $itemMetadataText"
                        
                        );
                    } else {
                        $file->addTextForElement(
                            $dcElement,
                            $itemMetadataText
                        
                        );
                    }

                }
                //save changes to file
                $file->save();
                
                //dump the object from memory,
                release_object($Item);
                
                release_object($file);

            }
        
       
        }
        
        
        
       
    }
}
?>