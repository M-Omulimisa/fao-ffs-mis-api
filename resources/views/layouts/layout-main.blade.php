@extends('layouts.base-layout')

@section('base-content')
    <!-- Page wrapper for sticky footer -->
    <!-- Wraps everything except footer to push footer to the bottom of the page if there is little content -->
    

        @include('layouts.header')
   
            @yield('main-content')
        </main>
        @include('layouts.footer')






@endsection
