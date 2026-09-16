<!-- partial:partials/_sidebar.html -->
<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        {{-- <li class="nav-item nav-profile">
              <a href="#" class="nav-link">
                <div class="nav-profile-image">
                  <img src="{!! ('public/assets/images/faces/face1.jpg') !!}" alt="profile" />
                  <span class="login-status online"></span>
                  <!--change to offline or busy as needed-->
                </div>
                <div class="nav-profile-text d-flex flex-column">
                  <span class="font-weight-bold mb-2">David Grey. H</span>
                  <span class="text-secondary text-small">Project Manager</span>
                </div>
                <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
              </a>
            </li> --}}
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.dashboard') }}">
                <span class="menu-title">Dashboard</span>
                <i class="mdi mdi-home menu-icon"></i>
            </a>
        </li>
        @if (auth()->user()->is_admin == 1 ||
                \App\helpers\CommonHelper::getPermission('Roles', 'list') ||
                \App\helpers\CommonHelper::getPermission('Roles', 'create') ||
                \App\helpers\CommonHelper::getPermission('Roles', 'edit') ||
                \App\helpers\CommonHelper::getPermission('Roles', 'delete'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('roles.index') }}">
                    <span class="menu-title">Roles</span>
                    <i class="mdi mdi-account-group menu-icon"></i>
                </a>
            </li>
        @endif
       
        @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Categories'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('categories.index') }}">
                    <span class="menu-title">Category</span>
                    <i class="mdi mdi-view-grid menu-icon"></i>
                </a>
            </li>
        @endif
        
            <li class="nav-item">
                <a class="nav-link" href="{{ route('tags.index') }}">
                    <span class="menu-title">Tags</span>
                    <i class="mdi mdi-view-grid menu-icon"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('allergies.index') }}">
                    <span class="menu-title">Allergy</span>
                    <i class="mdi mdi-view-grid menu-icon"></i>
                </a>
            </li>

        <!--<li class="nav-item">-->
        <!--        <a class="nav-link" href="{{ route('restaurants.index') }}">-->
        <!--            <span class="menu-title">Resturant Type</span>-->
        <!--            <i class="mdi mdi-view-grid menu-icon"></i>-->
        <!--        </a>-->
        <!--    </li>-->
         @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Cuisine Types'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('cuisine-types.index') }}">
                    <span class="menu-title">Cuisine Type</span>
                    <i class="mdi mdi-silverware-fork-knife menu-icon"></i>
                </a>
            </li>
        @endif 
        {{-- @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Preferences'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('preferences.index') }}">
                    <span class="menu-title">Preference</span>
                    <i class="mdi mdi-view-grid menu-icon"></i>
                </a>
            </li>
        @endif --}}
        {{-- @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('MealTimes'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('mealtimes.index') }}">
                    <span class="menu-title">Meal Times</span>
                    <i class="mdi mdi-view-grid menu-icon"></i>
                </a>
            </li>
        @endif --}}
        {{-- @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Food Items'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('food-items.index') }}">
                    <span class="menu-title">Food Items</span>
                    <i class="mdi mdi-food menu-icon"></i>
                </a>
            </li>
        @endif --}}
         {{-- @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Food Items')) --}}
            {{-- <li class="nav-item">
                <a class="nav-link" href="{{ route('food_dishes.index') }}">
                    <span class="menu-title">Food Dishes</span>
                    <i class="mdi mdi-food menu-icon"></i>
                </a>
            </li> --}}
        {{-- @endif --}}
        {{-- @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Food Items'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('foods.index') }}">
                    <span class="menu-title">Foods</span>
                    <i class="mdi mdi-food menu-icon"></i>
                </a>
            </li>
        @endif --}}
        
        @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Customer Management'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('customers.index') }}">
                    <span class="menu-title">Customer Management</span>
                    <i class="mdi mdi-food-variant menu-icon"></i>
                </a>
            </li>
        @endif
        
        @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Chefs'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('chefs.index') }}">
                    <span class="menu-title">Chef Managements</span>
                    <i class="mdi mdi-food-variant menu-icon"></i>
                </a>
            </li>
        @endif
        @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Orders'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('orders.index') }}">
                    <span class="menu-title">Orders Managements</span>
                    <i class="mdi mdi-package-variant-closed menu-icon"></i>
                </a>
            </li>
        @endif
        @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Coupons'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('coupons.index') }}">
                    <span class="menu-title">Coupons Code</span>
                    <i class="mdi mdi-ticket-percent menu-icon"></i>
                </a>
            </li>
        @endif
         <li class="nav-item">
                <a class="nav-link" href="{{ route('homescreen.index') }}">
                    <span class="menu-title">Home Screen(Customer)</span>
                    <i class="mdi mdi-ticket-percent menu-icon"></i>
                </a>
         </li>
        @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Pages'))
        <li class="nav-item">
                <a class="nav-link" href="{{ route('pages.index') }}">
                    <span class="menu-title">Pages</span>
                    <i class="mdi mdi-food menu-icon"></i>
                </a>
            </li>
            @endif
            <li class="nav-item">
                <a class="nav-link" href="{{ url('payouts/create') }}">
                    <span class="menu-title">Payout</span>
                    <i class="mdi mdi-account-box menu-icon"></i>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="{{ url('notifications/create') }}">
                    <span class="menu-title">Admin Notification</span>
                    <i class="mdi mdi-account-box menu-icon"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('reports') }}">
                    <span class="menu-title">Reports</span>
                    <i class="mdi mdi-account-box menu-icon"></i>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="{{ url('issues') }}">
                    <span class="menu-title">Issue/Complaint</span>
                    <i class="mdi mdi-account-box menu-icon"></i>
                </a>
            </li>
         {{-- <li class="nav-item">
                <a class="nav-link" href="{{ route('radius.index') }}">
                    <span class="menu-title">Radius Setting</span>
                    <i class="mdi mdi-account-box menu-icon"></i>
                </a>
            </li> --}}
        @if (auth()->user()->is_admin == 1 || \App\Helpers\CommonHelper::hasAnyPermission('Settings'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('settings.index') }}">
                    <span class="menu-title">Settings</span>
                    <i class="mdi mdi-cog menu-icon"></i>
                </a>
            </li>
             <li class="nav-item">
                <a class="nav-link" href="{{ url('smtp-settings') }}">
                    <span class="menu-title">Mail Setting</span>
                    <i class="mdi mdi-cog menu-icon"></i>
                </a>
            </li>
        @endif






        {{-- <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
                <span class="menu-title">Basic UI Elements</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-crosshairs-gps menu-icon"></i>
              </a>
              <div class="collapse" id="ui-basic">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="pages/ui-features/buttons.html">Buttons</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="pages/ui-features/dropdowns.html">Dropdowns</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="pages/ui-features/typography.html">Typography</a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#icons" aria-expanded="false" aria-controls="icons">
                <span class="menu-title">Icons</span>
                <i class="mdi mdi-contacts menu-icon"></i>
              </a>
              <div class="collapse" id="icons">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="pages/icons/font-awesome.html">Font Awesome</a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#forms" aria-expanded="false" aria-controls="forms">
                <span class="menu-title">Forms</span>
                <i class="mdi mdi-format-list-bulleted menu-icon"></i>
              </a>
              <div class="collapse" id="forms">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="pages/forms/basic_elements.html">Form Elements</a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#charts" aria-expanded="false" aria-controls="charts">
                <span class="menu-title">Charts</span>
                <i class="mdi mdi-chart-bar menu-icon"></i>
              </a>
              <div class="collapse" id="charts">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="pages/charts/chartjs.html">ChartJs</a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#tables" aria-expanded="false" aria-controls="tables">
                <span class="menu-title">Tables</span>
                <i class="mdi mdi-table-large menu-icon"></i>
              </a>
              <div class="collapse" id="tables">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="pages/tables/basic-table.html">Basic table</a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#auth" aria-expanded="false" aria-controls="auth">
                <span class="menu-title">User Pages</span>
                <i class="menu-arrow"></i>
                <i class="mdi mdi-lock menu-icon"></i>
              </a>
              <div class="collapse" id="auth">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item">
                    <a class="nav-link" href="pages/samples/blank-page.html"> Blank Page </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="pages/samples/login.html"> Login </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="pages/samples/register.html"> Register </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="pages/samples/error-404.html"> 404 </a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" href="pages/samples/error-500.html"> 500 </a>
                  </li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="docs/documentation.html" target="_blank">
                <span class="menu-title">Documentation</span>
                <i class="mdi mdi-file-document-box menu-icon"></i>
              </a>
            </li> --}}
    </ul>
</nav>
<!-- partial -->
