<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ImportCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {--user=} {--password=} {--product=} {--customer=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'used to import the data from given url into database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // getting option value from the command
        $user           =   $this->option('user');
        $password       =   $this->option('password');
        $product_link   =   $this->option('product');
        $customer_link  =   $this->option('customer');

        try{
            // generate the url with user details
            $strProductCsvUrl   =   $this->generateUrl($product_link, $user, $password);
            $strCustomerCsvUrl  =   $this->generateUrl($customer_link, $user, $password);

            // getting data from product table url
            $arrProductData     =   $this->getDataFromUrl($strProductCsvUrl);
            // getting data from customer table url
            $arrCustomerData    =   $this->getDataFromUrl($strCustomerCsvUrl);
        }
        catch(\Exception $e){
            // log the error while getting data from the given url
            $this->logImportCsv('error: '.$e->getMessage(),'error');
            $this->error('Error in getting data from URL.');
        }
        
        $intTotalProductData    =   count($arrProductData);
        $intTotalCustomerData   =   count($arrCustomerData);
        $intProductInsertData   =   0;
        $intCustomerInsertData  =   0;

        // if data in the product csv exist
        if($intTotalProductData > 0){
            foreach ($arrProductData as $arrData) {
                try{
                    Product::insert([
                        'id' => $arrData[0],
                        'product_name' => $arrData[1],
                        'price' => $arrData[2],
                    ]);
                    $intProductInsertData++;
                    $this->info('Product Inserted with id: '.$arrData[0]);
                }
                catch(\Exception $e){
                    // log the error details while inserting into database
                    $this->logImportCsv('Error in inserting data into Product table. Description: '.$e->getMessage(),'error');
                    $this->error('Error in inserting data into Product with id: '.$arrData[0]);
                }
            }
        }

        $arrLog = [
            'table' => 'Product',
            'no_of_imported_data' => $intProductInsertData,
            'no_of_error_in_data' => $intTotalProductData-$intProductInsertData,
        ];
        // log the product import data details
        $this->logImportCsv($arrLog);
        
        // if the data exist in customer csv
        if($intTotalCustomerData > 0){
            foreach ($arrCustomerData as $arrData) {
                try{
                    $arrName = explode(' ',$arrData[3]);
                    Customer::insert([
                        'id' => $arrData[0],
                        'job_title' => $arrData[1],
                        'email' => $arrData[2],
                        'first_name' => $arrName[0],
                        'last_name' => $arrName[1],
                        'registered_since' => Carbon::createFromFormat('l,F j,Y', $arrData[4])->format('Y-m-d'),
                        'phone' => $arrData[5],
                    ]);
                    $intCustomerInsertData++;
                    $this->info('Customer Inserted with id: '.$arrData[0]);
                }
                catch(\Exception $e){
                    // log the error details while inserting into database
                    $this->logImportCsv('Error in inserting data into Customer table. Description: '.$e->getMessage(), 'error');
                    $this->error('Error in inserting data into Customer with id: '.$arrData[0]);
                }
            }
        }

        $arrLog = [
            'table' => 'Customer',
            'no_of_imported_data' => $intCustomerInsertData,
            'no_of_error_in_data' => $intTotalCustomerData-$intCustomerInsertData,
        ];
        // log the customer import data details
        $this->logImportCsv($arrLog);
        $this->info('All data imported.');
    }

    /**
     * use to generate the url with username and password
     *
     * @var string strLink - given url
     * @var string strUsername - given username
     * @var string strPassword - given password
     * return string url with username and password
     */
    private function generateUrl($strLink, $strUsername, $strPassword){

        $arrLinkDetails =   parse_url($strLink);
        $strUrl         =   $arrLinkDetails['scheme'].'://'.$strUsername.':'.$strPassword.'@'.$arrLinkDetails['host'].$arrLinkDetails['path'];
        return $strUrl;

    }

    /**
     * use to get the data from url
     *
     * @var string strUrl - url with username and password
     * return array data from url
     */
    private function getDataFromUrl($strUrl){
        $arrData = array_map('str_getcsv',file($strUrl));
        unset($arrData[0]);                 //removes headers
        return $arrData;
    }

    /**
     * use to store the logs
     *
     * @var string data - data that we want to log
     * @var string strType - type of log (error/info) default 'info'
     * return null
     */
    private function logImportCsv($data, $strType = 'info'){
        Log::channel('csv_log')->$strType($data);
    }
}
