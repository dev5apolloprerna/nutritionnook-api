<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ExcelExportable;

class ExportController extends Controller
{
    use ExcelExportable;
    
    /**
     * 1. ROLES EXPORT
     * Export all roles data
     */
    public function exportRoles()
    {
        return $this->exportToExcel('roles', [], [], 'roles_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * 2. CATEGORIES EXPORT
     * Export all categories data
     */
    public function exportCategories()
    {
        return $this->exportToExcel('categories', [], [], 'categories_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * 3. TAGS EXPORT
     * Export all tags data
     */
    public function exportTags()
    {
        return $this->exportToExcel('tags', [], [], 'tags_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * 4. CUISINE TYPES EXPORT
     * Export all cuisine types data
     */
    public function exportCuisineTypes()
    {
        return $this->exportToExcel('cuisine_type', [], [], 'cuisine_types_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * 5. CUSTOMERS (USERS) EXPORT
     * Export all customers data
     */
    public function exportCustomers()
    {
        return $this->exportToExcel('users', [], [], 'customers_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * 6. CHEFS EXPORT
     * Export all chefs data
     */
    public function exportChefs()
    {
        return $this->exportToExcel('chefs', [], [], 'chefs_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * 7. ORDERS EXPORT
     * Export all orders data
     */
    public function exportOrders()
    {
        return $this->exportToExcel('orders', [], [], 'orders_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * 8. COUPON CODES EXPORT
     * Export all coupon codes data
     */
    public function exportCoupons()
    {
        // return $this->exportToExcel('coupons_code', [], [], 'coupons_' . date('Y-m-d') . '.xlsx');
        return $this->exportToExcel('coupons', [], [], 'coupons_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * 9. PAGES EXPORT
     * Export all pages data
     */
    public function exportPages()
    {
        return $this->exportToExcel('pages', [], [], 'pages_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * 10. ISSUES EXPORT
     * Export all issues data
     */
    public function exportIssues()
    {
        return $this->exportToExcel('issues', [], [], 'issues_' . date('Y-m-d') . '.xlsx');
    }
}