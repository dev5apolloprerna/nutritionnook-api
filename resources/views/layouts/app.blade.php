@php
    $setting = \App\Models\Setting::first();
    $loaderLogo = !empty($setting?->logo)
        ? asset('/images/' . $setting->logo)
        : asset('/images/1755158105Nutritionnook_logo 512_512.png');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Nutrition Nook</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Nutrition Nook') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- plugins:css -->
    <link rel="stylesheet" href="{!! asset('/assets/vendors/mdi/css/materialdesignicons.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('/assets/vendors/ti-icons/css/themify-icons.css') !!}">
    <link rel="stylesheet" href="{!! asset('/assets/vendors/css/vendor.bundle.base.css') !!}">
    <link rel="stylesheet" href="{!! asset('/assets/vendors/font-awesome/css/font-awesome.min.css') !!}">
    <!-- endinject -->

    {{-- <link rel="stylesheet" href="{!! asset('/assets/vendors/select2/select2.min.css') !!}">
    <link rel="stylesheet" href="{!! asset('/assets/vendors/select2-bootstrap-theme/select2-bootstrap.min.css') !!}"> --}}

    <!-- Plugin css for this page -->
    {{-- <link rel="stylesheet" href="{!! asset('/assets/vendors/font-awesome/css/font-awesome.min.css') !!}" /> --}}
    <link rel="stylesheet" href="{!! asset('/assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css') !!}">
    <!-- End plugin css for this page -->

    <!-- Layout styles -->
    <link rel="stylesheet" href="{!! asset('/assets/css/style.css') !!}">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="https://api.nutritionnook.net/public/images/1755158105Nutritionnook_logo%20512_512.png" />

    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />-->

<link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet" />

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .page-loader {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f2edf3;
            opacity: 1;
            visibility: visible;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }

        .page-loader.is-loaded {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .page-loader__logo {
            width: 96px;
            height: 96px;
            object-fit: contain;
            border-radius: 50%;
            animation: loader-pulse 1.2s ease-in-out infinite;
        }

        @keyframes loader-pulse {
            0%, 100% {
                transform: scale(0.94);
                opacity: 0.7;
            }

            50% {
                transform: scale(1.05);
                opacity: 1;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .page-loader__logo {
                animation: none;
            }
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            color: #b66dff;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer; 
        }

        .back-button:hover {
            color: #b66dff;
        }

        .back-button i {
            margin-right: 8px;
        }
        
    </style>
</head>

<body>
    <div class="page-loader" id="pageLoader" role="status" aria-label="Loading">
        <img class="page-loader__logo" src="{{ $loaderLogo }}" alt="Nutrition Nook">
    </div>
    <div class="container-scroller">
        @include('layouts.navbar')
        <div class="container-fluid page-body-wrapper">
            @include('layouts.sidebar')
            <div class="main-panel">
                <div class="content-wrapper">
                    {{-- Page Content --}}
                    @yield('content')
                </div>
                @include('layouts.footer')
            </div>
        </div>
    </div>
    <!-- container-scroller -->

    {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- plugins:js -->
    <script src="{!! asset('/assets/vendors/js/vendor.bundle.base.js') !!}"></script>
    <!-- endinject -->

<script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>

    <!-- Plugin js for this page -->
    <script src="{!! asset('/assets/vendors/chart.js/chart.umd.js') !!}"></script>
    <script src="{!! asset('/assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js') !!}"></script>
    <!-- End plugin js for this page -->

    <!-- inject:js -->
    <script src="{!! asset('/assets/js/off-canvas.js') !!}"></script>
    <script src="{!! asset('/assets/js/misc.js') !!}"></script>
    <script src="{!! asset('/assets/js/settings.js') !!}"></script>
    <script src="{!! asset('/assets/js/todolist.js') !!}"></script>
    <script src="{!! asset('/assets/js/jquery.cookie.js') !!}"></script>
    <!-- endinject -->

    <!-- Custom js for this page -->
    <script src="{!! asset('/assets/js/dashboard.js') !!}"></script>
    <!-- End custom js for this page -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!--<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>-->
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>

   



    <script>
        const pageLoader = document.getElementById('pageLoader');

        const hidePageLoader = () => {
            pageLoader?.classList.add('is-loaded');
        };

        window.addEventListener('load', hidePageLoader);
        window.setTimeout(hidePageLoader, 5000);
        
        function confirmLogout(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, logout',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            });
        }

        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: {!! json_encode(session('success')) !!},
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: {!! json_encode(session('error')) !!},
            });
        @endif
    </script>

    <script>
    new DataTable('#myTable');
    new DataTable('#foodTable');
    new DataTable('#ordersTable');
    new DataTable('#auditTable');

    // ✅ Kitchen table mate safe init
    const kitchenTable = document.querySelector('#kitchenphotoTable tbody');

    if (kitchenTable && kitchenTable.querySelectorAll('tr').length > 0 
        && !kitchenTable.querySelector('td[colspan]')) {
        new DataTable('#kitchenphotoTable');
    }
</script>
    
  

</body>

</html>
