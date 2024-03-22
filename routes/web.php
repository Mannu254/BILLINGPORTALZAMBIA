<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return view('auth.login');
});
Auth::routes();
Route::middleware(['admin'])->group(function () {
Route::get('/admin', 'AdminController@index')->name('admin');
Route::resource('/country','CountrysController');
Route::resource('/users','UsersController');
Route::post('status_change', 'UsersController@status_change'); 

});

    Route::middleware(['billing'])->group(function (){
    Route::get('/meter_reading_review', 'BillingExecutiveController@meter_reading_review')->name('meter_reading_review');
    Route::post('/post_reading', 'BillingExecutiveController@post_reading')->name('post_reading');
    Route::get('/billing', 'BillingExecutiveController@index')->name('billing');
    Route::post('cust_assets', 'CustomerController@cust_assets'); 
    Route::post('previous_reading', 'CustomerController@previous_reading'); 

    Route::get('/mds_service_assets_b', 'BillingExecutiveController@mds_service_assets_b')->name('mds_service_assets_b');
    Route::post('/mds_cust_assets_b', 'BillingExecutiveController@mds_cust_assets_b')->name('mds_cust_assets_b');

    Route::get('/mds_cost_click_b', 'BillingExecutiveController@mds_cost_click_b')->name('mds_cost_click_b');
    Route::post('/cont_code_b', 'BillingExecutiveController@cont_code_b')->name('cont_code_b');
    Route::get('slabs_rate_b','BillingExecutiveController@slabs_rate_b')->name('slabs_rate_b');
    Route::post('update_rates_b','BillingExecutiveController@update_rates_b')->name('update_rates_b'); 
    Route::post('/session_date','BillingExecutiveController@session_date');


    
    Route::post('reading_save', 'CustomerController@reading_save'); 


   
    Route::post('cust_search', 'CustomerController@cust_search'); 

    Route::resource('/customer','CustomerController');
  
    
    Route::get('meter_reading_tmp', 'BillingExecutiveController@meter_reading_tmp')->name('meter_reading_tmp'); 

    Route::get('reading_db', 'BillingExecutiveController@reading_db')->name('reading_db');
    Route::get('previous_reading', 'BillingExecutiveController@previous_reading')->name('previous_reading');
    
    

    
    Route::post('post_excel_reading', 'BillingExecutiveController@post_excel_reading')->name('post_excel_reading');

    Route::post('/post_single_reading', 'BillingExecutiveController@post_single_reading')->name('post_single_reading');
    Route::post('/salesOrder', 'SalesOrderController@salesOrder')->name('salesOrder');

    // Route::resource('/sales','SalesOrderCreateController');

    Route::get('/billing_review', 'BillingExecutiveController@billing_review')->name('billing_review');
    
    Route::Resource('/advanced_billing','AdvancedBillingController');
    Route::get('/adv_meter_reading_review', 'AdvancedBillingController@adv_meter_reading_review')->name('adv_meter_reading_review');

    Route::get('/adv_billing_review', 'AdvancedBillingController@adv_billing_review')->name('adv_billing_review');
    Route::post('/adv_salesOrder', 'AdvancedBillingController@adv_salesOrder')->name('adv_salesOrder');

    Route::post('search_serial', 'BillingExecutiveController@search_serial'); 
    Route::post('search_hint', 'BillingExecutiveController@search_hint'); 

    Route::get('export_orders', 'BillingExecutiveController@export_orders')->name('export_orders');
    Route::get('downloadTemp', 'BillingExecutiveController@downloadTemp')->name('downloadTemp');  
    Route::resource('/vol_analysis','VolAnalysisController');

    Route::post('vol_analsisExpo', 'VolAnalysisController@vol_analsisExpo')->name('vol_analsisExpo');   
    Route::get('summary_sheet', 'VolAnalysisController@summary_sheet')->name('summary_sheet');
    Route::post('summary', 'VolAnalysisController@summary')->name('summary'); 

           

// Route::get('/home', 'HomeController@index')->name('home');
  });

  Route::middleware(['LandedCost','cors'])->group(function () {
  Route::get('/landed_cost', 'LandedCostController@index')->name('landed_cost');
  Route::get('/shipment', 'LandedCostController@shipment')->name('shipment');
  Route::resource('/lcost','LandedCostController');
  Route::resource('/scheme','SchemesController');
 });


 Route::middleware(['reports'])->group(function () {
  Route::get('/reports', 'ReportsController@index')->name('reports');
  Route::post('/sales_rpt', 'ReportsController@sales_rpt')->name('sales_rpt');

  
  // Route::get('/shipment', 'LandedCostController@shipment')->name('shipment');
  // Route::resource('/lcost','LandedCostController');
  // Route::resource('/scheme','SchemesController');
 });


 Route::middleware(['servicemanager'])->group(function () {
  Route::resource('/Service_Manager','SMController');
 Route::get('/contract_update', 'SMController@contract_update')->name('contract_update');

 Route::post('contract_search', 'SMController@contract_search'); 
 Route::post('contract_date_change', 'SMController@contract_date_change'); 
 Route::post('contract_update_date', 'SMController@contract_update_date'); 
 Route::post('asset_export', 'SMController@asset_export')->name('asset_export');
 Route::post('update_asset', 'SMController@update_asset')->name('update_asset');
 Route::get('service_assets','SMController@service_assets')->name('service_assets');
 Route::post('cust_assets','SMController@cust_assets')->name('cust_assets');
 Route::get('contract_assets','SMController@contract_assets')->name('contract_assets');
 Route::post('asset_export_all', 'SMController@asset_export_all')->name('asset_export_all');
 Route::post('asset_inContract', 'SMController@asset_inContract')->name('asset_inContract');
 Route::post('contract_renew_monthly', 'SMController@contract_renew_monthly')->name('contract_renew_monthly');

 Route::post('NotIncontract', 'SMController@NotIncontract')->name('NotIncontract'); 

  
 });


 Route::middleware(['accountmanager'])->group(function (){
 Route::resource('/AccountManager','AccountManagerController');

});


