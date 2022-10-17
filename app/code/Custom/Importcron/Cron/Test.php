<?php

namespace Custom\Importcron\Cron;

/**
* Class Test
* @package Custom\Importcron\Cron
**/

class Test{
	/**
	* @return $this
	* @throws \Zend_Log_Exception
	**/
	 private $logger;

     public function __construct(\Psr\Log\LoggerInterface $logger)
     {
         $this->logger = $logger;
     }
	
	public function execute(){
		
		try {
			//$this->logger->info("my custom Cron job executed at".date("d/m/Y h:i:s",time()));
			
			
			$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
			$zendLogger = new \Zend_Log();
			$zendLogger->addWriter($writer);
			$zendLogger->info("1. my custom Cron job executed at ".date("d/m/Y h:i:s",time()));
			
			
			$file = fopen(BP .'/var/import/product.csv', 'r', '"'); // set path to the CSV file
			
			
			if ($file !== false)
			{
				
				require 'app/bootstrap.php';
				$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
				$objectManager = $bootstrap->getObjectManager();
				$state = $objectManager->get('Magento\Framework\App\State');
				$state->setAreaCode('adminhtml');
				$stockRegistry = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface');
				
			
				/*$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/import-product.log');
				$logger = new \Zend_Log(); 
				$logger->addWriter($writer);*/
				
				$header = fgetcsv($file); // get data headers and skip 1st row
				$required_data_fields = 3;
			
				while ($row = fgetcsv($file, 3000, ",") )
				{
					$data_count = count($row);
					if ($data_count < 1)
					{
						continue;
					}
					$product = $objectManager->create('Magento\Catalog\Model\Product');         
					$data = array();
					$data = array_combine($header, $row);
			
					//$sku_qty_arr = $data['sku_qty'];
					$sku_qty_arr = explode("|",$data['sku_qty']);
					$sku = $sku_qty_arr[0];
					
					//$sku = $data['sku_qty'];
					if ($data_count < $required_data_fields)
					{
						$zendLogger->info("Skipping product sku " . $sku . ", not all required fields are present to create the product.");
						continue;
					}
			
					$name = $data['name'];
					$description = $data['description'];
					$shortDescription = $data['short_description'];
					//$qty = trim($data['qty']);
					$qty = trim($sku_qty_arr[1]);
					$price = trim($data['price']);
					
					$zendLogger->info($sku."my custom Cron job executed at ".date("d/m/Y h:i:s",time()));
					
					try
					{
						$product->setTypeId('simple') // product type
								->setStatus(1) // 1 = enabled
								->setAttributeSetId(4)
								->setName($name)
								->setSku($sku)
								->setPrice($price)
								->setTaxClassId(0) // 0 = None
								->setCategoryIds(array(2, 3)) // array of category IDs, 2 = Default Category
								->setDescription($description)
								->setShortDescription($shortDescription)
								->setWebsiteIds(array(1)) // Default Website ID
								->setStoreId(0) // Default store ID
								->setVisibility(4) // 4 = Catalog & Search
								->save();
			
					}
					catch (\Exception $e)
					{
						$zendLogger->info('Error importing product sku: '.$sku.'. '.$e->getMessage());
						continue;
					}
					try
					{
						$stockItem = $stockRegistry->getStockItemBySku($sku);
			
						if ($stockItem->getQty() != $qty)
						{
							$stockItem->setQty($qty);
							if ($qty > 0)
							{
								$stockItem->setIsInStock(1);
							}
							$stockRegistry->updateStockItemBySku($sku, $stockItem);
						}
					}
					catch (\Exception $e)
					{
						$zendLogger->info('Error importing stock for product sku: '.$sku.'. '.$e->getMessage());
						continue;
					}
					unset($product);
				}
				fclose($file);
			}else{
				$zendLogger->info("Import Failed");
			}
			
			//$writer = new \Zend\Log\Writer\Stream('D:\xampp\htdocs\magento4.1\var\log\custom.log');
		  	
			/*$this->logger->addWriter($writer);
	  		$this->logger->info('Your text message');*/
			
			//$writer = new \Zend_Log_Writer_Stream('D:\xampp\htdocs\magento4.1\var\log\custom.log');
			//$this->addWriter($writer);
			
			/*$logger = new \Zend_Log();
			$logger->addWriter($writer);
			$logger->info('my custom Cron job executed at ');*/
			//$this->logger->info("my custom Cron job executed at".date("d/m/Y h:i:s",time()));
		
             //do something
        } catch (\Exception $e) {
             //$zendLogger->critical('Error message', ['exception' => $e]);
        }
		 
		
		
		/*date_default_timezone_set('Asia/Kolkata');
		$timezone = date_default_timezone_get();
		$date = date("d/m/Y h:i:s",time());
		
		$writer = new \Zend_Log_Writer_Stream(BP.'/var/log/custom.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);
		$logger->info('my custom Cron job executed at '.$date.' '.$timezone);
		return $this;*/
		
	}
	
}

?>