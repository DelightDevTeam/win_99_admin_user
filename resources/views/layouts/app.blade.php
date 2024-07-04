@include('layouts.head')
@yield('style')

<body>
  <div class="main">
    <!-- NAVBAR START -->
    @include('layouts.navbar')
    <!-- NAVBAR END -->

    @yield('content')
    <!-- FOOTER START -->
    @include('layouts.footer')
    <!-- FOOTER END -->
  </div>

  <!-- SIDE BAR -->
  @include('layouts.sidebar')
  <!-- SIDE BAR -->

  <!-- TOP UP AND WITHDRAW -->
  @include('layouts.topup-model')
</body>
@include('layouts.js')
@yield('script')