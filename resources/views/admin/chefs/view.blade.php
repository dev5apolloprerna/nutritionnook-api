@extends('layouts.app')

@section('content')
    <style>
        :root {
            --primary-color: #B66DFF;
            --secondary-color: #B66DFF;
            --dark-color: #333;
            --light-color: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .chef-detail-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        /* .back-button {
                                                            display: inline-flex;
                                                            align-items: center;
                                                            color: var(--primary-color);
                                                            text-decoration: none;
                                                            margin-bottom: 20px;
                                                            font-weight: 500;
                                                            transition: all 0.3s;
                                                        }

                                                        .back-button:hover {
                                                            color: var(--secondary-color);
                                                        }

                                                        .back-button i {
                                                            margin-right: 8px;
                                                        } */

        .chef-header {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .chef-image-container {
            position: relative;
            width: 200px;
            height: 200px;
        }

        .chef-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            border: 5px solid white;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .verification-badge {
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 5px 15px;
            border-radius: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .verified {
            color: var(--success-color);
        }

        .chef-info {
            flex: 1;
            min-width: 300px;
        }

        .chef-name {
            font-size: 28px;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .business-name {
            font-size: 18px;
            color: #666;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .rating-section {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .stars {
            color: var(--warning-color);
            font-size: 18px;
        }

        .review-count {
            color: #666;
            font-size: 14px;
        }

        .status-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .approved {
            background-color: #e6f7ee;
            color: var(--success-color);
        }

        .online-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            font-weight: 600;
        }

        .online {
            color: var(--success-color);
        }

        .online i {
            font-size: 10px;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
            font-size: 14px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            color: var(--dark-color);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
        }

        .social-links a:hover {
            background: var(--primary-color);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: #777;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #666;
            position: relative;
            transition: all 0.3s;
        }

        .tab:hover {
            color: var(--primary-color);
        }

        .tab.active {
            color: var(--primary-color);
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary-color);
        }

        .tab-content {
            min-height: 300px;
        }

        .section-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--dark-color);
            position: relative;
            padding-left: 15px;
        }

        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 5px;
            height: 70%;
            width: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .detail-card h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: var(--dark-color);
            padding-bottom: 8px;
            border-bottom: 1px dashed #eee;
        }

        .detail-row {
            display: flex;
            margin-bottom: 12px;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            width: 120px;
            font-size: 14px;
        }

        .detail-value {
            flex: 1;
            font-size: 14px;
            color: #333;
        }

        .cuisine-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cuisine-tag {
            background: #f0f0f0;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: #555;
        }

        .food-type {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 8px;
        }

        .veg {
            background: #e6f7ee;
            color: var(--success-color);
        }

        .vegan {
            background: #e6f2ff;
            color: var(--info-color);
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .review-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .reviewer-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .reviewer-info {
            flex: 1;
        }

        .reviewer-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .review-date {
            font-size: 12px;
            color: #999;
        }

        .review-rating {
            color: var(--warning-color);
            font-size: 16px;
        }

        .review-content {
            margin-bottom: 10px;
            color: #555;
            font-size: 14px;
        }

        .review-order {
            font-size: 13px;
            color: #999;
            font-style: italic;
        }

        .view-all-reviews {
            text-align: center;
            margin-top: 20px;
        }

        .view-all-reviews a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .view-all-reviews a:hover {
            color: var(--secondary-color);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            font-size: 14px;
        }

        .btn-edit {
            background: #e6f2ff;
            color: var(--info-color);
        }

        .btn-edit:hover {
            background: #d0e4ff;
        }

        .btn-suspend {
            background: #fff3cd;
            color: var(--warning-color);
        }

        .btn-suspend:hover {
            background: #ffe8a1;
        }

        .btn-delete {
            background: #f8d7da;
            color: var(--danger-color);
        }

        .btn-delete:hover {
            background: #f1b0b7;
            color: var(--danger-color);
        }

        .btn-message {
            background: #e6f7ee;
            color: var(--success-color);
        }

        .btn-message:hover {
            background: #d0f0e0;
        }

        /* Tab content styles */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Menu Items Tab - Table Layout */
        .menu-items-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .menu-items-table th,
        .menu-items-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .menu-items-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .menu-items-table tr:last-child td {
            border-bottom: none;
        }

        .menu-items-table tr:hover {
            background: #f8f9fa;
        }

        .menu-item-image-cell {
            width: 80px;
        }

        .menu-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .menu-item-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .menu-item-description {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        .menu-item-price {
            font-weight: 700;
            color: var(--primary-color);
        }

        .menu-item-rating {
            color: var(--warning-color);
            font-size: 13px;
        }

        .menu-item-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-available {
            background: #e6f7ee;
            color: var(--success-color);
        }

        .status-unavailable {
            background: #f8d7da;
            color: var(--danger-color);
        }

        .menu-item-actions {
            display: flex;
            gap: 10px;
        }

        .menu-item-action {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .menu-item-action:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Orders Tab */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .orders-table th,
        .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        .orders-table tr:hover {
            background: #f8f9fa;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-completed {
            background: #e6f7ee;
            color: var(--success-color);
        }

        .status-pending {
            background: #fff3cd;
            color: var(--warning-color);
        }

        .status-cancelled {
            background: #f8d7da;
            color: var(--danger-color);
        }

        /* Reviews Tab */
        .review-summary {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .average-rating {
            text-align: center;
        }

        .average-rating-value {
            font-size: 48px;
            font-weight: 700;
            color: var(--dark-color);
        }

        .average-rating-stars {
            color: var(--warning-color);
            font-size: 24px;
            margin-bottom: 5px;
        }

        .average-rating-count {
            color: #777;
            font-size: 14px;
        }

        .rating-progress-bars {
            flex: 1;
        }

        .rating-progress {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .rating-label {
            width: 80px;
            font-size: 14px;
            color: #555;
        }

        /*.progress-bar {*/
        /*    flex: 1;*/
        /*    height: 8px;*/
        /*    background: #eee;*/
        /*    border-radius: 4px;*/
        /*    overflow: hidden;*/
        /*    margin: 0 10px;*/
        /*}*/

        /*.progress-fill {*/
        /*    height: 100%;*/
        /*    background: var(--warning-color);*/
        /*}*/

        .rating-count {
            width: 40px;
            font-size: 14px;
            color: #777;
            text-align: right;
        }

        /* Earnings Tab */
        .earnings-overview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .earning-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .earning-card h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 15px;
        }

        .earning-amount {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
        }

        .earning-change {
            font-size: 14px;
            margin-top: 5px;
        }

        .change-up {
            color: var(--success-color);
        }

        .change-down {
            color: var(--danger-color);
        }

        .earnings-chart {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
            font-size: 18px;
        }

        /* Keep dropdown option text black on hover */
            .dropdown-item:hover {
                color: #000 !important;
                background-color: #f8f9fa !important; /* optional: light hover background */
            }
    
        /* Documents Tab */
        /*.documents-list {*/
        /*    display: grid;*/
        /*    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));*/
        /*    gap: 20px;*/
        /*}*/

        /*.document-card {*/
        /*    background: white;*/
        /*    border-radius: 8px;*/
        /*    padding: 20px;*/
        /*    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);*/
        /*    display: flex;*/
        /*    align-items: center;*/
        /*    gap: 15px;*/
        /*}*/

        /*.document-icon {*/
        /*    width: 50px;*/
        /*    height: 50px;*/
        /*    background: #f0f0f0;*/
        /*    border-radius: 8px;*/
        /*    display: flex;*/
        /*    align-items: center;*/
        /*    justify-content: center;*/
        /*    color: var(--primary-color);*/
        /*    font-size: 20px;*/
        /*}*/

        /*.document-info {*/
        /*    flex: 1;*/
        /*}*/

        /*.document-name {*/
        /*    font-weight: 600;*/
        /*    margin-bottom: 5px;*/
        /*    color: var(--dark-color);*/
            word-break: break-word;   /* long names wrap */
            white-space: normal;      /* allow multiple lines */
        /*}*/
        /*.document-details {*/
        /*    font-size: 13px;*/
        /*    color: #777;*/
        /*}*/

        /*.document-actions {*/
        /*    display: flex;*/
        /*    gap: 10px;*/
        /*}*/

        /*.document-action {*/
        /*    width: 35px;*/
        /*    height: 35px;*/
        /*    border-radius: 50%;*/
        /*    background: #f0f0f0;*/
        /*    display: flex;*/
        /*    align-items: center;*/
        /*    justify-content: center;*/
        /*    cursor: pointer;*/
        /*    transition: all 0.3s;*/
        /*}*/

        /*.document-action:hover {*/
        /*    background: var(--primary-color);*/
        /*    color: white;*/
        /*}*/
        .documents-wrapper {
    display: flex;
    flex-direction: column;
    gap: 25px;
    margin-top: 20px;
}

.document-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    padding: 20px 25px;
    transition: 0.3s ease;
}

.document-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.doc-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.doc-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #eef6ff;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #007bff;
    font-size: 22px;
}

.doc-body {
    padding-left: 65px;
}

/* ✅ Common style for all document rows */
.personal-docs-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
}

.personal-docs-left {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.personal-doc-item {
    display: flex;
    align-items: center;
    justify-content: center;
}

.single-doc-img {
    width: 90px;
    height: 90px;
    border-radius: 10px;
    border: 1px solid #ccc;
    object-fit: cover;
    transition: transform 0.3s;
}

.single-doc-img:hover {
    transform: scale(1.05);
}

.btn-single-download {
    background: #007bff;
    color: #fff;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    text-decoration: none;
    transition: 0.3s;
    white-space: nowrap;
}

.btn-single-download:hover {
    background: #0056b3;
}

.pdf-link {
    font-size: 40px;
    color: #e74c3c;
}

.no-docs {
    color: #888;
    font-size: 14px;
}

.btn-status-dropdown {
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-approved {
    background-color: #28a745 !important; /* Green */
}

.btn-rejected {
    background-color: #dc3545 !important; /* Red */
}

/* Bootstrap dropdown arrow */
.btn-status-dropdown.dropdown-toggle::after {
    margin-left: 8px;
    vertical-align: middle;
    border-top: 0.4em solid;
    border-right: 0.4em solid transparent;
    border-left: 0.4em solid transparent;
    content: "";
}

.btn-select {
    background-color: #f0f0f0;
    color: #555;
    border: 1px solid #ccc;
}



        .toggle-switch {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            /* ensures full height of cell */
        }
        
        /*photos*/
        .photos-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .photo-card {
            width: 220px; 
            height: 180px; /* fixed height */
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .photo-card img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* image full card me fit hogi, cut thoda ho sakta hai, lekin white space nahi rahega */
        } 
        /* Carousel arrows ko visible karne ke liye */
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0,0,0,0.5); /* semi-transparent black background */
            border-radius: 50%; /* circular icon */
            width: 40px; /* size adjust */
            height: 40px;
        }
        
        .carousel-control-prev,
        .carousel-control-next {
            top: 50%;
            transform: translateY(-50%);
        }

        .glightbox-container {
            z-index: 99999 !important;
        }
        
        .glightbox-next,
        .glightbox-prev {
            display: block !important;
            opacity: 1 !important;
        }

        
        
        

        .food-type {
            display: inline-block;
            padding: 4px 10px;
            margin: 2px 5px 2px 0;
            background-color: #f0f0f0;
            /* Light gray */
            border: 1px solid #ddd;
            border-radius: 15px;
            font-size: 13px;
            color: #333;
        }

        .status-dropdown {
            min-width: 130px;
            padding: 5px;
            border-radius: 4px;
        }

        .custom-refund-btn {
            background-color: #fff3cd;
            /* Light yellow */
            color: #856404;
            /* Dark yellow/brown text */
            border: 1px solid #ffeeba;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 5px;
            transition: all 0.2s ease-in-out;
        }

        .custom-refund-btn:hover {
            background-color: #ffe8a1;
            color: #533f03;
            border-color: #ffd25a;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #007bff;
            /* Blue */
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        /* Small toggle version */
        .small-switch {
            width: 36px;
            height: 18px;
        }

        .small-switch .slider:before {
            height: 14px;
            width: 14px;
            left: 2px;
            bottom: 2px;
        }

        .small-switch input:checked+.slider:before {
            transform: translateX(18px);
        }

        .small-text {
            font-size: 12px;
            margin-top: 2px;
        }

        @media (max-width: 768px) {
            .chef-header {
                flex-direction: column;
            }

            .chef-image-container {
                margin: 0 auto;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }

            .menu-items-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
    <div class="chef-detail-container">
        <a class="back-button" href="{{ route('chefs.index') }}">
            <i class="fas fa-arrow-left"></i> Back to Chefs
        </a>


        <div class="chef-header">
            <div class="chef-image-container">
                
             @php
                $mainImage = null;
            
                if (!empty($chef->profile_image)) {
                    // Agar JSON array hai toh pehla image le lo
                    if (is_array(json_decode($chef->profile_image, true))) {
                        $images = json_decode($chef->profile_image, true);
                        $mainImage = $images[0] ?? null;
                    } else {
                        $mainImage = $chef->profile_image; // direct string
                    }
                }
            @endphp
            
            @if (!empty($mainImage))
                <img src="{{ asset($mainImage) }}" class="chef-image">
            @else
                <img src="https://via.placeholder.com/100" class="chef-image">
            @endif


                {{-- <img src="https://randomuser.me/api/portraits/men/32.jpg" class="chef-image"> --}}
                {{-- <div class="verification-badge">
                    <i class="fas fa-check-circle verified"></i>
                    Verified Chef
                </div> --}}
            </div>

            <div class="chef-info">
                <h1 class="chef-name">{{ $chef->name }}</h1>
                {{-- <p class="business-name">Raj's Spice Kitchen</p> --}}

                <div class="rating-section">
                   

                    <div class="review-count">
                        
                    </div>
                </div>


                <div class="status-container">
                    @if ($chef->available == 1)
                        <span class="online-status online">
                            <i class="fas fa-circle"></i> Online Now
                        </span>
                    @else
                        <span class="online-status offline">
                            <i class="fas fa-circle"></i> Offline
                        </span>
                    @endif
                </div>


                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i> {{ $chef->email }}
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i> {{ $chef->phone_number }}
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i> {{ $chef->address }}
                    </div>
                </div>

                {{-- <div class="social-links">
                    <a href="https://instagram.com/rajspice" target="_blank">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://facebook.com/rajspice" target="_blank">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://rajspice.com" target="_blank">
                        <i class="fas fa-globe"></i>
                    </a>
                </div> --}}
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">{{ $totalOrders }}</div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹{{ number_format($totalAmount, 2) }}</div>
                <div class="stat-label">Total Earnings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹{{ number_format($netEarnings, 2) }}</div>
                <div class="stat-label">Net Earnings (after {{ $chef->commission }}% commission)</div>
            </div>
          
            <div class="stat-card">
                <div class="stat-value">{{ number_format($averageRating, 1) }}</div>
                <div class="stat-label">Avg Rating</div>
            </div>

            {{-- <div class="stat-card">
                <div class="stat-value">10 km</div>
                <div class="stat-label">Service Radius</div>
            </div> --}}
        </div>

        <div class="tabs">
            <button class="tab active" data-tab="overview">Overview</button>
            <button class="tab" data-tab="menu">Menu Items</button>
            <button class="tab" data-tab="orders">Orders</button>
            <button class="tab" data-tab="reviews">Reviews </button>
            <button class="tab" data-tab="earnings">Earnings</button>
            <button class="tab" data-tab="documents">Documents</button>
            <button class="tab" data-tab="photos">Kitchen Assesment Photos</button>
            <button class="tab" data-tab="audits">Audits</button>
        </div>

        <!-- Overview Tab Content -->
        <div class="tab-content active" id="overview">
            <div class="overview-content">
                <h2 class="section-title">Business Information</h2>
                <div class="details-grid">
                    <div class="detail-card">
                        <h3>Basic Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Restaurant Name:</span>
                            <span class="detail-value">{{ $chef->kitchen_name }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">House/Building Number:</span>
                            <span class="detail-value">{{ $chef->shop_plot_number }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Floor:</span>
                            <span class="detail-value">{{ $chef->floor }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Building/Complex Name:</span>
                            <span class="detail-value">{{ $chef->building_name }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">City:</span>
                            <span class="detail-value">{{ $chef->city }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Pincode:</span>
                            <span class="detail-value">{{ $chef->pincode }}</span>
                        </div>
                        {{-- <div class="detail-row">
                            <span class="detail-label">Cuisine Types:</span>
                            <div class="detail-value">
                                <div class="cuisine-tags">
                                    <span class="cuisine-tag">Indian</span>
                                    <span class="cuisine-tag">Mughlai</span>
                                    <span class="cuisine-tag">Gujarati</span>
                                </div>
                            </div>d
                        </div> --}}
                        {{-- <div class="detail-row">
                            <span class="detail-label">Rasturant Types:</span>
                            <div class="detail-value">
                                <span class="food-type veg">Vegetarian</span>
                                <span class="food-type vegan">Vegan Options</span>
                            </div>
                        </div> --}}
                        {{-- <div class="detail-row">
                            <span class="detail-label">Restaurant Types:</span>
                            <div class="detail-value">
                                @foreach ($chef->restaurantTypes as $type)
                                    <span class="food-type">{{ $type->title }}</span>
                                @endforeach
                            </div>
                        </div> --}}



                        {{-- <div class="detail-row">
                            <span class="detail-label">Specialties:</span>
                            <span class="detail-value">{{ $chef->specialties }}</span>
                        </div> --}}
                    </div>

                    <div class="detail-card">
                        <h3>Kitchen Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Kitchen Type:</span>
                            <span class="detail-value">{{ $chef->kitchen_type }}</span>
                        </div>
                        {{-- <div class="detail-row">
                            <span class="detail-label">Daily Capacity:</span>
                            <span class="detail-value">{{ $chef->kitchen_type }}50 meals/day</span>
                        </div> --}}
                        {{-- <div class="detail-row">
                            <span class="detail-label">Assistants:</span>
                            <span class="detail-value">{{ $chef->assistants }}</span>
                        </div> --}}
                        <div class="detail-row">
                            <span class="detail-label">FSSAI License:</span>
                            <span class="detail-value">{{ $chef->fssai_license_number }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">FSSAI Validity Date:</span>
                            <span class="detail-value">{{ $chef->fssai_validity_date }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Working Hours:</span>
                            <div class="detail-value">
                                <div>{{ $chef->opening_time }} to {{ $chef->closing_time }}</div>
                            </div>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Working Days:</span>
                            <span class="detail-value">
                                @php
                                    $days = is_string($chef->working_days) 
                                        ? json_decode($chef->working_days, true) 
                                        : (is_array($chef->working_days) ? $chef->working_days : []);
                                @endphp
                        
                                {{ !empty($days) ? implode(', ', array_map('ucfirst', $days)) : '—' }}
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Continuous Audits:</span>
                            <span class="detail-value">{{ $chef->continuous_audits }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Chef Training:</span>
                            <span class="detail-value">{{ $chef->chef_training }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Onboarding Kit Receipt:</span>
                            <span class="detail-value">{{ $chef->onboarding_kit_receipt }}</span>
                        </div>
                    </div>

                    {{-- <div class="detail-card">
                        <h3>Service Information</h3> --}}
                    {{-- <div class="detail-row">
                            <span class="detail-label">Service Radius:</span>
                            <span class="detail-value">10 km</span>
                        </div> --}}
                    {{-- <div class="detail-row">
                            <span class="detail-label">Delivery Fee:</span>
                            <span class="detail-value">₹{{ $chef->delivery_fee }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Packaging:</span>
                            <span class="detail-value">{{ $chef->packaging }}</span>
                        </div> --}}
                    {{-- <div class="detail-row">
                            <span class="detail-label">Working Hours:</span>
                            <div class="detail-value">
                                <div>{{ $chef->working_hours }}</div>
                            </div>
                        </div>
                    </div> --}}

                    <div class="detail-card">
                        <h3>Financial Details</h3>
                        {{-- <div class="detail-row">
                            <span class="detail-label">Commission Rate:</span>
                            <span class="detail-value">15%</span>
                        </div> --}}
                        <div class="detail-row">
                            <span class="detail-label">Bank Name:</span>
                            <span class="detail-value">{{ $chef->bank_name }}</span>
                        </div>
                         <div class="detail-row">
                            <span class="detail-label">Banking Name:</span>
                            <span class="detail-value">{{ $chef->account_holder_name }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Account Number:</span>
                            <span class="detail-value">{{ $chef->account_number }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">IFSC Code:</span>
                            <span class="detail-value">{{ $chef->ifsc_code }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">PAN Card:</span>
                            <span class="detail-value">{{ $chef->pan_card }}</span>
                        </div>
                    </div>
                </div>

                {{-- <h2 class="section-title">Recent Reviews</h2>
                <div class="reviews-list">
                    <div class="review-card">
                        <div class="review-header">
                            <img src="https://randomuser.me/api/portraits/men/41.jpg" class="reviewer-image">
                            <div class="reviewer-info">
                                <div class="reviewer-name">Amit Sharma</div>
                                <div class="review-date">May 2, 2023</div>
                            </div>
                            <div class="review-rating">★★★★★</div>
                        </div>
                        <div class="review-content">
                            The biryani was absolutely amazing! Perfectly spiced and generous portion. Will definitely order
                            again.
                        </div>
                        <div class="review-order">
                            Ordered: Vegetable Biryani × 2, Garlic Naan
                        </div>
                    </div>

                    <div class="review-card">
                        <div class="review-header">
                            <img src="https://randomuser.me/api/portraits/women/33.jpg" class="reviewer-image">
                            <div class="reviewer-info">
                                <div class="reviewer-name">Priya Mehta</div>
                                <div class="review-date">April 28, 2023</div>
                            </div>
                            <div class="review-rating">★★★★☆</div>
                        </div>
                        <div class="review-content">
                            Delicious food but delivery was a bit late. The butter chicken was perfect though!
                        </div>
                        <div class="review-order">
                            Ordered: Butter Chicken, Jeera Rice, Naan
                        </div>
                    </div>
                </div> --}}

                {{-- <div class="view-all-reviews">
                    <a href="#">View all 128 reviews →</a>
                </div> --}}
            </div>
        </div>

        <div class="tab-content" id="menu">

            <div class="mb-3 text-end">
                <a href="{{ route('food_dishes.create', ['chef_id' => $chef->id]) }}" class="btn btn-primary">
                    + Add Dish
                </a>
                <a href="{{ route('pdf.export', ['model' => 'food_dishes', 'chef_id' => $chef->id]) }}" 
                    class="btn btn-sm btn-primary"> 
                    <i class="fa fa-file-pdf-o"></i> Export PDF
                </a>


            </div>

            <div class="table-responsive">
                <table class="table align-middle" style="border-collapse: collapse;" id="foodTable">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th>Image</th>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Base Price</th>
                            <th>Spicy Level</th>
                            <th>Weight</th>
                            <th>Prep. Time</th>
                            <th>Category</th>
                            <th>Recommended</th>
                            <th>In Stock?</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($foodItems as $item)
                            <tr style="border-bottom: 1px solid #eee;">
                                {{-- <td>
                                    @if ($item->image)
                                        <img src="{{ asset('/' . $item->image) }}" class="rounded-circle"
                                            style="width: 50px; height: 50px; object-fit: cover;" alt="Image">
                                    @else
                                        -
                                    @endif
                                </td> --}}
                                

                                <td>
                                    @php
                                        $firstImage = null;
                                
                                        if (!empty($item->image)) {
                                            // Try to decode JSON
                                            $decoded = json_decode($item->image, true);
                                
                                            if (json_last_error() === JSON_ERROR_NONE) {
                                                // It's an array (multiple images)
                                                $firstImage = $decoded[0] ?? null;
                                            } else {
                                                // It's a single image string
                                                $firstImage = $item->image;
                                            }
                                        }
                                    @endphp
                                
                                    @if ($firstImage && file_exists(public_path(str_replace('public/', '', $firstImage))))
                                        <img src="{{ asset($firstImage) }}" alt="Dish Image"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    @else
                                        <span>—</span>
                                    @endif
                                </td>




                                <td>{{ $item->name }}</td>
                                <td>{{ $item->description ?? '-' }}</td>
                                <td>₹{{ number_format($item->price, 2) }}</td>
                                <td>₹{{ number_format($item->base_price, 2) }}</td>
                                <td><span class="badge bg-danger">{{ $item->spicy_level }}</span></td>
                                <td><span class="badge bg-info">{{ $item->weight_option_id }}</span></td>
                                <td><span class="badge bg-success">{{ $item->preparation_time_id }}</span></td>
                                <td>
                                    @forelse ($item->categories as $cat)
                                        <span class="badge bg-warning">{{ $cat->title }}</span>
                                    @empty
                                        <span class="badge bg-secondary">—</span>
                                    @endforelse
                                </td>
                                <td class="text-center align-middle">
                                    <div class="form-check form-switch d-flex justify-content-center align-items-center m-0" style="height: 100%;">
                                        <input class="form-check-input toggle-recommended" type="checkbox"
                                               data-id="{{ $item->id }}" {{ $item->is_recommended ? 'checked' : '' }}>
                                    </div>
                                </td>

                                <td class="text-center align-middle">
                                    <div class="form-check form-switch d-flex justify-content-center align-items-center m-0" style="height: 100%;">
                                        <input class="form-check-input toggle-active" type="checkbox"
                                               data-id="{{ $item->id }}" {{ $item->in_stock ? 'checked' : '' }}>
                                    </div>
                                </td>



                                <td>
                                    <a href="{{ route('food_dishes.edit', $item->id) }}" class="text-warning me-2">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="{{ route('food_dishes.destroy', $item->id) }}" method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this dish?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn p-0 border-0 bg-transparent text-danger">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            {{-- <tr>
                                <td colspan="11" class="text-center text-muted">No dishes found.</td>
                            </tr> --}}
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>




        <!-- Orders Tab Content -->
        {{-- <div class="tab-content" id="orders">
            <h2 class="section-title">Recent Orders</h2>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#ORD-78945</td>
                        <td>Amit Sharma</td>
                        <td>Butter Chicken × 1, Naan × 2</td>
                        <td>₹18.99</td>
                        <td>May 15, 2023</td>
                        <td><span class="order-status status-completed">Completed</span></td>
                    </tr>
                    <tr>
                        <td>#ORD-78944</td>
                        <td>Priya Mehta</td>
                        <td>Vegetable Biryani × 2, Raita × 1</td>
                        <td>₹24.98</td>
                        <td>May 14, 2023</td>
                        <td><span class="order-status status-completed">Completed</span></td>
                    </tr>
                    <tr>
                        <td>#ORD-78943</td>
                        <td>Rahul Gupta</td>
                        <td>Paneer Tikka Masala × 1, Garlic Naan × 3</td>
                        <td>₹22.97</td>
                        <td>May 14, 2023</td>
                        <td><span class="order-status status-completed">Completed</span></td>
                    </tr>
                    <tr>
                        <td>#ORD-78942</td>
                        <td>Neha Patel</td>
                        <td>Dal Tadka × 1, Jeera Rice × 1, Roti × 2</td>
                        <td>₹16.99</td>
                        <td>May 13, 2023</td>
                        <td><span class="order-status status-completed">Completed</span></td>
                    </tr>
                    <tr>
                        <td>#ORD-78941</td>
                        <td>Vikram Singh</td>
                        <td>Chicken Biryani × 1, Raita × 1</td>
                        <td>₹15.99</td>
                        <td>May 13, 2023</td>
                        <td><span class="order-status status-completed">Completed</span></td>
                    </tr>
                    <tr>
                        <td>#ORD-78940</td>
                        <td>Ananya Joshi</td>
                        <td>Vegetable Thali × 1</td>
                        <td>₹12.99</td>
                        <td>May 12, 2023</td>
                        <td><span class="order-status status-completed">Completed</span></td>
                    </tr>
                    <tr>
                        <td>#ORD-78939</td>
                        <td>Rohan Malhotra</td>
                        <td>Butter Chicken × 2, Naan × 4</td>
                        <td>₹37.98</td>
                        <td>May 12, 2023</td>
                        <td><span class="order-status status-pending">Pending</span></td>
                    </tr>
                    <tr>
                        <td>#ORD-78938</td>
                        <td>Deepika Reddy</td>
                        <td>Paneer Butter Masala × 1, Naan × 2</td>
                        <td>₹18.99</td>
                        <td>May 11, 2023</td>
                        <td><span class="order-status status-cancelled">Cancelled</span></td>
                    </tr>
                </tbody>
            </table>
        </div> --}}

        {{-- <div class="tab-content" id="orders">
            <h2 class="section-title">Recent Orders</h2>
            <table class="orders-table" id="myTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                       
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->user->name ?? 'N/A' }}</td>
                            <td>{{ $order->items_text ?? 'N/A' }}</td>
                            <td>₹{{ number_format($order->amount, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</td>
                            <td>
                                <select class="form-control status-dropdown" data-order-id="{{ $order->id }}">
                                    <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                    <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>
                                        Completed</option>
                                    <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>
                                        Cancelled</option>
                                </select>
                            </td>
                            
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div> --}}

        <div class="table-responsive tab-content" id="orders">
            <h2 class="section-title mb-3">Recent Orders</h2>
            <div class="mb-3 text-end">
                <a href="{{ route('pdf.export', ['model' => 'chef_orders', 'chef_id' => $chef->id]) }}" 
                    class="btn btn-sm btn-primary"> 
                    <i class="fa fa-file-pdf-o"></i> Export PDF
                </a>


            </div>
            <table class="table align-middle" style="border-collapse: collapse;" id="ordersTable">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Payment Status</th>
                        
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr style="border-bottom: 1px solid #eee;">
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->user->name ?? 'N/A' }}</td>
                            <td>{{ $order->items_text ?? 'N/A'  }}</td>
                            <td>
                                <div>₹{{ number_format($order->total_amount, 2) }}</div>
                                <div style="font-size:11px;color:#888;white-space:nowrap;">
                                    Items ₹{{ number_format($order->items_subtotal, 2) }}
                                    + GST ₹{{ number_format($order->gst_amount, 2) }}
                                    + Fee ₹{{ number_format($order->platform_fee_amount, 2) }}
                                </div>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($order->date)->format('M d, Y') }}</td>

                            {{-- <td>
                                <select class="form-select form-select-sm status-dropdown"
                                    data-order-id="{{ $order->id }}">
                                    <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                    <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>
                                        Completed</option>
                                    <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>
                                        Cancelled</option>
                                </select>
                            </td> --}}

                            <td>
                                @php
                                    $statusClasses = [
                                        'new'       => 'btn-secondary',
                                        'accepted'  => 'btn-primary',
                                        'preparing' => 'btn-warning',
                                        'ready'     => 'btn-info',
                                        'delivered' => 'btn-success',
                                        'rejected'  => 'btn-danger',
                                    ];
                                @endphp
                            
                                <div class="btn-group">
                                    <button type="button"
                                        class="btn btn-sm {{ $statusClasses[$order->status] ?? 'btn-secondary' }} dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ ucfirst($order->status) }}
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="new">New</a></li>
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="accepted">Accepted</a></li>
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="preparing">Preparing</a></li>
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="ready">Ready</a></li>
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="delivered">Delivered</a></li>
                                        <li><a class="dropdown-item change-status" data-order-id="{{ $order->id }}" data-status="rejected">Rejected</a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>{{ $order->payment_status }}</td>

                        </tr>
                    @empty
                        {{-- <tr>
                            <td colspan="7" class="text-center text-muted">No orders found.</td>
                        </tr> --}}
                    @endforelse
                </tbody>
            </table>
        </div>



        <!-- Reviews Tab Content -->
        <div class="tab-content" id="reviews">
            <div class="review-summary">
                <div class="average-rating">
                    <div class="average-rating-value">{{ number_format($averageRating, 1) }}</div>
                    <div class="average-rating-stars">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= round($averageRating))
                                ★
                            @else
                                ☆
                            @endif
                        @endfor
                    </div>
                    <div class="average-rating-count">{{ $totalReviews }} reviews</div>
                </div>
        
                <div class="rating-progress-bars">
                    @foreach ($completeRatingCounts as $rating => $count)
                        @php
                            $percent = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
                        @endphp
                        
                        <div class="rating-progress d-flex align-items-center mb-2">
                            <div class="rating-label" style="width: 60px;">
                                {{ $rating }} Star{{ $rating > 1 ? 's' : '' }}
                            </div>
                
                            <div class="progress flex-grow-1 mx-2" style="height: 10px; background:#eee;">
                                <div class="progress-bar bg-warning" 
                                     role="progressbar" 
                                     style="width: {{ $percent }}%;">
                                </div>
                            </div>
                
                            <div class="rating-count" style="width: 30px; text-align:right;">
                                {{ $count }}
                            </div>
                        </div>
                    @endforeach
                </div>

        
            </div>
        
            <h2 class="section-title">All Reviews</h2>
            <div class="reviews-list">
                @foreach ($allReviews as $review)
                    <div class="review-card">
                        <div class="review-header">
                            <img src="https://randomuser.me/api/portraits/men/1.jpg" class="reviewer-image">
                            <div class="reviewer-info">
                                <div class="reviewer-name">{{ $review->name }}</div>
                                <div class="review-date">{{ \Carbon\Carbon::parse($review->created_at)->format('M d, Y') }}</div>
                            </div>
                            <div class="review-rating">
                                @for ($i = 1; $i <= 5; $i++)
                                    {{ $i <= $review->rating ? '★' : '☆' }}
                                @endfor
                            </div>
                        </div>
                        @if ($review->review)
                            <div class="review-content">
                                {{ $review->review }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>


        <!-- Earnings Tab Content -->
        <div class="tab-content" id="earnings">
            <div class="earnings-overview">
                <div class="earning-card">
                    <h3>Total Earnings <small style="font-weight:normal;color:#999;">(after {{ $chef->commission }}% commission)</small></h3>
                    <div class="earning-amount">₹{{ number_format($netEarnings, 2) }}</div>
                    <!--<div class="earning-change change-up">+12% from last month</div>-->
                </div>

                <div class="earning-card">
                    <h3>This Month <small style="font-weight:normal;color:#999;">(after {{ $chef->commission }}% commission)</small></h3>
                    <div class="earning-amount">₹{{ number_format($netThisMonthAmount, 2) }}</div>
                    <!--<div class="earning-change change-up">+8% from last month</div>-->
                </div>

                <div class="earning-card">
                    <h3>Last Month <small style="font-weight:normal;color:#999;">(after {{ $chef->commission }}% commission)</small></h3>
                    <div class="earning-amount">₹{{ number_format($netLastMonthAmount, 2) }}</div>
                    <!--<div class="earning-change change-down">-3% from previous</div>-->
                </div>

                <div class="earning-card">
                    <h3>This Year <small style="font-weight:normal;color:#999;">(after {{ $chef->commission }}% commission)</small></h3>
                    <div class="earning-amount">₹{{ number_format($netThisYearAmount, 2) }}</div>
                    <!--<div class="earning-change change-down">-3% from previous</div>-->
                </div>

                <!--<div class="earning-card">-->
                <!--    <h3>Avg. Order Value</h3>-->
                <!--    <div class="earning-amount">₹24.98</div>-->
                <!--    <div class="earning-change change-up">+5% from last month</div>-->
                <!--</div>-->
            </div>

            <!--<div class="earnings-chart">-->
            <!--    [Earnings Chart Would Appear Here]-->
            <!--</div>-->

            <!--<h2 class="section-title">Transaction History</h2>-->
            <!--<table class="orders-table">-->
            <!--    <thead>-->
            <!--        <tr>-->
            <!--            <th>Date</th>-->
            <!--            <th>Order ID</th>-->
            <!--            <th>Amount</th>-->
            <!--            <th>Commission</th>-->
            <!--            <th>Payout</th>-->
            <!--            <th>Status</th>-->
            <!--        </tr>-->
            <!--    </thead>-->
            <!--    <tbody>-->
            <!--        <tr>-->
            <!--            <td>May 15, 2023</td>-->
            <!--            <td>#ORD-78945</td>-->
            <!--            <td>₹18.99</td>-->
            <!--            <td>₹2.85</td>-->
            <!--            <td>₹16.14</td>-->
            <!--            <td><span class="order-status status-completed">Paid</span></td>-->
            <!--        </tr>-->
            <!--        <tr>-->
            <!--            <td>May 14, 2023</td>-->
            <!--            <td>#ORD-78944</td>-->
            <!--            <td>₹24.98</td>-->
            <!--            <td>₹3.75</td>-->
            <!--            <td>₹21.23</td>-->
            <!--            <td><span class="order-status status-completed">Paid</span></td>-->
            <!--        </tr>-->
            <!--        <tr>-->
            <!--            <td>May 14, 2023</td>-->
            <!--            <td>#ORD-78943</td>-->
            <!--            <td>₹22.97</td>-->
            <!--            <td>₹3.45</td>-->
            <!--            <td>₹19.52</td>-->
            <!--            <td><span class="order-status status-completed">Paid</span></td>-->
            <!--        </tr>-->
            <!--        <tr>-->
            <!--            <td>May 13, 2023</td>-->
            <!--            <td>#ORD-78942</td>-->
            <!--            <td>₹16.99</td>-->
            <!--            <td>₹2.55</td>-->
            <!--            <td>₹14.44</td>-->
            <!--            <td><span class="order-status status-completed">Paid</span></td>-->
            <!--        </tr>-->
            <!--        <tr>-->
            <!--            <td>May 13, 2023</td>-->
            <!--            <td>#ORD-78941</td>-->
            <!--            <td>₹15.99</td>-->
            <!--            <td>₹2.40</td>-->
            <!--            <td>₹13.59</td>-->
            <!--            <td><span class="order-status status-completed">Paid</span></td>-->
            <!--        </tr>-->
            <!--        <tr>-->
            <!--            <td>May 12, 2023</td>-->
            <!--            <td>#ORD-78940</td>-->
            <!--            <td>₹12.99</td>-->
            <!--            <td>₹1.95</td>-->
            <!--            <td>₹11.04</td>-->
            <!--            <td><span class="order-status status-pending">Processing</span></td>-->
            <!--        </tr>-->
            <!--        <tr>-->
            <!--            <td>May 12, 2023</td>-->
            <!--            <td>#ORD-78939</td>-->
            <!--            <td>₹37.98</td>-->
            <!--            <td>₹5.70</td>-->
            <!--            <td>₹32.28</td>-->
            <!--            <td><span class="order-status status-pending">Processing</span></td>-->
            <!--        </tr>-->
            <!--    </tbody>-->
            <!--</table>-->
        </div>

        <!-- Documents Tab Content -->
        {{-- <div class="tab-content" id="documents">
            <h2 class="section-title">Chef Documents</h2>
            <div class="documents-list">
                <div class="document-card">
                    <div class="document-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="document-info">
                        <div class="document-name">PAN Card</div>
                        <div class="document-details">Uploaded on Jan 15, 2022 • Expires: Never</div>
                    </div>
                    <div class="document-actions">
                        <div class="document-action" title="View" onclick="viewDocument('pan-card')">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="document-action" title="Download" onclick="downloadDocument('pan-card')">
                            <i class="fas fa-download"></i>
                        </div>
                    </div>
                </div>

                <div class="document-card">
                    <div class="document-icon">
                        <i class="fas fa-file-certificate"></i>
                    </div>
                    <div class="document-info">
                        <div class="document-name">FSSAI License</div>
                        <div class="document-details">Uploaded on Jan 18, 2022 • Expires: Jan 17, 2025</div>
                    </div>
                    <div class="document-actions">
                        <div class="document-action" title="View" onclick="viewDocument('fssai-license')">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="document-action" title="Download" onclick="downloadDocument('fssai-license')">
                            <i class="fas fa-download"></i>
                        </div>
                    </div>
                </div>

                <div class="document-card">
                    <div class="document-icon">
                        <i class="fas fa-passport"></i>
                    </div>
                    <div class="document-info">
                        <div class="document-name">Aadhaar Card</div>
                        <div class="document-details">Uploaded on Jan 20, 2022 • Expires: Never</div>
                    </div>
                    <div class="document-actions">
                        <div class="document-action" title="View" onclick="viewDocument('aadhaar-card')">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="document-action" title="Download" onclick="downloadDocument('aadhaar-card')">
                            <i class="fas fa-download"></i>
                        </div>
                    </div>
                </div>

                <div class="document-card">
                    <div class="document-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="document-info">
                        <div class="document-name">Kitchen Photos</div>
                        <div class="document-details">Uploaded on Feb 5, 2022 • 5 images</div>
                    </div>
                    <div class="document-actions">
                        <div class="document-action" title="View" onclick="viewDocument('kitchen-photos')">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="document-action" title="Download" onclick="downloadDocument('kitchen-photos')">
                            <i class="fas fa-download"></i>
                        </div>
                    </div>
                </div>

                <div class="document-card">
                    <div class="document-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="document-info">
                        <div class="document-name">Bank Details</div>
                        <div class="document-details">Uploaded on Jan 22, 2022 • HDFC Bank</div>
                    </div>
                    <div class="document-actions">
                        <div class="document-action" title="View" onclick="viewDocument('bank-details')">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="document-action" title="Download" onclick="downloadDocument('bank-details')">
                            <i class="fas fa-download"></i>
                        </div>
                    </div>
                </div>

                <div class="document-card">
                    <div class="document-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="document-info">
                        <div class="document-name">Food Safety Certificate</div>
                        <div class="document-details">Uploaded on Mar 10, 2022 • Expires: Mar 9, 2024</div>
                    </div>
                    <div class="document-actions">
                        <div class="document-action" title="View" onclick="viewDocument('food-safety-cert')">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="document-action" title="Download" onclick="downloadDocument('food-safety-cert')">
                            <i class="fas fa-download"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

            {{-- <div class="tab-content" id="documents">
            <h2 class="section-title">Chef Documents</h2>
            <div class="documents-list">
                @forelse ($documents as $doc)
                    @php
                        $ext = pathinfo($doc['path'], PATHINFO_EXTENSION);
                        $isPdf = strtolower($ext) === 'pdf';
                        $icon = match (true) {
                            str_contains(strtolower($doc['label']), 'gst') => 'fa-file-invoice',
                            str_contains(strtolower($doc['label']), 'fssai') => 'fa-file-invoice',
                            str_contains(strtolower($doc['label']), 'aadhar') => 'fa-id-card',
                            str_contains(strtolower($doc['label']), 'food') => 'fa-utensils',
                            $isPdf => 'fa-file-pdf',
                            default => 'fa-file-image',
                        };
                    
                        $fileUrl = asset($doc['path']);
                    
                        // ✅ File name without timestamp prefix
                        $fileName = basename($doc['path']); 
                        $parts = explode('_', $fileName, 3); // 3rd se aage jo hai wo lena
                        $originalName = isset($parts[2]) ? $parts[2] : $fileName;
                    @endphp
                    
                    <div class="document-card">
                        <div class="document-icon">
                            <i class="fas {{ $icon }}"></i>
                        </div>
                        <div class="document-info">
                            <div class="document-name">
                                {{ $doc['category'] }} - {{ $originalName }}
                            </div>
                        </div>
                        <div class="document-actions">
                            <div class="document-action" title="View" onclick="window.open('{{ $fileUrl }}', '_blank')">
                                <i class="fas fa-eye"></i>
                            </div>
                            <a href="{{ $fileUrl }}" download class="document-action" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>

                @empty
                    <p>No documents uploaded.</p>
                @endforelse
            </div>
        </div> --}}
        

       <div class="tab-content" id="documents">
            <h2 class="section-title">Chef Documents</h2>

            <div class="documents-wrapper">

                {{-- 🪪 Personal Documents --}}
                <div class="document-card">
                    <div class="doc-header">
                        <div class="doc-icon"><i class="fas fa-id-card"></i></div>
                        <div>
                            <h5>Personal Documents</h5>
                            <p>{{ ucfirst(str_replace('_', ' ', $chef->personal_document_type ?? 'Not Selected')) }}</p>
                        </div>
                    </div>

                    <div class="doc-body">
                        @php
                            $personalDocs = json_decode($chef->personal_documents ?? '[]', true);
                        @endphp

                        @if (!empty($personalDocs))
                            <div class="personal-docs-row">
                                <div class="personal-docs-left">
                                    @foreach ($personalDocs as $file)
                                        @php $ext = pathinfo($file, PATHINFO_EXTENSION); @endphp
                                        <div class="personal-doc-item">
                                            @if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png']))
                                                <img src="{{ asset($file) }}" alt="Document" class="single-doc-img">
                                            @else
                                                <a href="{{ asset($file) }}" target="_blank" class="pdf-link">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Status Dropdown --}}
                              {{-- Status Dropdown --}}
                                <div class="dropdown">
                                    @php
                                        $status = trim($chef->personal_document_status ?? '', "'"); // removes extra single quotes
                                    @endphp
                                    
                                    <button class="btn-status-dropdown dropdown-toggle 
                                        {{ $status == 'approved' ? 'btn-approved' : ($status == 'rejected' ? 'btn-rejected' : 'btn-select') }}" 
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ $status ? ucfirst($status) : 'Select Status' }}
                                    </button>

                                
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="updateStatus('{{ $chef->id }}', 'personal_document_status', 'approved')">Approved</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="updateStatus('{{ $chef->id }}', 'personal_document_status', 'rejected')">Rejected</a></li>
                                    </ul>
                                </div>




                                <form action="{{ route('chefs.downloadPersonalDocs', $chef->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-single-download">
                                        <i class="fas fa-download"></i> Download All
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="no-docs">No personal documents uploaded.</p>
                        @endif
                    </div>
                </div>
<br>
         {{-- 🍽️ FSSAI Certificate --}}
<div class="document-card">
    <div class="doc-header">
        <div class="doc-icon">
            <i class="fas fa-utensils"></i>
        </div>

        <div>
            <h5>FSSAI Certificate</h5>
        </div>
    </div>

    <div class="doc-body">

        @php
            $decodedDocs = json_decode($chef->fscai_certificate ?? '', true);

            if (is_array($decodedDocs) && !empty($decodedDocs)) {
                $fssaiDocs = $decodedDocs;
            } elseif (!empty($chef->fscai_certificate)) {
                $fssaiDocs = [$chef->fscai_certificate];
            } else {
                $fssaiDocs = [];
            }
        @endphp

        @if(count($fssaiDocs))

            <div class="personal-docs-row">

                <div class="personal-docs-left">

                    @foreach($fssaiDocs as $doc)

                        @php
                            // Ensure path format:
                            // https://api.nutritionnook.net/public/documents/file.pdf

                            $doc = ltrim($doc, '/');

                            // Add public/ if missing
                            if (!str_contains($doc, 'public/')) {
                                $doc = 'public/' . $doc;
                            }

                            $fileUrl = url($doc);

                            $ext = strtolower(pathinfo($doc, PATHINFO_EXTENSION));
                        @endphp

                        <div class="personal-doc-item">

                            @if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                                <img src="{{ $fileUrl }}"
                                     alt="FSSAI"
                                     class="single-doc-img">
                            @else
                                <a href="{{ $fileUrl }}"
                                   target="_blank"
                                   class="pdf-link">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            @endif

                        </div>

                    @endforeach

                </div>

                <div class="dropdown">

                    @php
                        $status = $chef->fssai_status ?? '';
                    @endphp

                    <button class="btn-status-dropdown dropdown-toggle 
                        {{ $status == 'approved' ? 'btn-approved' : ($status == 'rejected' ? 'btn-rejected' : 'btn-select') }}"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">

                        {{ $status ? ucfirst($status) : 'Select Status' }}

                    </button>

                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item"
                               href="#"
                               onclick="updateStatus('{{ $chef->id }}', 'fssai_status', 'approved')">
                               Approved
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="#"
                               onclick="updateStatus('{{ $chef->id }}', 'fssai_status', 'rejected')">
                               Rejected
                            </a>
                        </li>
                    </ul>

                </div>

                {{-- Download --}}
                <form action="{{ route('chefs.downloadFssaiDocs', $chef->id) }}"
                      method="POST">
                    @csrf

                    <button type="submit" class="btn-single-download">
                        <i class="fas fa-download"></i> Download
                    </button>
                </form>

            </div>

        @else
            <p class="no-docs">No FSSAI certificate uploaded.</p>
        @endif

    </div>
</div>
<br>
{{-- 📄 Self Declaration --}}
<div class="document-card">

    <div class="doc-header">
        <div class="doc-icon">
            <i class="fas fa-file-signature"></i>
        </div>

        <div>
            <h5>Self Declaration</h5>
        </div>
    </div>

    <div class="doc-body">

        @php
            $decodedDocs = json_decode($chef->self_declaration ?? '', true);

            if (is_array($decodedDocs) && !empty($decodedDocs)) {
                $selfDocs = $decodedDocs;
            } elseif (!empty($chef->self_declaration)) {
                $selfDocs = [$chef->self_declaration];
            } else {
                $selfDocs = [];
            }
        @endphp

        @if(count($selfDocs))

            <div class="personal-docs-row">

                <div class="personal-docs-left">

                    @foreach($selfDocs as $doc)

                        @php
                            $doc = ltrim($doc, '/');

                            // Ensure public/ exists
                            if (!str_contains($doc, 'public/')) {
                                $doc = 'public/' . $doc;
                            }

                            $fileUrl = url($doc);

                            $ext = strtolower(pathinfo($doc, PATHINFO_EXTENSION));
                        @endphp

                        <div class="personal-doc-item">

                            @if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                                <img src="{{ $fileUrl }}"
                                     alt="Self Declaration"
                                     class="single-doc-img">
                            @else
                                <a href="{{ $fileUrl }}"
                                   target="_blank"
                                   class="pdf-link">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            @endif

                        </div>

                    @endforeach

                </div>

                {{-- Download --}}
               <form action="{{ route('chefs.downloadSelfDocs', $chef->id) }}"
                      method="POST">
                    @csrf

                    <button type="submit" class="btn-single-download">
                        <i class="fas fa-download"></i> Download
                    </button>
                </form>

            </div>

        @else
            <p class="no-docs">No Self Declaration uploaded.</p>
        @endif

    </div>

</div>
           
            </div>
        </div>






        
        <div class="tab-content" id="audits">
            <div class="mb-3 text-end">
                <a href="{{ route('continuous_audits.create', ['chef_id' => $chef->id]) }}" class="btn btn-primary">
                    + Add Audit
                </a>
            </div>
        
            <div class="table-responsive">
                <table class="table align-middle" style="border-collapse: collapse;" id="auditTable">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th>Image</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($continuousAudits as $audit)
                            <tr style="border-bottom: 1px solid #eee;">
                                {{-- Image --}}
                                <td>
                                    @php
                                        $firstImage = null;
                                        if (!empty($audit->images)) {
                                            $decoded = json_decode($audit->images, true);
                                            $firstImage = json_last_error() === JSON_ERROR_NONE ? ($decoded[0] ?? null) : $audit->images;
                                        }
                                    @endphp
                                
                                    @if ($firstImage && file_exists(public_path(str_replace('public/', '', $firstImage))))
                                        <img src="{{ asset($firstImage) }}" alt="Audit Image"
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    @else
                                        <span>�</span>
                                    @endif
                                </td>
                    
                                <td>{{ \Carbon\Carbon::parse($audit->date)->format('d M Y') }}</td>
                                <td>{{ $audit->description ?? '-' }}</td>
                    
                                <td>
                                    <a href="{{ route('continuous_audits.edit', $audit->id) }}" class="text-warning me-2">
                                        <i class="fas fa-edit"></i>
                                    </a>
                    
                                    <form action="{{ route('continuous_audits.destroy', $audit->id) }}" method="POST"
                                          style="display:inline;"
                                          onsubmit="return confirm('Are you sure you want to delete this audit?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn p-0 border-0 bg-transparent text-danger">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
        
        
        <div class="tab-content" id="photos">
                <div class="mb-3 text-end">
                    <a href="{{ route('kitchen_photos.create', ['chef_id' => $chef->id]) }}" class="btn btn-primary">
                        + Add Kitchen Photo
                    </a>
                </div>
            
                <div class="table-responsive">
    <table class="table align-middle" style="border-collapse: collapse;" id="kitchenphotoTable">
        <thead style="background-color: #f8f9fa;">
            <tr>
                <th>Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @php
            
    // Decode the images from chef table
    $decoded = [];
    $displayImages = [];
    $totalImages = 0;
    $photoId = $kitchenPhotos->id ?? 1;
    
    if (!empty($kitchenPhotos->kitchen_assessment_photographs)) {
        $data = $kitchenPhotos->kitchen_assessment_photographs;
        
        // Check if it's already an array or JSON string
        if (is_array($data)) {
            $decoded = $data;
        } else {
            // Try to decode JSON
            $decoded = json_decode($data, true);
            
            // If JSON decode failed, treat as single file path
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $decoded = [$data];
            }
        }
        
        // Filter out empty values
        $decoded = array_filter($decoded);
        
        // Sort only if we have valid images
        if (!empty($decoded)) {
            // Sort by timestamp in filename (DESC - newest first)
            usort($decoded, function($a, $b) {
                if (empty($a) || empty($b)) return 0;
                
                preg_match('/(\d+)_kitchen_/', basename($a), $matchesA);
                preg_match('/(\d+)_kitchen_/', basename($b), $matchesB);
                
                $timeA = isset($matchesA[1]) ? (int)$matchesA[1] : 0;
                $timeB = isset($matchesB[1]) ? (int)$matchesB[1] : 0;
                
                return $timeB <=> $timeA; // DESC order (newest first)
            });
            
            // Reset array keys
            $decoded = array_values($decoded);
        }
        
        $totalImages = count($decoded);
        $displayImages = array_slice($decoded, 0, 3);
    }
@endphp



            @if($totalImages > 0)
                <tr style="border-bottom: 1px solid #eee;">
                    {{-- Image Column --}}
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="d-flex" style="position: relative; height: 45px;">
                                @foreach ($displayImages as $index => $img)
                                    @php
                                        $offset = $index * 20;
                                        $imgPath = asset($img);
                                    @endphp

                                    @if (file_exists(public_path(str_replace(['public/', 'public'], '', $img))))
                                        <div style="
                                            width: 45px;
                                            height: 45px;
                                            border-radius: 50%;
                                            overflow: hidden;
                                            border: 2px solid #fff;
                                            position: relative;
                                            left: -{{ $offset }}px;
                                            margin-left: {{ $index === 0 ? '0' : '-20px' }};
                                            box-shadow: 0 0 3px rgba(0,0,0,0.2);
                                            z-index: {{ 100 - $index }};
                                            transition: all 0.2s ease;
                                        " class="image-hover">
                                            <img src="{{ $imgPath }}" alt="Image"
                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            
                            @if($totalImages > 3)
                                <span class="ms-3 text-muted small fw-bold" style="cursor: pointer;" 
                                      data-bs-toggle="modal" data-bs-target="#viewModal{{ $photoId }}">
                                    +{{ $totalImages - 3 }} more
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Actions --}}
                    <td>
                        {{-- View Button --}}
                        <button type="button"
                                class="btn p-0 border-0 bg-transparent text-info me-3"
                                data-bs-toggle="modal"
                                data-bs-target="#viewModal{{ $photoId }}"
                                title="View All Images">
                            <i class="fas fa-eye fs-5"></i>
                        </button>

                        {{-- Edit Button --}}
                        <a href="{{ route('kitchen_photos.edit', $kitchenPhotos->id ?? $chefId) }}" 
                           class="text-warning me-3" 
                           title="Edit">
                            <i class="fas fa-edit fs-5"></i>
                        </a>

                        {{-- Delete Button --}}
                        <form action="{{ route('kitchen_photos.destroy', $kitchenPhotos->id ?? $chefId) }}" method="POST"
                              style="display:inline;"
                              onsubmit="return confirm('Are you sure you want to delete all kitchen photos?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn p-0 border-0 bg-transparent text-danger" title="Delete All">
                                <i class="fas fa-trash-alt fs-5"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                {{-- Modal for viewing all images --}}
                <div class="modal fade" id="viewModal{{ $photoId }}" tabindex="-1" aria-labelledby="viewModalLabel{{ $photoId }}" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                        <div class="modal-content" style="max-height: 90vh;">
                            <div class="modal-header">
                                <h5 class="modal-title" id="viewModalLabel{{ $photoId }}">
                                    <i class="fas fa-images me-2"></i>All Kitchen Images
                                    <span class="badge bg-secondary ms-2">{{ $totalImages }} images</span>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" style="overflow-y: auto; max-height: calc(90vh - 120px);">
                                @if($totalImages > 0)
                                    <div class="row g-4">
                                        @php
                                            // Sort again for modal to ensure DESC order
                                            $modalImages = $decoded;
                                            usort($modalImages, function($a, $b) {
                                                preg_match('/(\d+)_kitchen_/', basename($a), $matchesA);
                                                preg_match('/(\d+)_kitchen_/', basename($b), $matchesB);
                                                
                                                $timeA = isset($matchesA[1]) ? (int)$matchesA[1] : 0;
                                                $timeB = isset($matchesB[1]) ? (int)$matchesB[1] : 0;
                                                
                                                return $timeB <=> $timeA;
                                            });
                                        @endphp
                                        
                                        @foreach($modalImages as $index => $img)
                                            @php
                                                $timestamp = '';
                                                preg_match('/(\d+)_kitchen_/', basename($img), $matches);
                                                if(isset($matches[1])) {
                                                    $timestamp = date('d M Y H:i', (int)$matches[1]);
                                                }
                                            @endphp
                                            
                                            @if (file_exists(public_path(str_replace(['public/', 'public'], '', $img))))
                                                <div class="col-lg-3 col-md-4 col-sm-6">
                                                    <div class="image-card">
                                                        <div class="image-wrapper" style="
                                                            position: relative;
                                                            width: 100%;
                                                            padding-bottom: 100%;
                                                            overflow: hidden;
                                                            border-radius: 10px;
                                                            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                                                            background: #f8f9fa;
                                                        ">
                                                            <a href="{{ asset($img) }}" 
                                                               class="glightbox" 
                                                               data-gallery="gallery{{ $photoId }}"
                                                               data-title="Image {{ $index + 1 }} - Uploaded: {{ $timestamp }}">
                                                                <img src="{{ asset($img) }}" 
                                                                     alt="Kitchen Image {{ $index + 1 }}" 
                                                                     style="
                                                                        position: absolute;
                                                                        top: 0;
                                                                        left: 0;
                                                                        width: 100%;
                                                                        height: 100%;
                                                                        object-fit: contain;
                                                                        padding: 8px;
                                                                        transition: transform 0.3s ease;
                                                                     "
                                                                     class="modal-image">
                                                            </a>
                                                        </div>
                                                        <div class="image-footer mt-2 text-center">
                                                            <small class="text-muted d-block">
                                                                Image {{ $index + 1 }}
                                                                @if($timestamp)
                                                                    <span class="d-block small">Uploaded: {{ $timestamp }}</span>
                                                                @endif
                                                            </small>
                                                            <a href="{{ asset($img) }}" download class="text-primary mt-1 d-inline-block" title="Download">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-5">
                                        <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">No images found.</p>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <tr>
                    <td colspan="2" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-image fa-2x mb-2"></i>
                            <p class="mb-0">No kitchen assessment photographs found.</p>
                            <a href="{{ route('kitchen_photos.edit', $kitchenPhotos->id ?? $chefId) }}" class="btn btn-sm btn-gradient-primary mt-3">
                                <i class="fas fa-upload me-2"></i>Upload Photos
                            </a>
                        </div>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

@push('styles')
<style>
    /* Image hover effect */
    .image-hover:hover {
        transform: scale(1.05);
        z-index: 999 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
    }
    
    /* Modal image hover effect */
    .modal-image:hover {
        transform: scale(1.05);
        cursor: pointer;
    }
    
    /* Custom scrollbar for modal */
    .modal-body::-webkit-scrollbar {
        width: 8px;
    }
    
    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .modal-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* Image card styles */
    .image-card {
        transition: all 0.3s ease;
        border-radius: 12px;
        padding: 5px;
    }
    
    .image-card:hover {
        transform: translateY(-5px);
        background: white;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    
    /* Fix for stacked images */
    .d-flex[style*="position: relative"] {
        margin-right: 15px;
    }
    
    /* Modal XL size */
    .modal-xl {
        max-width: 95%;
    }
    
    @media (min-width: 1200px) {
        .modal-xl {
            max-width: 1140px;
        }
    }
    
    /* Badge styling */
    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }
    
    /* Download icon */
    .fa-download {
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }
    
    .fa-download:hover {
        opacity: 1;
    }
    
    /* Disabled button */
    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize glightbox if you're using it
        if (typeof GLightbox !== 'undefined') {
            const lightbox = GLightbox({
                selector: '.glightbox',
                touchNavigation: true,
                loop: true,
                zoomable: true,
                draggable: true,
                width: '100vw',
                height: '100vh'
            });
        }
        
        // Fix for modal scroll
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                document.body.style.overflow = 'hidden';
            });
            
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.style.overflow = '';
            });
        });
    });
