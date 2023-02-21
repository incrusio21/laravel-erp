<div class="horizontal-layout horizontal-menu blank-page navbar-floating footer-static">
    @section('custom_css')
        @yield('view_css') 
        
        @viteReactRefresh
        @vite(['resources/js/app.js', 'resources/js/main.tsx'])
    @endsection

    <div id="root">
        <div class="fallback-spinner app-loader">
            <img class="fallback-logo" src="data:image/png;base64,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" alt="logo">
            <div class="spinner-border text-primary mt-1" style="width: 4rem;height: 4rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    </div>
    @section('custom_js')
    <!-- BEGIN: Page JS-->
    
    @yield('view_js')
    
    <!-- END: Page JS-->
    @endsection
    
</div>