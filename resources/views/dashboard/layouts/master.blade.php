<!doctype html>

    <html
      lang="ar"
      class="light-style layout-navbar-fixed layout-menu-fixed layout-compact"
      @if (App::getLocale() == 'ar')
      dir="rtl"
      @else
      dir="ltr"
      @endif
      data-theme="theme-default"
      data-assets-path="../../dashboard/assets/"
      data-template="vertical-menu-template"
      data-style="light">
@include('dashboard.layouts.head')
<style>

    .app-sidebar .slide.active .side-menu__item {
        background: #53649021;
    }
    .slide.is-expanded .side-menu__item
    {
        background: #53649021;
    }
    .side-menu__label
    {
        font-weight: bold;
    }
    .dot-label {
        right: -6px !important;
    }
    body{
        font-family: 'Alexandria', sans-serif;
        font-optical-sizing: auto;
        font-weight: 200;
        font-style: normal;


    }
    body, html {
        overflow-y: scroll; /* لعرض شريط التمرير العمودي دائمًا */
        overflow-x: scroll; /* لعرض شريط التمرير الأفقي دائمًا */
    }
    /* For Webkit browsers (Chrome, Safari) */
    ::-webkit-scrollbar {
        width: 10px; /* Adjust width to make it thicker */
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1; /* Background color of the scrollbar track */
    }

    ::-webkit-scrollbar-thumb {
        background: #888; /* Color of the scrollbar thumb */
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #555; /* Color when hovering over the scrollbar thumb */
    }

    .red-btn:hover {
        background-color: red !important;
        color: white !important;
    }
    .bg-menu-theme.menu-vertical .menu-item.active > .menu-link:not(.menu-toggle) {
        /*background: linear-gradient(270deg, rgba(115, 103, 240, 0.7) 0%, #7367f0 100%);*/
        /*background: linear-gradient(270deg, rgba(154, 114, 87, 0.7) 0%, #9A7257 100%);*/
        /*background: linear-gradient(270deg, rgba(154, 114, 87, 0.3) 0%, #9A7257 100%);*/
        background: linear-gradient(270deg, #C5A87D 0%, #9A7257 100%);
        /*box-shadow: 0 0 10px 1px rgba(115, 103, 240, 0.7);*/
        box-shadow: 0 0 10px 1px rgba(154, 114, 87, 0.7);
        color: #fff !important;
    }
    .page-item.active .page-link, .page-item.active .page-link:hover, .page-item.active .page-link:focus, .pagination li.active > a:not(.page-link), .pagination li.active > a:not(.page-link):hover, .pagination li.active > a:not(.page-link):focus {
        background-color: #9A7257 !important;
        border-color: #9A7257 !important;
        color: #fff !important;
    }
    .btn-primary{
        background-color: #9A7257 !important;
        border-color: #9A7257 !important;
    }

    .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
        background-color: #C5A87D !important;
        border-color: #C5A87D !important;
    }


</style>


  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
          @include('dashboard.layouts.sidebar')

        <!-- Layout container -->
        <div class="layout-page">
           @include('dashboard.layouts.header')

          <!-- Content wrapper -->
          <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
            <!-- Content -->

{{--                    @if(Session::has('success'))--}}
{{--                    <div class="alert alert-success alert-dismissible" role="alert">--}}
{{--                        {{Session::get('success')}}--}}
{{--                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>--}}
{{--                      </div>--}}
{{--                    @elseif(Session::has('error'))--}}
{{--                    <div class="alert alert-danger alert-dismissible" role="alert">--}}
{{--                        {{Session::get('error')}}--}}
{{--                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>--}}
{{--                      </div>--}}
{{--                    @endif--}}


             @yield('content')
            <!-- / Content -->

              </div>
          </div>

            @include('dashboard.layouts.footer')

            <div class="content-backdrop fade"></div>

          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>

      <!-- Drag Target Area To SlideIn Menu On Small Screens -->
      <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

   @include('dashboard.layouts.scripts')
  </body>
</html>



