@extends('frontend.layouts.master')

@section('title','Point Transactions | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/order-history.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <!-- Shop Login -->
    <div class="container custom-max-container">
        <div class="order-history-white-sec">
            <h3>Your Points</h3>
            <div class="royalty-btn-sec"><span class="royalty-btn-span">Loyalty Points : {{ $loyaltyPoint }}</span>
            </div>
            <div class="oder-history-table-sec">
                <div id="myGrid" style="display: block; height: 600px; width: 100%; float: left; margin: 25px 0 0 0;"
                     class="ag-theme-balham"></div>
            </div>
        </div>
    </div>

@endsection
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- <script src="https://unpkg.com/@ag-grid-enterprise/all-modules/dist/ag-grid-enterprise.js"></script> -->
<script src="{{asset('public/js/ag-grid-enterprise.js')}}"></script>
<script src="{{asset('public/js/ag-grid-license.js')}}"></script>

<script type="text/javascript">
    var customerId = '{{ $customerId }}';
    var locationNo = '{{ $locationNo }}';
    var locationName = '{{ $locationName }}';
    var agGridLicenseKey = '{{ $aggridKey  }}';
    // specify the columns
    var gridDiv;
    var loadmsg = "Please wait while we process your request....";

    // agGrid.LicenseManager.setLicenseKey(agGridLicenseKey);
    var columnDefs = [
        {headerName: "Date", field: "date", filter: "agTextColumnFilter"},
        {headerName: "Point Type", field: "point_type", filter: "agTextColumnFilter"},
        {headerName: "Invoice No.", field: "invoice_no", filter: "agTextColumnFilter"},
        {headerName: "Point IN", field: "point_in", filter: "agTextColumnFilter"},
        {headerName: "Point OUT", field: "point_out", filter: "agTextColumnFilter"},
        {headerName: "Point BALANCE", field: "point_balance", filter: "agTextColumnFilter"},
        {headerName: "Location", field: "location", filter: "agTextColumnFilter"},
        {headerName: "Valid Points", field: "valid_points", filter: "agTextColumnFilter"},
        {headerName: "Valid Date", field: "valid_date", filter: "agTextColumnFilter"},
        {headerName: "Valid Days", field: "valid_days", filter: "agTextColumnFilter"},
    ];

    var gridOptions = {
        statusBar: {
            statusPanels: [
                {statusPanel: 'agTotalRowCountComponent', align: 'left'},
                {statusPanel: 'agFilteredRowCountComponent', align: 'left'},
                {statusPanel: 'agSelectedRowCountComponent', align: 'left'},
                {statusPanel: 'agAggregationComponent', align: 'left'}
            ]
        },
        columnDefs: columnDefs,
        defaultColDef: {
            resizable: true
        },
        pagination: true,
        overlayLoadingTemplate: '<span class="ag-overlay-loading-center">' + loadmsg + '</span>',
        defaultExportParams: {
            suppressTextAsCDATA: true // This is necessary for sheetJs to read the resultant file
        },
        onGridReady: function (params) {
            autoSizeAllcols();
        },
        getRowNodeId: function (data) {
            return data.sub_invoice;
        },
        suppressCellSelection: true,
    };

    // setup the grid after the page has finished loading
    document.addEventListener('DOMContentLoaded', function () {
        var gridDiv = document.querySelector('#myGrid');
        new agGrid.Grid(gridDiv, gridOptions);
        createData();
    });

    function autoSizeAllcols() {
        var allColumnIds = [];
        gridOptions.columnApi.getAllColumns().forEach(function (column) {
            allColumnIds.push(column.colId);
        });
        gridOptions.api.sizeColumnsToFit(allColumnIds);
    }

    function onFilterTextBoxChanged() {
        gridOptions.api.setQuickFilter(document.getElementById('filter-text-box').value);
    }

    function createData() {
        gridOptions.api.showLoadingOverlay();

        $.ajax({
            url: '{{ route("fetch-points") }}',
            type: "POST",
            data: { 'customer_id':customerId },
            dataType: "json",
            beforeSend: function () {
            },
            success: function(data) {
                gridOptions.api.hideOverlay();
                gridOptions.api.setRowData(data);
                autoSizeAllcols();
            },
        });
    }

    function build_store_cell(params) {
        var data = '#' + params.data.store_no + '(' + params.data.store_name + ')';
        return data;
    }

    function build_action_cell(params) {
        console.log(params.data);
        var sub_invoice = params.data.sub_invoice;
        return '<a href="{{ url("order-details") }}/'+ customerId + '/' + sub_invoice + '" target="_blank" class="action">View</a>';
    }

    $(document).on('click', '.reorder-btn', function (e) {
        e.preventDefault();

        var current_element = $(this);
        var current_invoice = current_element.attr('data-invoice');

        $.ajax({
            url: '{{ route("reorder") }}',
            type: "POST",
            data: {
                'invoice': current_invoice
            },
            dataType: "json",
            beforeSend: function () {
            },
            success: function (response) {
                if (response.result === true) {
                    $(".cart_count_html").text(response.data);
                    window.location.href = '{{ route("my-basket") }}';
                } else {
                    showMessage(response.message);
                }

            }
        });
    });

    $(document).on('click', '.export-btn', function (e) {
        e.preventDefault();
        var current_element = $(this);
        var current_invoice = current_element.attr('data-invoice');

        // Update the URL to use the named route
        $.ajax({
            url: '{{ route("export-excel") }}', // Use the named route
            type: "POST",
            data: {
                'func': 'export-excel',
                'invoice': current_invoice
            },
            dataType: "json",
            beforeSend: function () {
            },
            success: function (response) {
                if (response.result) {
                    // Trigger the download link
                    var downloadLink = document.createElement('a');
                    downloadLink.href = baseUrl + '/public/uploads/excel/' + response.data;
                    downloadLink.download = response.data;
                    downloadLink.click();
                } else {
                    alert('Download failed. Please try again.');
                }
            }
        });
    });
</script>