</script>
@endpush
            </div>

        {{-- <div class="action-buttons">
            <button class="btn btn-edit">
                <i class="fas fa-edit"></i> Edit Profile
            </button>
            <button class="btn btn-suspend">
                <i class="fas fa-pause"></i> Suspend Account
            </button>
            <button class="btn btn-delete">
                <i class="fas fa-trash"></i> Delete Chef
            </button>
            <button class="btn btn-message">
                <i class="fas fa-envelope"></i> Send Message
            </button>
        </div> --}}
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
            
    <script>
                document.addEventListener('DOMContentLoaded', function () {
                    GLightbox({
                        selector: '.glightbox',
                        touchNavigation: true,
                        loop: true,
                        closeButton: true,
                        slideEffect: 'fade',
                    });
                });
            </script>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    tab.classList.add('active');
                    const tabId = tab.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });

        // Document view/download functionality
        function viewDocument(docType) {
            alert(`Viewing document: ₹{docType}`);
            // In a real application, this would open a modal or new tab with the document
        }

        function downloadDocument(docType) {
            alert(`Downloading document: ₹{docType}`);
            // In a real application, this would trigger a file download
            // Example: window.location.href = `/download-document?type=₹{docType}`;
        }
    </script>
    
    <script>
        document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('toggle-active')) {
            let checkbox = e.target;
            let id = checkbox.getAttribute('data-id');
            let isChecked = checkbox.checked;
    
            let url = `{{ route('food_dishes.toggleActive', ':id') }}`.replace(':id', id);
    
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        timer: 1200,
                        showConfirmButton: false
                    });
                } else {
                    checkbox.checked = !isChecked; // revert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong!'
                    });
                }
            })
            .catch(error => {
                checkbox.checked = !isChecked; // revert
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Please try again later!'
                });
            });
        }
    });
    
    </script>

    <script>
        document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('toggle-recommended')) {
            let checkbox = e.target;
            let id = checkbox.getAttribute('data-id');
            let isChecked = checkbox.checked;
    
            let url = `{{ route('food_dishes.toggleRecommended', ':id') }}`.replace(':id', id);
    
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        timer: 1200,
                        showConfirmButton: false
                    });
                } else {
                    checkbox.checked = !isChecked; // revert back
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong!'
                    });
                }
            })
            .catch(error => {
                checkbox.checked = !isChecked; // revert back
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Please try again later!'
                });
            });
        }
    });
    </script>

    <script>
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('change-status')) {
            let orderId = e.target.getAttribute('data-order-id');
            let status = e.target.getAttribute('data-status');

            fetch(`{{ route('orders.updateStatus') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: status
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // ✅ Map Laravel status => Bootstrap btn class
                        const statusClasses = {
                            new: 'btn-secondary',
                            accepted: 'btn-primary',
                            preparing: 'btn-warning',
                            ready: 'btn-info',
                            delivered: 'btn-success',
                            rejected: 'btn-danger'
                        };

                        // ✅ Update button text & color instantly
                        let btn = e.target.closest('.btn-group').querySelector('button');
                        btn.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                        btn.className = `btn btn-sm dropdown-toggle ${statusClasses[status] ?? 'btn-secondary'}`;

                        // ✅ Show SweetAlert success popup
                        Swal.fire({
                            title: 'Success!',
                            text: `Order status updated to "${status.charAt(0).toUpperCase() + status.slice(1)}".`,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    }
                });
        }
    });
</script>

    <script>
        $(document).ready(function() {
            // Refund button click
            $('.refund-btn').click(function() {
                var orderId = $(this).data('order-id');
                var maxAmount = $(this).data('amount');

                Swal.fire({
                    title: 'Refund Amount',
                    input: 'number',
                    inputLabel: 'Enter amount to refund (Max: ₹' + maxAmount + ')',
                    inputPlaceholder: 'Enter refund amount',
                    inputAttributes: {
                        min: 1,
                        max: maxAmount,
                        step: 0.01
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Refund',
                    showLoaderOnConfirm: true,
                    preConfirm: (refundAmount) => {
                        if (!refundAmount || refundAmount <= 0 || refundAmount > maxAmount) {
                            Swal.showValidationMessage('Please enter a valid amount');
                            return false;
                        }
                        // You can send to server here if needed
                        return refundAmount;
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Success!',
                            text: '₹' + result.value + ' will be refunded (simulation).',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.approval-toggle');

        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const reviewId = this.dataset.id;
                const isApproved = this.checked;

                const routeTemplate =
                    "{{ route('chefs.reviews.toggle-approval', ['id' => '__id__']) }}";
                const finalUrl = routeTemplate.replace('__id__', reviewId);

                fetch(finalUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            is_approved: isApproved ? 1 : 0
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        const statusDiv = document.getElementById(`status-${reviewId}`);
                        statusDiv.textContent = data.status_text;

                        Swal.fire({
                            icon: 'success',
                            title: data.status_text,
                            text: 'Review status updated successfully!',
                            confirmButtonText: 'OK',
                            showConfirmButton: true
                        });
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!',
                            confirmButtonText: 'OK',
                            showConfirmButton: true
                        });
                        console.error('Error:', error);
                    });
            });
        });
    });
</script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const lightbox = GLightbox({
            selector: '.glightbox', // sab images ko select karo
            loop: true,             // last image ke baad first pe
            touchNavigation: true,
            closeButton: true,
            slideEffect: 'slide',
        });
    });
</script>


    <script>
        function handleStatusChange(select, id) {
            const form = select.closest('.status-form');
        
            // Status immediately update in DB
            form.submit();
        }
    </script>
    
    <script>
        document.querySelectorAll('.status-option').forEach(option => {
            option.addEventListener('click', function(e){
                e.preventDefault();
                const dropdown = this.closest('.dropdown');
                const btn = dropdown.querySelector('.status-btn');
                const input = dropdown.querySelector('input[name="status"]');
                const value = this.getAttribute('data-value');
    
                // Update button text and color
                btn.textContent = value.charAt(0).toUpperCase() + value.slice(1);
                btn.classList.remove('btn-success', 'btn-danger');
                btn.classList.add(value === 'approved' ? 'btn-success' : 'btn-danger');
    
                // Update hidden input
                input.value = value;
    
                // Auto-submit the form
                dropdown.closest('form').submit();
            });
        });
    </script>


<script>
function updateStatus(id, field, status) {
    fetch(`{{ route('chefs.updateDocumentStatus', ':id') }}`.replace(':id', id), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ field, status })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload(); // page reload to update button color
        }
    });
}
</script>




@endsection
