@include('erp::header')

<body @class([ $class ])>

    <!-- BEGIN: Content-->
    {{ $slot }}
    <!-- END: Content-->

    {{ doctype_script() }}
    <!-- SCRIPT -->
    @include('erp::footer')


    
    <!-- END SCRIPT -->
</body>