Route::middleware(['billingmanager'])->group(function (){
  Route::resource('/BillingManager','BillingManagerController');
  Route::get('/mds_contract_update', 'BillingManagerController@mds_contract_update')->name('mds_contract_update');
  Route::post('mds_asset_export', 'BillingManagerController@mds_asset_export')->name('mds_asset_export');
  Route::post('mds_update_asset', 'BillingManagerController@mds_update_asset')->name('mds_update_asset');

  Route::post('mds_contract_search', 'BillingManagerController@mds_contract_search'); 

  Route::post('mds_contract_date_change', 'BillingManagerController@mds_contract_date_change'); 

  Route::post('mds_contract_update_date', 'BillingManagerController@mds_contract_update_date'); 

  Route::get('mds_service_assets','BillingManagerController@mds_service_assets')->name('mds_service_assets');
  Route::post('mds_cust_assets','BillingManagerController@mds_cust_assets')->name('mds_cust_assets');

  Route::get('mds_cost_click','BillingManagerController@mds_cost_click')->name('mds_cost_click');

  
  Route::post('cont_code', 'BillingManagerController@cont_code'); 
  Route::get('slabs_rate','BillingManagerController@slabs_rate')->name('slabs_rate');

  Route::post('update_rates', 'BillingManagerController@update_rates')->name('update_rates'); 

  Route::get('rental_charges','BillingManagerController@rental_charges')->name('rental_charges');
  Route::post('rental_cont_code', 'BillingManagerController@rental_cont_code'); 
  Route::post('update_rental', 'BillingManagerController@update_rental'); 

  Route::get('contract_rates_report','BillingManagerController@contract_rates_report')->name('contract_rates_report');
  Route::post('export_rates','BillingManagerController@export_rates')->name('export_rates');
  Route::post('export_rates_ecalation','BillingManagerController@export_rates_ecalation')->name('export_rates_ecalation');


  

  
  

  


  

  


  

  

  

  

 
  

  
 
 });
  
